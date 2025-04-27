<?php

namespace Webkul\DataTransfer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\DataTransfer\Contracts\ImportBatch as ImportBatchContract;

class ImportBatch extends Model implements ImportBatchContract
{
    
    public $timestamps = false;

    
    protected $fillable = [
        'state',
        'data',
        'summary',
        'import_id',
    ];

    
    protected $casts = [
        'summary' => 'array',
        'data'    => 'array',
    ];

    
    public function import()
    {
        return $this->belongsTo(ImportProxy::modelClass());
    }
}
