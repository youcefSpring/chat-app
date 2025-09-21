<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publication extends Model
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
        'authors',
        'journal',
        'conference',
        'year',
        'abstract',
        'publication_file_path',
        'external_link',
    ];

    /**
     * Get the user that owns the publication.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to order publications by year descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('year', 'desc');
    }

    /**
     * Scope a query to filter publications by year.
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope a query to filter publications by journal.
     */
    public function scopeByJournal($query, $journal)
    {
        return $query->where('journal', 'like', "%{$journal}%");
    }

    /**
     * Get the formatted citation for the publication.
     */
    public function getCitationAttribute(): string
    {
        $citation = $this->authors . ' (' . $this->year . '). ' . $this->title . '.';

        if ($this->journal) {
            $citation .= ' ' . $this->journal . '.';
        } elseif ($this->conference) {
            $citation .= ' In ' . $this->conference . '.';
        }

        return $citation;
    }

    /**
     * Check if publication has a downloadable file.
     */
    public function hasFile(): bool
    {
        return !empty($this->publication_file_path);
    }

    /**
     * Check if publication has an external link.
     */
    public function hasExternalLink(): bool
    {
        return !empty($this->external_link);
    }
}