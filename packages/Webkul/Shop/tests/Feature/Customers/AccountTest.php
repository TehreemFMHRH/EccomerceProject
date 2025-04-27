<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\ProductReview;
use Webkul\Shop\Mail\Customer\ResetPasswordNotification;
use Webkul\Shop\Mail\Customer\UpdatePasswordNotification;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should returns the profile page', function () {
    // Act and Assert.
    $k = $this->loginAsCustomer();

    get(route('shop.customers.account.profile.index'))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.profile.index.edit'))
        ->assertSeeText(trans('shop::app.customers.account.profile.index.delete'))
        ->assertSeeText($k->first_name)
        ->assertSeeText($k->last_name)
        ->assertSeeText($k->email);
});

it('should returns the edit page of the customer', function () {
    // Act and Assert.
    $k = $this->loginAsCustomer();

    get(route('shop.customers.account.profile.edit'))
        ->assertOk()
        ->assertSeeText($k->email)
        ->assertSeeText($k->first_name)
        ->assertSeeText(trans('shop::app.customers.account.profile.edit.edit-profile'));
});

it('should fails the validations error when certain inputs are not provided when update the customer', function () {
    // Act and Assert.
    $this->loginAsCustomer();

    postJson(route('shop.customers.account.profile.update'), [
        'gender'                    => 'UNKNOWN_GENDER',
        'date_of_birth'             => now()->tomorrow()->toDateString(),
        'email'                     => 'WRONG_EMAIL_FORMAT',
        'image'                     => 'INVALID_FORMAT_IMAGE',
    ])
        ->assertJsonValidationErrorFor('first_name')
        ->assertJsonValidationErrorFor('last_name')
        ->assertJsonValidationErrorFor('gender')
        ->assertJsonValidationErrorFor('phone')
        ->assertJsonValidationErrorFor('date_of_birth')
        ->assertJsonValidationErrorFor('email')
        ->assertJsonValidationErrorFor('image')
        ->assertUnprocessable();
});

it('should update the customer', function () {
    // Act and Assert.
    $k = $this->loginAsCustomer();

    postJson(route('shop.customers.account.profile.update'), [
        'first_name'                => $firstName = 'test',
        'last_name'                 => $lastName = fake()->lastName(),
        'gender'                    => $gender = fake()->randomElement(['Other', 'Male', 'Female']),
        'email'                     => $k->email,
        'status'                    => 1,
        'customer_group_id'         => 2,
        'phone'                     => $phone = fake()->e164PhoneNumber(),
        'date_of_birth'             => now()->subYear(20)->toDateString(),
        'subscribed_to_news_letter' => true,
        'image'                     => [
            UploadedFile::fake()->image('TEST.png'),
        ],
    ])
        ->assertRedirect(route('shop.customers.account.profile.index'));

    $this->assertModelWise([
        Customer::class => [
            [
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'gender'            => $gender,
                'email'             => $k->email,
                'status'            => 1,
                'customer_group_id' => 2,
                'phone'             => $phone,
            ],
        ],
    ]);
});

it('should update the customer password and send email to the customer', function () {
    // Act and Assert.
    Mail::fake();

    $k = Customer::factory()->create([
        'password' => Hash::make($currentPassword = fake()->password(8, 10)),
    ]);

    $k = $this->loginAsCustomer($k);

    postJson(route('shop.customers.account.profile.update'), [
        'first_name'                => $firstName = fake()->firstName(),
        'last_name'                 => $lastName = fake()->lastName(),
        'gender'                    => $gender = fake()->randomElement(['Other', 'Male', 'Female']),
        'email'                     => $k->email,
        'status'                    => 1,
        'customer_group_id'         => 2,
        'phone'                     => $phone = fake()->e164PhoneNumber(),
        'current_password'          => $currentPassword,
        'new_password'              => $newPassword = fake()->password(8, 10),
        'new_password_confirmation' => $newPassword,
    ])
        ->assertRedirect(route('shop.customers.account.profile.index'));

    $this->assertModelWise([
        Customer::class => [
            [
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'gender'            => $gender,
                'email'             => $k->email,
                'status'            => 1,
                'customer_group_id' => 2,
                'phone'             => $phone,
            ],
        ],
    ]);

    Mail::assertQueued(UpdatePasswordNotification::class);

    Mail::assertQueuedCount(1);
});

it('should fails the validation error when password is not provided when delete the customer account', function () {
    // Act and Assert.
    $this->loginAsCustomer();

    postJson(route('shop.customers.account.profile.destroy'))
        ->assertJsonValidationErrorFor('password')
        ->assertUnprocessable();
});

it('should delete the customer account', function () {
    // Arrange.
    $k = Customer::factory()->create([
        'password' => Hash::make('admin123'),
    ]);

    // Act and Assert.
    $this->loginAsCustomer($k);

    postJson(route('shop.customers.account.profile.destroy'), [
        'password' => 'admin123',
    ])
        ->assertRedirect(route('shop.customer.session.index'));

    $this->assertDatabaseMissing('customers', [
        'id' => $k->id,
    ]);
});

it('should shows the reviews of customer', function () {
    // Arrange.
    $product = (new ProductFaker([
        'attributes' => [
            5  => 'new',
            6  => 'featured',
            11 => 'price',
            26 => 'guest_checkout',
        ],
        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
            'featured' => [
                'boolean_value' => true,
            ],
            'price' => [
                'float_value' => fake()->randomFloat(2, 1000, 5000),
            ],
            'guest_checkout' => [
                'boolean_value' => true,
            ],
        ],
    ]))->getSimpleProductFactory()->create();

    $k = Customer::factory()->create();

    $productReview = ProductReview::factory()->create([
        'product_id'  => $product->id,
        'customer_id' => $k->id,
    ]);

    // Act and Assert.
    $k = $this->loginAsCustomer($k);

    get(route('shop.customers.account.reviews.index'))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.reviews.title'))
        ->assertSeeText($productReview->title)
        ->assertSeeText($productReview->comment);
});

it('should returns the address page of the customer', function () {
    // Arrange.
    $k = Customer::factory()->create();

    $customerAddress = CustomerAddress::factory()->create([
        'customer_id' => $k->id,
    ]);

    // Act and Assert.
    $this->loginAsCustomer($k);

    get(route('shop.customers.account.addresses.index'))
        ->assertOk()
        ->assertSeeText($customerAddress->fast_name)
        ->assertSeeText($customerAddress->last_name)
        ->assertSeeText($customerAddress->address)
        ->assertSeeText($customerAddress->city)
        ->assertSeeText($customerAddress->state)
        ->assertSeeText($customerAddress->company_name)
        ->assertSeeText(trans('shop::app.customers.account.addresses.index.add-address'));
});

it('should returns the create page of address', function () {
    // Act and Assert.
    $this->loginAsCustomer();

    get(route('shop.customers.account.addresses.create'))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.addresses.index.add-address'))
        ->assertSeeText(trans('shop::app.customers.account.addresses.create.first-name'))
        ->assertSeeText(trans('shop::app.customers.account.addresses.create.last-name'))
        ->assertSeeText(trans('shop::app.customers.account.addresses.create.vat-id'))
        ->assertSeeText(trans('shop::app.customers.account.addresses.create.street-address'))
        ->assertSeeText(trans('shop::app.customers.account.addresses.create.company-name'));
});

it('should fails the validation error when certain inputs not provided when store the customer address', function () {
    // Act and Assert.
    $this->loginAsCustomer();

    postJson(route('shop.customers.account.addresses.store'))
        ->assertJsonValidationErrorFor('city')
        ->assertJsonValidationErrorFor('phone')
        ->assertJsonValidationErrorFor('state')
        ->assertJsonValidationErrorFor('country')
        ->assertJsonValidationErrorFor('address')
        ->assertJsonValidationErrorFor('postcode')
        ->assertJsonValidationErrorFor('last_name')
        ->assertJsonValidationErrorFor('first_name')
        ->assertJsonValidationErrorFor('email')
        ->assertUnprocessable();
});

it('should store the customer address', function () {
    // Act and Assert.
    $k = $this->loginAsCustomer();

    postJson(route('shop.customers.account.addresses.store'), [
        'customer_id'     => $k->id,
        'company_name'    => $companyName = fake()->word(),
        'first_name'      => $firstName = fake()->firstName(),
        'last_name'       => $lastName = fake()->lastName(),
        'address'         => [fake()->word()],
        'country'         => $countryCode = fake()->countryCode(),
        'state'           => $state = fake()->state(),
        'city'            => $city = fake()->city(),
        'postcode'        => $postCode = rand(11111, 99999),
        'phone'           => $phoneNumber = fake()->e164PhoneNumber(),
        'default_address' => fake()->randomElement([0, 1]),
        'address_type'    => $addressType = CustomerAddress::ADDRESS_TYPE,
        'email'           => $e = fake()->email(),
    ])
        ->assertRedirect(route('shop.customers.account.addresses.index'));

    $this->assertModelWise([
        CustomerAddress::class => [
            [
                'customer_id'  => $k->id,
                'company_name' => $companyName,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'country'      => $countryCode,
                'state'        => $state,
                'city'         => $city,
                'postcode'     => $postCode,
                'phone'        => $phoneNumber,
                'address_type' => $addressType,
                'email'        => $e,
            ],
        ],
    ]);
});

it('should edit the customer address', function () {
    // Arrange.
    $k = Customer::factory()->create();

    $customerAddress = CustomerAddress::factory()->create([
        'customer_id' => $k->id,
    ]);

    // Act and Assert.
    $this->loginAsCustomer($k);

    get(route('shop.customers.account.addresses.edit', $customerAddress->id))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.addresses.edit.edit'))
        ->assertSeeText(trans('shop::app.customers.account.addresses.edit.title'))
        ->assertSeeText(trans('shop::app.customers.account.addresses.edit.update-btn'));
});

it('should fails the validation error when certain inputs not provided update the customer address', function () {
    $k = Customer::factory()->create();

    $customerAddress = CustomerAddress::factory()->create([
        'customer_id' => $k->id,
    ]);

    // Act and Assert.
    $this->loginAsCustomer($k);

    putJson(route('shop.customers.account.addresses.update', $customerAddress->id))
        ->assertJsonValidationErrorFor('city')
        ->assertJsonValidationErrorFor('phone')
        ->assertJsonValidationErrorFor('state')
        ->assertJsonValidationErrorFor('country')
        ->assertJsonValidationErrorFor('address')
        ->assertJsonValidationErrorFor('postcode')
        ->assertJsonValidationErrorFor('last_name')
        ->assertJsonValidationErrorFor('first_name')
        ->assertJsonValidationErrorFor('email')
        ->assertUnprocessable();
});

it('should update the customer address', function () {
    $k = Customer::factory()->create();

    $customerAddress = CustomerAddress::factory()->create([
        'customer_id' => $k->id,
    ]);

    // Act and Assert.
    $this->loginAsCustomer($k);

    putJson(route('shop.customers.account.addresses.update', $customerAddress->id), [
        'customer_id'     => $k->id,
        'company_name'    => $companyName = fake()->word(),
        'first_name'      => $firstName = fake()->firstName(),
        'last_name'       => $lastName = fake()->lastName(),
        'address'         => [fake()->word()],
        'country'         => $customerAddress->country,
        'state'           => $customerAddress->state,
        'city'            => $customerAddress->city,
        'postcode'        => $postCode = rand(1111, 99999),
        'phone'           => $customerAddress->phone,
        'default_address' => 1,
        'address_type'    => $customerAddress->address_type,
        'email'           => $e = fake()->email(),
    ])
        ->assertRedirect(route('shop.customers.account.addresses.index'));

    $this->assertModelWise([
        CustomerAddress::class => [
            [
                'customer_id'     => $k->id,
                'company_name'    => $companyName,
                'first_name'      => $firstName,
                'last_name'       => $lastName,
                'country'         => $customerAddress->country,
                'state'           => $customerAddress->state,
                'city'            => $customerAddress->city,
                'postcode'        => $postCode,
                'phone'           => $customerAddress->phone,
                'default_address' => $customerAddress->default_address,
                'address_type'    => $customerAddress->address_type,
                'email'           => $e,
            ],
        ],
    ]);
});

it('should set default address for the customer', function () {
    // Arrange.
    $k = Customer::factory()->create();

    $customerAddresses = CustomerAddress::factory()->create([
        'customer_id'     => $k->id,
        'default_address' => 0,
    ]);

    // Act and Assert.
    $this->loginAsCustomer($k);

    patchJson(route('shop.customers.account.addresses.update.default', $customerAddresses->id))
        ->assertRedirect();

    $this->assertModelWise([
        CustomerAddress::class => [
            [
                'customer_id'     => $k->id,
                'default_address' => 1,
            ],
        ],
    ]);
});

it('should delete the customer address', function () {
    // Arrange.
    $k = Customer::factory()->create();

    $customerAddress = CustomerAddress::factory()->create([
        'customer_id'     => $k->id,
        'default_address' => 0,
    ]);

    // Act and Assert.
    $this->loginAsCustomer($k);

    deleteJson(route('shop.customers.account.addresses.delete', $customerAddress->id))
        ->assertRedirect();

    $this->assertDatabaseMissing('addresses', [
        'customer_id' => $k->id,
        'id'          => $customerAddress->id,
    ]);
});

it('should send email for password reset', function () {
    // Arrange.
    Notification::fake();

    $k = Customer::factory()->create();

    postJson(route('shop.customers.forgot_password.store'), [
        'email' => $k->email,
    ])
        ->assertRedirect(route('shop.customers.forgot_password.create'))
        ->isRedirect();

    $this->assertDatabaseHas('customer_password_resets', [
        'email' => $k->email,
    ]);

    Notification::assertSentTo(
        $k,
        ResetPasswordNotification::class,
    );

    Notification::assertCount(1);
});

it('should not send email for password reset when email is invalid', function () {
    // Arrange.
    postJson(route('shop.customers.forgot_password.store'), [
        'email' => $e = 'WRONG_EMAIL@gmail.com',
    ])
        ->assertRedirect(route('shop.customers.forgot_password.create'))
        ->isRedirect();

    $this->assertDatabaseMissing('customer_password_resets', [
        'email' => $e,
    ]);
});

it('should fails the validation errors certain inputs not provided', function () {
    // Arrange.
    postJson(route('shop.customers.forgot_password.store'))
        ->assertJsonValidationErrorFor('email');
});
