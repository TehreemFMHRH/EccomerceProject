<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Promotions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Marketing\Promotions\CartRuleCouponDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\CartRule\Repositories\CartRuleCouponRepository;

class CartRuleCouponController extends Controller
{
    
    public function __construct(protected CartRuleCouponRepository $cartRuleCouponRepository) {}

    
    public function index(int $i)
    {
        return datagrid(CartRuleCouponDataGrid::class)->process();
    }

    
    public function store($i): JsonResponse
    {
        $this->validate(request(), [
            'coupon_qty'  => 'required|integer|min:1',
            'code_length' => 'required|integer|min:10',
            'code_format' => 'required',
        ]);

        if (! $i) {
            return new JsonResponse([
                'message' => trans('admin::app.promotions.cart-rules-coupons.cart-rule-not-defined-error'),
            ], 400);
        }

        $this->cartRuleCouponRepository->generateCoupons(request()->only(
            'coupon_qty',
            'code_length',
            'code_format',
            'code_prefix',
            'code_suffix'
        ), $i);

        return new JsonResponse([
            'message' => trans(
                'admin::app.marketing.promotions.cart-rules-coupons.success', ['name' => 'Cart rule coupons']
            ),
        ]);
    }

    
    public function destroy(int $i): JsonResponse
    {
        try {
            $this->cartRuleCouponRepository->delete($i);

            return new JsonResponse([
                'message' => trans('admin::app.marketing.promotions.cart-rules-coupons.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.marketing.promotions.cart-rules-coupons.cart-rule-not-defined-error'),
            ], 400);
        }
    }

    
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $couponIds = $massDestroyRequest->input('indices');

        foreach ($couponIds as $couponId) {
            $coupon = $this->cartRuleCouponRepository->find($couponId);

            if ($coupon) {
                Event::dispatch('cart_rules.coupons.delete.before', $coupon);

                $this->cartRuleCouponRepository->delete($couponId);

                Event::dispatch('cart_rules.coupons.delete.after', $coupon);
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.marketing.promotions.cart-rules-coupons.mass-delete-success'),
        ]);
    }
}
