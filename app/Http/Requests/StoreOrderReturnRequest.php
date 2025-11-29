<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth sudah dihandle middleware di route
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string',
            'description' => 'required|string|min:10',
            'solution' => 'required|in:refund,exchange',
            // Validasi file media (Gambar/Video max 50MB)
            'evidence' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:51200', 
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'Evidence (photo/video) is required.',
            'evidence.mimes' => 'Format must be JPG, PNG, or Video (MP4, MOV).',
            'evidence.max' => 'Maximum file size is 50MB.',
            'description.min' => 'Please describe the issue in at least 10 characters.'
        ];
    }
}