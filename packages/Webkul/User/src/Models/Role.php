<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\User\Contracts\Role as RoleContract;
use Webkul\User\Database\Factories\RoleFactory;

class Role extends Model implements RoleContract
{
    use HasFactory;

    
    protected $fillable = [
        'name',
        'description',
        'permission_type',
        'permissions',
    ];

    
    protected $casts = [
        'permissions' => 'array',
    ];

    
    public function admins()
    {
        return $this->hasMany(AdminProxy::modelClass());
    }

    
    protected static function newFactory(): Factory
    {
        return RoleFactory::new();
    }
}
