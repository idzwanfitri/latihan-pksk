<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $attempt_id
 * @property int $question_id
 * @property int $position
 * @property int|null $selected_option_id
 * @property bool $is_flagged
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Attempt $attempt
 * @property-read Question $question
 * @property-read Option|null $selectedOption
 */
#[Fillable(['question_id', 'position', 'selected_option_id', 'is_flagged'])]
class AttemptQuestion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'selected_option_id' => 'integer',
            'is_flagged' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Attempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(Attempt::class);
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<Option, $this>
     */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'selected_option_id');
    }

    public function isAnswered(): bool
    {
        return $this->selected_option_id !== null;
    }
}
