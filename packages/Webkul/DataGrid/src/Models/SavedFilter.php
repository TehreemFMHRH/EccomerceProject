<?php

namespace Webkul\DataGrid\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\DataGrid\Contracts\SavedFilter as SavedFilterContract;

class SavedFilter extends Model implements SavedFilterContract
{
    use HasFactory;

    
    protected $table = 'datagrid_saved_filters';

    
    protected $fillable = [
        'user_id',
        'src',
        'name',
        'applied',
    ];

    
    protected $casts = [
        'applied' => 'json',
    ];
}
