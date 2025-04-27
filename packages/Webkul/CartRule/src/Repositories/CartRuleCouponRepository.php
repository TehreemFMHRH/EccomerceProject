<?php

namespace Webkul\CartRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CartRuleCouponRepository extends Repository
{
    
    protected $charset = [
        'alphanumeric' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        'alphabetical' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'numeric'      => '0123456789',
    ];

    
    public function model(): string
    {
        return 'Webkul\CartRule\Contracts\CartRuleCoupon';
    }

    
    public function generateCoupons(array $dat, int $cartRuleId): void
    {
        $cartRule = app('Webkul\CartRule\Repositories\CartRuleRepository')->findOrFail($cartRuleId);

        for ($i = 0; $i < $dat['coupon_qty']; $i++) {
            parent::create([
                'cart_rule_id'       => $cartRuleId,
                'code'               => $dat['code_prefix'].$this->getRandomString($dat['code_format'], $dat['code_length']).$dat['code_suffix'],
                'usage_limit'        => $cartRule->uses_per_coupon ?? 0,
                'usage_per_customer' => $cartRule->usage_per_customer ?? 0,
                'is_primary'         => 0,
                'expired_at'         => $cartRule->ends_till ?: null,
            ]);
        }
    }

    
    public function getRandomString(string $format, int $length): string
    {
        $couponCode = '';

        for ($i = 0; $i < $length; $i++) {
            $couponCode .= $this->charset[$format][rand(0, strlen($this->charset[$format]) - 1)];
        }

        return $couponCode;
    }
}
