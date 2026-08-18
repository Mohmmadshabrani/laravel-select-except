<?php

namespace Shabrani\SelectExcept\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shabrani\SelectExcept\HasSelectExcept;

class Post extends Model
{
    use HasSelectExcept;

    protected $guarded = [];

    /**
     * @var list<string>
     */
    protected array $selectExcept = [
        'body',
        'html',
        'search_index',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
