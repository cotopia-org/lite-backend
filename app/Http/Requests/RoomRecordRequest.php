<?php

namespace App\Http\Requests;

use App\Enums\RoomRecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'is_audio' => 'required|boolean',
            'status'   => ['required', Rule::in(get_enum_values(RoomRecordStatus::cases()))],
            'is_video' => 'required|boolean',
            'url'      => 'nullable|string',
        ];
    }
}
