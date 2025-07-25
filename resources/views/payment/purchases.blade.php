@extends('layouts.app')

@section('title', 'My Purchases')

@section('content')
    <div class="container">
        <h1>My Purchases</h1>

        @if ($purchases->isEmpty())
            <p>You haven't made any purchases yet.</p>
            <a href="{{ route('videos.index') }}" class="btn btn-primary">Browse Videos</a>
        @else
            <div class="row">
                @foreach ($purchases as $purchase)
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">{{ $purchase->video->title }}</h5>
                                <p class="card-text"><strong>Purchase Date:</strong> {{ $purchase->created_at->format('M d, Y H:i') }}</p>
                                <p class="card-text"><strong>Amount:</strong> ${{ number_format($purchase->amount, 2) }}</p>
                                <p class="card-text"><strong>Status:</strong> {{ ucfirst($purchase->purchase_status) }}</p>
                                <p class="card-text"><strong>Telegram Username:</strong> @{{ $purchase->telegram_username }}</p>
                                <p class="card-text"><strong>UUID:</strong> {{ $purchase->purchase_uuid }}</p>
                                <a href="{{ route('purchase.view', $purchase->purchase_uuid) }}" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
@endsection
