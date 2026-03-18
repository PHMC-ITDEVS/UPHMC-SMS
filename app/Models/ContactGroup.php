<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactGroup extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ContactGroup $group) {
            $group->contacts()->detach();
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            'App\\Models\\Contact'::class,
            'contact_group_members',
            'contact_group_id',
            'contact_id'
        )->withPivot('added_at');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getMemberCountAttribute(): int
    {
        return $this->contacts()->count();
    }
}
