<?php

namespace Webkul\Shop\Http\Controllers;

use Webkul\Attribute\Repositories\AttributeFamilyRepository;

class CompareController extends Controller
{
    
    public function __construct(protected AttributeFamilyRepository $attributeFamilyRepository) {}

    
    public function index()
    {
        $comparableAttributes = $this->attributeFamilyRepository->getComparableAttributesBelongsToFamily();

        return view('shop::compare.index', compact('comparableAttributes'));
    }
}
