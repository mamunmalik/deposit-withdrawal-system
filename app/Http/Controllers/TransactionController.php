<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Http\Requests\TransactionRequest;

class TransactionController extends Controller
{
    /**
     * Show all the transactions and current balance.
     */
    public function index()
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Show all the deposited transactions.
     */
    public function get_all_deposit()
    {
        $deposits = Transaction::where('user_id', Auth::id())
            ->where('transaction_type', TransactionType::Deposit->name)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('deposits.index', compact('deposits'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store_deposit(TransactionRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();

            $data['transaction_type'] = TransactionType::Deposit->name;
            $data['date'] = date("Y-m-d");

            $transaction = Transaction::create($data);
            if ($transaction) {
                $user = User::find(Auth()->id());
                $user->balance += $transaction->amount;
                $user->save();
            }

            DB::commit();

            // Redirect back or to any other route as needed
            return redirect()->back()->with('success', 'Amount stored successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Something wrong!');
        }
    }

    /**
     * Show all the withdrawal transactions
     */
    public function get_all_withdrawal()
    {
        $withdrawals = Transaction::where('user_id', Auth::id())
            ->where('transaction_type', TransactionType::Withdrawal->name)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('withdrawals.index', compact('withdrawals'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function store_withdrawal(TransactionRequest $request)
    {
        DB::beginTransaction();
        try {
            $fee = 0;

            // Retrieve the user
            $user = User::findOrFail($request->user_id);
            $amount = $request->amount;

            // Get the withdrawal rate based on user account type
            $withdrawalRate = Config::get('withdrawals.rates.' . $user->account_type);

            // Check if it's Friday
            $today = Carbon::now();
            $isFriday = $today->isFriday();

            // Check if it's the first 5K withdrawal for the month
            $monthStart = $today->startOfMonth();
            $monthEnd = $today->endOfMonth();
            $totalWithdrawalsThisMonth = $user->transactions()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('amount', '>', 0)
                ->sum('amount');

            // Apply free withdrawal conditions for Individual accounts
            if ($user->account_type === AccountType::Individual->value) {
                // First 5K withdrawal each month is free
                if ($totalWithdrawalsThisMonth <= 5000 && $amount <= 5000) {
                    $fee += ($amount - 5000) * $withdrawalRate;
                    $amount = 5000;
                } elseif ($totalWithdrawalsThisMonth >= 5000) {
                    $fee += $amount * $withdrawalRate;
                    $amount = 0;
                }
                // First 1K withdrawal per transaction is free
                if ($amount > 1000) {
                    $fee += ($amount - 1000) * $withdrawalRate;
                    $amount = 1000;
                }
                // Friday withdrawal is free
                if ($isFriday) {
                    $fee = 0;
                }
            }

            // Check if the user has enough balance to cover the withdrawal
            $totalAmount = $amount + $fee;
            if ($user->balance < $totalAmount) {
                return redirect()->back()->with('error', 'Insufficient balance.');
            }

            // Deduct the amount from the user's balance
            $user->balance -= $totalAmount;
            $user->save();

            // Record the withdrawal transaction
            Transaction::create([
                'user_id' => $user->id,
                'transaction_type' => TransactionType::Withdrawal->name,
                'amount' => $request->amount,
                'fee' => $fee,
                'date' => date('Y-m-d')
            ]);

            DB::commit();

            // Redirect back or to any other route as needed
            return redirect()->back()->with('success', 'Amount withdrawal successfully.');
        } catch (\Throwable $th) {
            dd($th->getMessage());
            DB::rollBack();

            return redirect()->back()->with('error', 'Something wrong!');
        }
    }
}
