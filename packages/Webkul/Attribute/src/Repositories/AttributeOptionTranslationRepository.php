<?php

namespace Webkul\Attribute\Repositories;

use Webkul\Core\Eloquent\Repository;

class AttributeOptionTranslationRepository extends Repository
{
    
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeOptionTranslation';
    }
}
