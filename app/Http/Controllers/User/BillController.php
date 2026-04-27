<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBillRequest;
use App\Http\Requests\TransferBillRequest;
use App\Http\Service\User\CreateBill;
use App\Http\Service\User\TransferBetweenBills;
use App\Models\Currency;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillController extends Controller
{
    public function create(CreateBillRequest $request)
    {
        $user = $request->user();
        $bill = (new CreateBill())->createUsdBill($user, $request->bill_name, $request->is_demo);

        return redirect()->back()->with('success', 'Bill created successfully');
    }

    public function get(){
        $user = auth()->user();
        $bills = $user->bills()->with('currency')->get();

        return response()->json([
            'bills' => $bills,
        ]);
    }

    public function transfer(TransferBillRequest $request, TransferBetweenBills $service)
    {
        $user = $request->user();

        $service->execute(
            $user,
            (int) $request->input('from_bill_id'),
            (int) $request->input('to_bill_id'),
            (float) $request->input('amount'),
        );

        return redirect()->back()->with('success', 'Transfer completed successfully');
    }
}
