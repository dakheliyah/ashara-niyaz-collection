<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DonationController extends Controller
{
    /**
     * Reconcile a donation.
     */
    public function reconcile(Request $request, Donation $donation)
    {
        $remainingAmount = $donation->amount - ($donation->reconciled_amount ?? 0);

        $validator = Validator::make($request->all(), [
            'reconciled_amount' => 'required|numeric|min:0.01|max:'.$remainingAmount,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reconciledAmount = (float) $request->input('reconciled_amount');
        $donation->reconciled_amount = ($donation->reconciled_amount ?? 0) + $reconciledAmount;

        if ($donation->reconciled_amount >= $donation->amount) {
            $donation->reconciliation_status = 'fully_reconciled';
        } else {
            $donation->reconciliation_status = 'partially_reconciled';
        }

        $donation->reconciled_at = now();
        $donation->reconciled_by = Auth::id();
        $donation->save();

        return response()->json($donation);
    }
}
