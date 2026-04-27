<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'from_bill_id' => ['required', 'integer', 'different:to_bill_id'],
            'to_bill_id'   => ['required', 'integer', 'different:from_bill_id'],
            'amount'       => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_bill_id.different' => 'Source and destination bills must be different.',
            'to_bill_id.different'   => 'Source and destination bills must be different.',
            'amount.gt'              => 'Amount must be greater than zero.',
        ];
    }
}
