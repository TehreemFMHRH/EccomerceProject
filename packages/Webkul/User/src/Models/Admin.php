<?php

namespace Webkul\User\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Webkul\Admin\Mail\Admin\ResetPasswordNotification;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Database\Factories\AdminFactory;

class Admin extends Authenticatable implements AdminContract
{
    use HasApiTokens, HasFactory, Notifiable;

    
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'api_token',
        'role_id',
        'status',
    ];

    
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

    
    public function image_url()
    {
        if (! $this->image) {
            return;
        }

        return Storage::url($this->image);
    }

    
    public function getImageUrlAttribute()
    {
        return $this->image_url();
    }

    
    public function toArray()
    {
        $array = parent::toArray();

        $array['image_url'] = $this->image_url;

        return $array;
    }

    
    public function role()
    {
        return $this->belongsTo(RoleProxy::modelClass());
    }

    
    public function hasPermission($permission)
    {
        if (
            $this->role->permission_type == 'custom'
            && ! $this->role->permissions
        ) {
            return false;
        }

        return in_array($permission, $this->role->permissions);
    }

    
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    
    protected static function newFactory(): Factory
    {
        return AdminFactory::new();
    }
}
