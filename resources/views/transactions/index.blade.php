@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">All Transactions</div>

                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction Type</th>
                                    <th>Amount</th>
                                    <th>Fee</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->date->format("j F, Y") }}</td>
                                        <td>{{ $transaction->transaction_type }}</td>
                                        <td>{{ number_format($transaction->amount) }}</td>
                                        <td>{{ number_format($transaction->fee) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">Current Balance</div>

                    <div class="card-body">
                        <p>Your current balance is: Tk. {{ number_format(auth()->user()->balance) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
