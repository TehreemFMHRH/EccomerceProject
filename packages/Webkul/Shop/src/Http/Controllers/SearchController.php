<?php

namespace Webkul\Shop\Http\Controllers;

use Webkul\Marketing\Repositories\SearchTermRepository;
use Webkul\Product\Repositories\SearchRepository;

class SearchController extends Controller
{
    
    public function __construct(
        protected SearchTermRepository $searchTermRepository,
        protected SearchRepository $searchRepository
    ) {}

    
    public function index()
    {
        $this->validate(request(), [
            'query' => ['sometimes', 'required', 'string', 'regex:/^[^\\\\]+$/u'],
        ]);

        $searchTerm = $this->searchTermRepository->findOneWhere([
            'term'       => request()->query('query'),
            'channel_id' => core()->getCurrentChannel()->id,
            'locale'     => app()->getLocale(),
        ]);

        if ($searchTerm?->redirect_url) {
            return redirect()->to($searchTerm->redirect_url);
        }

        return view('shop::search.index');
    }

    
    public function upload()
    {
        return $this->searchRepository->uploadSearchImage(request()->all());
    }
}
