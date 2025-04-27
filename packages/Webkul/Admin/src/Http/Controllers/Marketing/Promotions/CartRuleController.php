<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Promotions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\DataGrids\Marketing\Promotions\CartRuleDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\CartRuleRequest;
use Webkul\CartRule\Repositories\CartRuleRepository;

class CartRuleController extends Controller
{
    
    public function __construct(protected CartRuleRepository $cartRuleRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CartRuleDataGrid::class)->process();
        }

        return view('admin::marketing.promotions.cart-rules.index');
    }

    
    public function create()
    {
        return view('admin::marketing.promotions.cart-rules.create');
    }

    
    public function copy(int $cartRuleId)
    {
        $cartRule = $this->cartRuleRepository->with(['channels', 'customer_groups'])->findOrFail($cartRuleId);

        $copiedCartRule = $cartRule->replicate()->fill([
            'status' => 0,
            'name'   => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.copy-of', ['value' => $cartRule->name]),
        ]);

        $copiedCartRule->save();

        foreach ($copiedCartRule->channels as $channel) {
            $copiedCartRule->channels()->save($channel);
        }

        foreach ($copiedCartRule->customer_groups as $group) {
            $copiedCartRule->customer_groups()->save($group);
        }

        return view('admin::marketing.promotions.cart-rules.edit', [
            'cartRule' => $copiedCartRule,
        ]);
    }

    
    public function store(CartRuleRequest $cartRuleRequest)
    {
        try {
            Event::dispatch('promotions.cart_rule.create.before');

            $cartRule = $this->cartRuleRepository->create($cartRuleRequest->all());

            Event::dispatch('promotions.cart_rule.create.after', $cartRule);

            session()->flash('success', trans('admin::app.marketing.promotions.cart-rules.create.create-success'));

            return redirect()->route('admin.marketing.promotions.cart_rules.index');
        } catch (ValidationException $e) {
            if ($firstError = collect($e->errors())->first()) {
                session()->flash('error', $firstError[0]);
            }
        }

        return redirect()->back();
    }

    
    public function edit(int $i)
    {
        $cartRule = $this->cartRuleRepository->findOrFail($i);

        return view('admin::marketing.promotions.cart-rules.edit', compact('cartRule'));
    }

    
    public function update(CartRuleRequest $cartRuleRequest, int $i)
    {
        try {
            $cartRule = $this->cartRuleRepository->findOrFail($i);

            if ($cartRule->coupon_type) {
                if ($cartRule->cart_rule_coupon) {
                    $this->validate(request(), [
                        'coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code,'.$cartRule->cart_rule_coupon->id,
                    ]);
                } else {
                    $this->validate(request(), [
                        'coupon_code' => 'required_if:use_auto_generation,==,0|unique:cart_rule_coupons,code',
                    ]);
                }
            }

            Event::dispatch('promotions.cart_rule.update.before', $i);

            $cartRule = $this->cartRuleRepository->update($cartRuleRequest->all(), $i);

            Event::dispatch('promotions.cart_rule.update.after', $cartRule);

            session()->flash('success', trans('admin::app.marketing.promotions.cart-rules.edit.update-success'));

            return redirect()->route('admin.marketing.promotions.cart_rules.index');
        } catch (ValidationException $e) {
            if ($firstError = collect($e->errors())->first()) {
                session()->flash('error', $firstError[0]);
            }
        }

        return redirect()->back();
    }

    
    public function destroy(int $i): JsonResponse
    {
        $this->cartRuleRepository->findOrFail($i);

        try {
            Event::dispatch('promotions.cart_rule.delete.before', $i);

            $this->cartRuleRepository->delete($i);

            Event::dispatch('promotions.cart_rule.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.marketing.promotions.cart-rules.delete-success'
                )]);
        } catch (Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.marketing.promotions.cart-rules.delete-failed'
            )], 400);
    }
}
