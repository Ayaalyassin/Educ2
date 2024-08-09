<?php

namespace App\Http\Requests;

use App\Traits\GeneralTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SeriesRequest extends FormRequest
{
    use GeneralTrait;

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
//            'title'=>'required|string',
//            'type'=>'required|string',
//            'description'=>'required',
//            'file'=>'required|file',
//            'status'=>'required|boolean',
//            'price'=>'required|numeric|min:0',
           'teaching_method_id'=>'required|integer',
            'series'=>'required|array|min:1',
            'series.*.file'=>'required|file',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->returnValidationError('422', $validator));

    }
}
