<?php

namespace App\Http\Requests;

use App\Traits\GeneralTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileTeacherRequest extends FormRequest
{
    use GeneralTrait;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'certificate' => 'sometimes|image|mimes:jpeg,jpg,png,gif',
            'description' => 'sometimes|string',
            'jurisdiction' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpeg,jpg,png,gif',
            'address'=>'sometimes|string',
            'governorate'=>'sometimes|string',
            'domains'=>'sometimes|array',
            'domains.*.id'=>'sometimes|integer',
            'domains.*.type'=>'sometimes|string',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->returnValidationError('422', $validator));
    }
}
