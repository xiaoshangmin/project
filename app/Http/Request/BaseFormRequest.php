<?php
declare(strict_types=1);

namespace App\Http\Request;

use Hyperf\Validation\Request\FormRequest;

class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

}