<?php
// ============================================================
// StoreVehicleRequest.php
// ============================================================
namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:100',
            'brand'         => 'required|string|max:50',
            'model'         => 'required|string|max:50',
            'year'          => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'plate_number'  => 'required|string|max:20|unique:vehicles,plate_number',
            'type'          => 'required|in:MPV,SUV,Van,Sedan,Minibus',
            'capacity'      => 'required|integer|min:2|max:20',
            'price_per_day' => 'required|integer|min:100000',
            'features_raw'  => 'nullable|string',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
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
