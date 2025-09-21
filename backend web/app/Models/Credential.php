<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'certificate_file_path',
        'credential_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the credential.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to order credentials by issue date descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('issue_date', 'desc');
    }

    /**
     * Scope a query to only include non-expired credentials.
     */
    public function scopeValid($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
        });
    }

    /**
     * Scope a query to only include expired credentials.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now());
    }

    /**
     * Check if credential is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if credential is still valid.
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Check if credential has a downloadable file.
     */
    public function hasFile(): bool
    {
        return !empty($this->certificate_file_path);
    }

    /**
     * Check if credential has an external URL.
     */
    public function hasUrl(): bool
    {
        return !empty($this->credential_url);
    }

    /**
     * Get the credential's validity status for display.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->expiry_date) {
            return 'No expiry';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        if ($this->expiry_date->diffInDays() <= 30) {
            return 'Expiring soon';
        }

        return 'Valid';
    }
}