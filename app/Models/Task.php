<?php

namespace App\Models;

use App\Domain\Comments\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All comments on the task (root comments and replies).
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Root comments on the task (excludes replies).
     *
     * @return HasMany<Comment, $this>
     */
    public function rootComments(): HasMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    /**
     * Filter tasks whose name contains the given term (case-insensitive partial match).
     * No-op when the term is blank.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = is_string($term) ? trim($term) : null;

        if ($term === null || $term === '') {
            return $query;
        }

        return $query->where('name', 'like', '%'.$term.'%');
    }

    /**
     * Filter tasks by exact status. No-op when the status is blank or "all".
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        $status = is_string($status) ? trim($status) : null;

        if ($status === null || $status === '' || $status === 'all') {
            return $query;
        }

        return $query->where('status', $status);
    }
}
