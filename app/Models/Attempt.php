<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Database\Factories\AttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $started_at
 * @property Carbon $expires_at
 * @property Carbon|null $submitted_at
 * @property AttemptStatus $status
 * @property float|null $score_a
 * @property float|null $score_b
 * @property float|null $score_total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, AttemptQuestion> $attemptQuestions
 */
#[Fillable(['started_at', 'expires_at', 'submitted_at', 'status', 'score_a', 'score_b', 'score_total'])]
class Attempt extends Model
{
    /** @use HasFactory<AttemptFactory> */
    use HasFactory;

    /**
     * Scores are cast as float, not `decimal:2`. The `decimal:` cast returns a
     * string, which breaks arithmetic typing and equality assertions; rounding
     * is done explicitly in ScoringService instead.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'status' => AttemptStatus::class,
            'score_a' => 'float',
            'score_b' => 'float',
            'score_total' => 'float',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AttemptQuestion, $this>
     */
    public function attemptQuestions(): HasMany
    {
        return $this->hasMany(AttemptQuestion::class);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->expires_at);
    }

    public function isActive(): bool
    {
        return $this->status === AttemptStatus::InProgress && ! $this->isExpired();
    }

    public function isFinalised(): bool
    {
        return $this->status !== AttemptStatus::InProgress;
    }

    /**
     * Rounded up so a part-elapsed second still shows as remaining; truncating
     * would drop the clock to 89:59 the instant an attempt is created.
     */
    public function secondsRemaining(): int
    {
        return max(0, (int) ceil(now()->diffInSeconds($this->expires_at, false)));
    }
}
