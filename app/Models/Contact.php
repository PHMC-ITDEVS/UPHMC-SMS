<?php

namespace App\Models;

use App\Models\Concerns\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Library\Helper;

class Contact extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'name',
        'phone_number',
        'email',
        'notes',
        'created_by',
    ];

    protected $appends = ['avatar'];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function creator(): BelongsTo
    {
        // only get the creator's name in the account relationship to optimize query performance and combine the first_name, middle_name, and last_name into a single field called name
        return $this->belongsTo(User::class, 'created_by')
            ->leftJoin('accounts', 'users.id', '=', 'accounts.user_id')
            ->select('users.id', DB::raw("
                TRIM(
                    REGEXP_REPLACE(
                        CONCAT(
                            COALESCE(accounts.first_name, ''), ' ',
                            COALESCE(accounts.middle_name, ''), ' ',
                            COALESCE(accounts.last_name, '')
                        ),
                        '\s+',
                        ' ',
                        'g'
                    )
                ) AS name
            "));
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            ContactGroup::class,
            'contact_group_members',
            'contact_id',
            'contact_group_id'
        )->withPivot('added_at');
    }

    public function smsRecipients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SmsRecipient::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Filter by partial name or phone number.
     */
    public function scopeSearch($query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
              ->orWhere('phone_number', 'ilike', "%{$term}%");
        });
    }

    public function getAvatarAttribute()
    {
        return "image/contact/" . Helper::encrypt($this->id);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    
}
