<?php

use Illuminate\Http\UploadedFile;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryTranslation;
use Webkul\Faker\Helpers\Category as CategoryFaker;
use illuminate\Support\Facades\DB;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should show category page', function () {

    $this->loginAsAdmin();

    get(route('admin.catalog.categories.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.index.title'));
});

it('should show category edit page', function () {

    $a = (new CategoryFaker)->factory()->create();


    $this->loginAsAdmin();

    get(route('admin.catalog.categories.edit', $a->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.edit.title'));
});

it('should return listing items of categories', function () {

    $a = (new CategoryFaker)->factory()->create();


    $this->loginAsAdmin();

    getJson(route('admin.catalog.categories.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertJsonPath('records.0.category_id', $a->id)
        ->assertJsonPath('meta.total', 2);
});

it('should fail the validation with errors of logo path is not an array and image', function () {

    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'logo_path'   => fake()->word(),
        'banner_path' => [UploadedFile::fake()->create('banner.jpg')],
    ])
        ->assertJsonValidationErrorFor('logo_path')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fails the image validation error when provided tempered logo and banner', function () {

    $results = DB::select("SELECT id FROM attributes WHERE is_filterable = 1");
    $attributes = array_map(function($row) { return $row->id; }, $results);



    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'slug'        => fake()->slug(),
        'name'        => fake()->name(),
        'position'    => rand(1, 5),
        'description' => substr(fake()->paragraph(), 0, 50),
        'attributes'  => $attributes,
        'logo_path'   => [
            UploadedFile::fake()->image('logo.php'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.js'),
        ],
    ])
        ->assertJsonValidationErrorFor('logo_path.0')
        ->assertJsonValidationErrorFor('banner_path.0')
        ->assertUnprocessable();
});

it('should create a category', function () {

    $results = DB::select("SELECT id FROM attributes WHERE is_filterable = 1");
$attributes = array_map(function($row) { return $row->id; }, $results);



    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), $dat = [
        'slug'        => fake()->slug(),
        'name'        => fake()->name(),
        'position'    => rand(1, 5),
        'description' => substr(fake()->paragraph(), 0, 50),
        'attributes'  => $attributes,
        'logo_path'   => [
            UploadedFile::fake()->image('logo.png'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.png'),
        ],
    ])
        ->assertRedirect(route('admin.catalog.categories.index'))
        ->isRedirection();

    $this->assertModelWise([
        CategoryTranslation::class => [
            [
                'slug'        => $dat['slug'],
                'name'        => $dat['name'],
                'description' => $dat['description'],
            ],
        ],
    ]);
});

it('should fail the validation with errors when certain inputs are not provided when store in category', function () {

    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'))
        ->assertJsonValidationErrorFor('attributes')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fail the validation with errors of description if display mode products_and_description when store', function () {

    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'display_mode' => 'products_and_description',
    ])
        ->assertJsonValidationErrorFor('attributes')
        ->assertJsonValidationErrorFor('description')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fail the validation with errors slug is already taken', function () {

    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'slug' => 'root',
    ])
        ->assertJsonValidationErrorFor('attributes')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fail the validation with errors when certain inputs are not provided when update in category', function () {

    $a = (new CategoryFaker)->factory()->create();

    $localeCode = core()->getRequestedLocaleCode();


    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $a->id))
        ->assertJsonValidationErrorFor($localeCode.'.name')
        ->assertJsonValidationErrorFor($localeCode.'.slug')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('attributes')
        ->assertUnprocessable();
});

it('should fail the validation with errors when certain inputs are not provided and display mode products and description when update in category', function () {

    $a = (new CategoryFaker)->factory()->create();

    $localeCode = core()->getRequestedLocaleCode();


    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $a->id), [
        'display_mode' => 'products_and_description',
    ])
        ->assertJsonValidationErrorFor($localeCode.'.name')
        ->assertJsonValidationErrorFor($localeCode.'.slug')
        ->assertJsonValidationErrorFor($localeCode.'.description')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('attributes')
        ->assertUnprocessable();
});

it('should fails the validation with certain provided inputs', function () {

    $a = (new CategoryFaker)->factory()->create();

    $results = DB::select("SELECT id FROM attributes WHERE is_filterable = 1");
$attributes = array_map(function($row) { return $row->id; }, $results);



    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $a->id), [
        'en' => [
            'name'        => $n = fake()->name(),
            'slug'        => $a->slug,
            'description' => $d = substr(fake()->paragraph(), 0, 50),
        ],
        'locale'      => config('app.locale'),
        'attributes'  => $attributes,
        'position'    => rand(1, 5),
        'logo_path'   => [
            UploadedFile::fake()->image('logo.py'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.js'),
        ],
    ])
        ->assertJsonValidationErrorFor('logo_path.0')
        ->assertJsonValidationErrorFor('banner_path.0')
        ->assertUnprocessable();
});

it('should update a category', function () {

    $a = (new CategoryFaker)->factory()->create();

    $results = DB::select("SELECT id FROM attributes WHERE is_filterable = 1");
$attributes = array_map(function($row) { return $row->id; }, $results);



    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $a->id), [
        'en' => $dat = [
            'name'        => fake()->name(),
            'description' => substr(fake()->paragraph(), 0, 50),
            'slug'        => $a->slug,
        ],
        'locale'      => config('app.locale'),
        'attributes'  => $attributes,
        'position'    => rand(1, 5),
        'logo_path'   => [
            UploadedFile::fake()->image('logo.png'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.png'),
        ],
    ])
        ->assertRedirect(route('admin.catalog.categories.index'))
        ->isRedirection();

    $this->assertModelWise([
        CategoryTranslation::class => [
            [
                'name'        => $dat['name'],
                'slug'        => $a->slug,
                'description' => $dat['description'],
            ],
        ],
    ]);
});

it('should delete a category', function () {

    $a = (new CategoryFaker)->factory()->create();


    $this->loginAsAdmin();

    deleteJson(route('admin.catalog.categories.delete', $a->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.delete-success'));

    $this->assertDatabaseMissing('categories', [
        'id' => $a->id,
    ]);
});

it('should delete mass categories', function () {

    $categories = (new CategoryFaker)->create(5);


    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.mass_delete', [
        'indices' => $categories->pluck('id')->toArray(),
    ]))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.delete-success'));

    foreach ($categories as $a) {
        $this->assertDatabaseMissing('categories', [
            'id' => $a->id,
        ]);
    }
});

it('should update mass categories', function () {

    $categories = (new CategoryFaker)->create(5);


    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.mass_update', [
        'indices' => $categories->pluck('id')->toArray(),
        'value'   => 1,
    ]))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.update-success'));

    foreach ($categories as $a) {
        $this->assertModelWise([
            Category::class => [
                [
                    'id'     => $a->id,
                    'status' => 1,
                ],
            ],
        ]);
    }
});

it('should search categories with mega search', function () {

    $a = (new CategoryFaker)->factory()->create();


    $this->loginAsAdmin();

    getJson(route('admin.catalog.categories.search', [
        'query' => $a->name,
    ]))
        ->assertOk()
        ->assertJsonPath('data.0.id', $a->id)
        ->assertJsonPath('total', 1);
});

it('should show the tree view of categories', function () {

    $a = (new CategoryFaker)->factory()->create();


    $this->loginAsAdmin();

    getJson(route('admin.catalog.categories.tree'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $a->id);
});
