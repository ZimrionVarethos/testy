<?php
// ============================================================
// StoreBookingRequest.php
// ============================================================
namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'      => 'required|string|exists:vehicles,_id',
            'start_date'      => 'required|date|after_or_equal:today',
            'end_date'        => 'required|date|after:start_date',
            'pickup_address'  => 'required|string|max:255',
            'pickup_lat'      => 'nullable|numeric',
            'pickup_lng'      => 'nullable|numeric',
            'dropoff_address' => 'nullable|string|max:255',
            'dropoff_lat'     => 'nullable|numeric',
            'dropoff_lng'     => 'nullable|numeric',
            'notes'           => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'end_date.after'            => 'Tanggal selesai harus setelah tanggal mulai.',
            'vehicle_id.exists'         => 'Kendaraan tidak ditemukan.',
        ];
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
