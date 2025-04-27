<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductReviewAttachmentRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\Shop\Http\Resources\ProductReviewResource;

class ReviewController extends APIController
{
    
    public function __construct(

        protected ProductReviewRepository $productReviewRepository,
        protected ProductReviewAttachmentRepository $productReviewAttachmentRepository
    ) {}

    
    const STATUS_APPROVED = 'approved';

    const STATUS_PENDING = 'pending';

    
    public function index(int $i): JsonResource
    {
        $product = Product
            ->findOrFail($i)
            ->reviews()
            ->where('status', self::STATUS_APPROVED)
            ->paginate(8);

        if (core()->getConfigData('catalog.products.review.censoring_reviewer_name')) {
            $product->getCollection()->transform(function ($review) {
                $review->name = $this->censorReviewerName($review->name);

                return $review;
            });
        }

        return ProductReviewResource::collection($product);
    }

    
    public function store(int $i): JsonResource
    {
        $this->validate(request(), [
            'title'         => 'required',
            'comment'       => 'required',
            'rating'        => 'required|numeric|min:1|max:5',
            'attachments'   => 'array',
            'attachments.*' => 'file|mimetypes:image/*,video/*',
        ]);

        $dat = array_merge(request()->only([
            'title',
            'comment',
            'rating',
        ]), [
            'attachments' => request()->file('attachments') ?? [],
            'status'      => self::STATUS_PENDING,
            'product_id'  => $i,
        ]);

        $dat['name'] = auth()->guard('customer')->user()?->name ?? request()->input('name');
        $dat['customer_id'] = auth()->guard('customer')->id() ?? null;

        $review = $this->productReviewRepository->create($dat);

        $this->productReviewAttachmentRepository->upload($dat['attachments'], $review);

        return new JsonResource([
            'message' => trans('shop::app.products.view.reviews.success'),
        ]);
    }

    
    public function translate(int $reviewId): JsonResponse
    {
        $review = $this->productReviewRepository->find($reviewId);

        $currentLocale = core()->getCurrentLocale();

        $prompt = "
        Translate the following product review to $currentLocale->name. Ensure that the translation retains the sentiment and conveys the meaning accurately. If specific product-related terms or expressions are commonly used in the $currentLocale->name, please adapt accordingly.
        ---

        **Original Product Review:**
        $review->comment

        ---
        Translation:
        ";

        try {
            $model = core()->getConfigData('general.magic_ai.review_translation.model');

            $resp = MagicAI::setModel($model)
                ->setPrompt($prompt)
                ->ask();

            return new JsonResponse([
                'content' => $resp,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    private function censorReviewerName(string $na): string
    {
        return collect(explode(' ', $na))
            ->map(fn ($part) => substr($part, 0, 1).str_repeat('*', max(strlen($part) - 1, 0)))
            ->join(' ');
    }
}
