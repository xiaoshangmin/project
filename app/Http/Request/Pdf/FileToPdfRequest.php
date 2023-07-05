<?php

declare(strict_types=1);

namespace App\Http\Request\Pdf;

use App\Http\Request\BaseFormRequest;

class FileToPdfRequest extends BaseFormRequest
{

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "file" => ["required", "file"],
        ];
    }

    public function messages(): array
    {
        return [
            "url.required" => "请上传转换的文件"
        ];
    }
}
