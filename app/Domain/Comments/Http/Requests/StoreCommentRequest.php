<?php

namespace App\Domain\Comments\Http\Requests;

use App\Domain\Comments\Models\Comment;
use App\Models\Task;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Any authenticated user may post a comment (route is behind auth:sanctum).
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Trim the body so whitespace-only input is treated as empty.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->input('body'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:comments,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    /** @var Task $task */
                    $task = $this->route('task');
                    $parent = Comment::find($value);

                    if ($parent === null) {
                        return; // exists rule already reports this
                    }

                    if ($parent->task_id !== $task->id) {
                        $fail('The selected parent comment does not belong to this task.');

                        return;
                    }

                    if (! $parent->isRoot()) {
                        $fail('You can only reply to a top-level comment.');
                    }
                },
            ],
        ];
    }
}
