@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card mt-3">
    <div class="card-body">
        <h5>Import Summary</h5>
        <p><strong>Imported:</strong> {{ $assessment->imported_rows }}</p>
        <p><strong>Skipped:</strong> {{ $assessment->skipped_rows }}</p>
        <p><strong>Status:</strong> {{ ucfirst($assessment->status) }}</p>
    </div>
</div>
