<?php

namespace Webkul\DataGrid\Enums;

enum FilterTypeEnum: string
{
    
    case DROPDOWN = 'dropdown';

    
    case DATE_RANGE = 'date_range';

    
    case DATETIME_RANGE = 'datetime_range';
}
