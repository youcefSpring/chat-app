<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get all of the projects that are assigned this tag.
     */
    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'taggable');
    }

    /**
     * Get all of the blog posts that are assigned this tag.
     */
    public function blogPosts(): MorphToMany
    {
        return $this->morphedByMany(BlogPost::class, 'taggable');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name')) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Scope a query to find tags by name or slug.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%")
                    ->orWhere('slug', 'like', "%{$name}%");
    }

    /**
     * Get all taggable models for this tag.
     */
    public function getAllTaggables()
    {
        return collect([
            'projects' => $this->projects,
            'blog_posts' => $this->blogPosts,
        ])->filter(fn($collection) => $collection->isNotEmpty());
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}