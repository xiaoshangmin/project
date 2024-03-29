<?php

declare(strict_types=1);

namespace App\Http\Request\Pdf;

use App\Http\Request\BaseFormRequest;

class UrlToPdfRequest extends BaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "url" => ["required", "string"],
            "paper" => ['required', "integer"],
            "marginTop" => ['nullable', "integer", "max:20"],
            "marginBottom" => ['nullable', "integer", "max:20"],
            "marginLeft" => ['nullable', "integer", "max:20"],
            "marginRight" => ['nullable', "integer", "max:20"],
        ];
    }

    public function messages(): array
    {
        return [
            "url.required" => "url 必传"
        ];
    }
}
