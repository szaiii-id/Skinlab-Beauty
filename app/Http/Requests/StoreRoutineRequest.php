<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreRoutineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'product_id'          => 'nullable|exists:products,id',
            'custom_product_name' => 'nullable|string|max:255',
            'step_order'          => 'required|integer|min:1',
            'note'                => 'nullable|string|max:100',
            'is_reminder_active'  => 'boolean',
            'repeat_frequency'    => 'required|integer|min:1|max:30',
            'reminder_times'      => 'array', 
            'reminder_times.*'    => 'nullable|date_format:H:i',
            'timezone_input'      => 'nullable|string', // Contoh: 'Asia/Jakarta'
        ];
    }
}