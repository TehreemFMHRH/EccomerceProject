<?php

namespace Webkul\CartRule\Listeners;

use Webkul\CartRule\Repositories\CartRuleCouponRepository;
use Webkul\CartRule\Repositories\CartRuleCouponUsageRepository;
use Webkul\CartRule\Repositories\CartRuleCustomerRepository;
use Webkul\CartRule\Repositories\CartRuleRepository;

class Order
{
    
    public function __construct(
        protected CartRuleRepository $cartRuleRepository,
        protected CartRuleCustomerRepository $cartRuleCustomerRepository,
        protected CartRuleCouponRepository $cartRuleCouponRepository,
        protected CartRuleCouponUsageRepository $cartRuleCouponUsageRepository
    ) {}

    
    public function manageCartRule($o)
    {
        if (! $o->discount_amount) {
            return;
        }

        $cartRuleIds = explode(',', $o->applied_cart_rule_ids);

        $cartRuleIds = array_unique($cartRuleIds);

        foreach ($cartRuleIds as $ruleId) {
            $rule = $this->cartRuleRepository->find($ruleId);

            if (! $rule) {
                continue;
            }

            $rule->update(['times_used' => $rule->times_used + 1]);

            if (! $o->customer_id) {
                continue;
            }

            $ruleCustomer = $this->cartRuleCustomerRepository->findOneWhere([
                'customer_id'  => $o->customer_id,
                'cart_rule_id' => $ruleId,
            ]);

            if ($ruleCustomer) {
                $this->cartRuleCustomerRepository->update(['times_used' => $ruleCustomer->times_used + 1], $ruleCustomer->id);
            } else {
                $this->cartRuleCustomerRepository->create([
                    'customer_id'  => $o->customer_id,
                    'cart_rule_id' => $ruleId,
                    'times_used'   => 1,
                ]);
            }
        }

        if (! $o->coupon_code) {
            return;
        }

        $coupon = $this->cartRuleCouponRepository->findOneByField('code', $o->coupon_code);

        if ($coupon) {
            $this->cartRuleCouponRepository->update(['times_used' => $coupon->times_used + 1], $coupon->id);

            if ($o->customer_id) {
                $couponUsage = $this->cartRuleCouponUsageRepository->findOneWhere([
                    'customer_id'         => $o->customer_id,
                    'cart_rule_coupon_id' => $coupon->id,
                ]);

                if ($couponUsage) {
                    $this->cartRuleCouponUsageRepository->update(['times_used' => $couponUsage->times_used + 1], $couponUsage->id);
                } else {
                    $this->cartRuleCouponUsageRepository->create([
                        'customer_id'         => $o->customer_id,
                        'cart_rule_coupon_id' => $coupon->id,
                        'times_used'          => 1,
                    ]);
                }
            }
        }
    }
}
