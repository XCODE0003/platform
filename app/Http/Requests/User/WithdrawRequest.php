<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WithdrawRequest extends FormRequest
{
    private ?Bill $resolvedBill = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bill_id' => [
                'required',
                'integer',
                Rule::exists('bills', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'network' => ['nullable', 'string', Rule::in(['USDTTRC20', 'USDTERC20', 'USDTBEP20', 'BTC', 'ETH', 'BNB', ''])],
            'address' => ['required', 'string', 'max:255'],
            'amount'  => ['required', 'numeric', 'min:0.00000001'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $bill = $this->bill();

            if (! $bill) {
                return;
            }

            $amount = (float) $this->input('amount');
            $balance = (float) $bill->balance;

            if ($balance < $amount) {
                $validator->errors()->add('amount', 'Insufficient balance for this withdrawal.');
            }

            $currency = $bill->currency;

            if ($currency && $currency->min_withdraw !== null) {
                $minWithdraw = (float) $currency->min_withdraw;

                if ($amount < $minWithdraw) {
                    $validator->errors()->add('amount', 'Amount is below the minimum withdrawal requirement.');
                }
            }

            if ($currency && $currency->address_regex) {
                $pattern = '/' . trim($currency->address_regex, '/') . '/u';

                if (! preg_match($pattern, $this->input('address'))) {
                    $validator->errors()->add('address', 'Address format is invalid for the selected currency.');
                }
            }
        });
    }

    public function bill(): ?Bill
    {
        if ($this->resolvedBill === null) {
            $this->resolvedBill = Bill::query()
                ->with('currency')
                ->where('user_id', $this->user()?->id)
                ->find($this->input('bill_id'));
        }

        return $this->resolvedBill;
    }
}


