<?php

namespace App\Http\Requests;

use App\Traits\GeneralTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ProfileStudentRequest extends FormRequest
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
            'educational_level'=>'sometimes|string',
            'phone' =>
                'sometimes|regex:/^09\d{8}$/
                |unique:profile_students',
            'name'=>'sometimes|string',

        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->returnValidationError('422', $validator));
    }
}
