<?php

declare(strict_types=1);

namespace App\Http\Requests\Trade;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'pair_id' => ['required', 'integer', 'exists:pairs,id'],
            'bill_id' => ['required', 'integer', 'exists:bills,id'],
            'side' => ['required', 'in:buy,sell'],
            'type' => ['required', 'in:market,limit,stop'],
            'tif' => ['nullable', 'in:GTC,IOC,FOK'],
            'post_only' => ['nullable', 'boolean'],

            'price' => ['nullable', 'numeric', 'gt:0'],
            'stop_price' => ['nullable', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'total' => ['nullable', 'numeric', 'gt:0'],

            'stops_mode' => ['nullable', 'in:none,pips,price'],
            'take_profit' => ['nullable', 'numeric', 'gt:0'],
            'stop_loss' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}


