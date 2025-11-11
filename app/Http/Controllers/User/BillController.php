<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBillRequest;
use App\Http\Service\User\CreateBill;
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
}
