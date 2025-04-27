<?php

namespace Webkul\Core\Enums;

enum CurrencyPositionEnum: string
{
    
    case LEFT = 'left';

    
    case LEFT_WITH_SPACE = 'left_with_space';

    
    case RIGHT = 'right';

    
    case RIGHT_WITH_SPACE = 'right_with_space';

    
    public static function options()
    {
        return [
            CurrencyPositionEnum::LEFT->value             => trans('core::app.currency-position.options.left'),
            CurrencyPositionEnum::LEFT_WITH_SPACE->value  => trans('core::app.currency-position.options.left-with-space'),
            CurrencyPositionEnum::RIGHT->value            => trans('core::app.currency-position.options.right'),
            CurrencyPositionEnum::RIGHT_WITH_SPACE->value => trans('core::app.currency-position.options.right-with-space'),
        ];
    }
}
