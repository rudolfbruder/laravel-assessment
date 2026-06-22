<?php

namespace App\Domain\Comments\Models;

use App\Domain\Comments\Database\Factories\CommentFactory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'parent_id',
        'body',
    ];

    /**
     * The author of the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * The root comment this comment replies to, if any.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Whether this comment is a root comment (not a reply).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
