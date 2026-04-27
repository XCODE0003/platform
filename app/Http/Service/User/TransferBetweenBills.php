<?php

declare(strict_types=1);

namespace App\Http\Service\User;

use App\Models\Bill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferBetweenBills
{
    /**
     * Transfer funds between two bills owned by the same user.
     *
     * Rules:
     *  - Both bills must belong to the user
     *  - Both bills must share the same currency
     *  - Both bills must share the same `demo` flag (no demo <-> real)
     *  - Source must have sufficient balance
     *
     * Uses row locks inside a DB transaction to prevent race conditions.
     */
    public function execute(User $user, int $fromBillId, int $toBillId, float $amount): void
    {
        if ($fromBillId === $toBillId) {
            throw ValidationException::withMessages([
                'to_bill_id' => 'Source and destination bills must be different.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }

        DB::transaction(function () use ($user, $fromBillId, $toBillId, $amount): void {
            /** @var Bill|null $from */
            $from = $user->bills()->whereKey($fromBillId)->lockForUpdate()->first();
            /** @var Bill|null $to */
            $to = $user->bills()->whereKey($toBillId)->lockForUpdate()->first();

            if (! $from || ! $to) {
                throw ValidationException::withMessages([
                    'from_bill_id' => 'One of the selected bills is not available.',
                ]);
            }

            if ((int) $from->currency_id !== (int) $to->currency_id) {
                throw ValidationException::withMessages([
                    'to_bill_id' => 'Currency must match between source and destination bills.',
                ]);
            }

            if ((bool) $from->demo !== (bool) $to->demo) {
                throw ValidationException::withMessages([
                    'to_bill_id' => 'Transfers between demo and real bills are not allowed.',
                ]);
            }

            $fromBalance = (float) $from->balance;
            if ($fromBalance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient balance for this transfer.',
                ]);
            }

            $from->balance = number_format($fromBalance - $amount, 8, '.', '');
            $from->save();

            $to->balance = number_format(((float) $to->balance) + $amount, 8, '.', '');
            $to->save();

            Transaction::create([
                'user_id'     => $user->id,
                'currency_id' => $from->currency_id,
                'amount'      => number_format($amount, 8, '.', ''),
                'type'        => 'transfer_out',
                'status'      => 'completed',
            ]);

            Transaction::create([
                'user_id'     => $user->id,
                'currency_id' => $to->currency_id,
                'amount'      => number_format($amount, 8, '.', ''),
                'type'        => 'transfer_in',
                'status'      => 'completed',
            ]);
        });
    }
}
