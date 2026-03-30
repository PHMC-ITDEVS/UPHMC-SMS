<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// use MongoDB\Laravel\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'last_login_at',
        'password_changed_at',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'must_change_password' => 'boolean',
    ];

    protected $appends = ['role_name', 'department_name', 'position_name'];

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtoupper($value);
    }

    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtoupper($value);
    }

    public function getRoleNameAttribute()
    {
        return optional($this->roles->first())->name;
    }

    public function getDepartmentNameAttribute()
    {
        return $this->account?->department?->name;
    }

    public function getPositionNameAttribute()
    {
        return $this->account?->position?->name;
    }

    public function getLastLoginAtAttribute($value): ?string
    {
        return $value
            ? Carbon::parse($value)->timezone(config('app.timezone'))->format('d M Y H:i')
            : null;
    }

    public function getPasswordChangedAtAttribute($value): ?string
    {
        return $value
            ? Carbon::parse($value)->timezone(config('app.timezone'))->format('d M Y H:i')
            : null;
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'user_id', 'id');
    }
}
