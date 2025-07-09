@extends('admin.layouts.master')
@section('title', 'WhatsApp Messaging')

@section('content')
<section>
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">Messaging /</span> WhatsApp</h4>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Conversation List --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">WhatsApp Conversations</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendMessageModal">Send Message</button>
        </div>
        <div class="card-body">
            @if (isset($conversations))
                <ul class="list-group">
                    @forelse ($conversations as $number)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('whatsapp.show', $number) }}">{{ $number }}</a>
                            <i class="bx bx-chevron-right"></i>
                        </li>
                    @empty
                        <li class="list-group-item">No conversations found.</li>
                    @endforelse
                </ul>
            @endif
        </div>
    </div>

    {{-- Specific Conversation --}}
    @if (isset($messages) && isset($number))
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Conversation with {{ $number }}</h5>
                <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#sendMessageModal">Send Message</button>
            </div>
            <div class="card-body">
                @foreach ($messages as $message)
                    <div class="mb-3">
                        <strong>{{ $message->direction == 'outbound' ? 'You' : $message->from }}:</strong>
                        <p class="mb-1">{{ $message->body }}</p>
                        <small class="text-muted">{{ $message->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                    <hr>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Send Message Modal --}}
    <div class="modal fade" id="sendMessageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content p-3 p-md-4">
                <div class="modal-header">
                    <h5 class="modal-title">Send WhatsApp Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('whatsapp.send') }}" method="POST">
                    @csrf
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label for="to" class="form-label">Recipient Number (e.g., +1234567890)</label>
                            <input type="text" name="to" id="to" class="form-control" required value="{{ $number ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label">Message</label>
                            <textarea name="message" id="message" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Send</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
