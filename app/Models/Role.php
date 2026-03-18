<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Illuminate\Support\Carbon;
use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    use HasAuditTrail;

    public $guarded = [];

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }
}
