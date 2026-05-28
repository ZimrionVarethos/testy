<?php
// app/Http/Requests/Api/RegisterRequest.php
namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                  => 'bail|required|string|max:100',
            'email'                 => 'bail|required|email|unique:users,email',
            'phone'                 => 'bail|required|string|max:20',
            'password'              => 'bail|required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                  => 'Nama lengkap wajib diisi.',
            'email.required'                 => 'Email wajib diisi.',
            'email.email'                    => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah terdaftar.',
            'phone.required'                 => 'No. HP wajib diisi.',
            'phone.max'                      => 'No. HP maksimal 20 karakter.',
            'password.required'              => 'Password wajib diisi.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'password.min'          => 'Password minimal 8 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'phone' => is_string($this->phone) ? trim($this->phone) : $this->phone,
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi gagal.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
