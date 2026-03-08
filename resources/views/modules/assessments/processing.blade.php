@php
App::setLocale(session('lang'));
@endphp
@extends('layouts.master')
@section('title')
    {{ __('Processing Import') }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.assessments')
        @endslot
        @slot('title')
            {{ __('Processing') }}
        @endslot
    @endcomponent

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mt-4">
                <div class="card-body text-center py-5">

                    {{-- Spinner --}}
                    <div class="mb-4">
                        <div class="spinner-border text-primary" role="status" style="width: 3.5rem; height: 3.5rem;">
                            <span class="visually-hidden">Processing…</span>
                        </div>
                    </div>

                    <h4 class="fw-bold mb-2">{{ __('Step 1: Staging Data') }}</h4>
                    <p class="text-muted mb-4">
                        {{ __('We are reading your file and preparing it for review.') }}
                    </p>

                    {{-- Progress Bar --}}
                    <div class="mb-4 px-4">
                        <div class="progress animated-progress custom-progress progress-label" style="height: 25px;">
                            <div id="import-progress-bar" class="progress-bar bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                <div class="label">0%</div>
                            </div>
                        </div>
                        <div class="mt-2 text-muted small">
                            {{ __('Processed') }} <span id="processed-count">0</span> {{ __('of') }} <span id="total-count">...</span> {{ __('rows') }}
                        </div>
                    </div>

                    <p class="text-muted small mb-4">
                        {{ __('Assessment ID:') }} <strong>#{{ $assessment->id }}</strong> &nbsp;|&nbsp;
                        {{ __('Status:') }} <span id="current-status" class="badge bg-warning text-dark">{{ __('Processing') }}</span>
                    </p>

                    {{-- Error Message (Hidden by default) --}}
                    <div id="error-container" class="alert alert-danger d-none mb-4 mx-4">
                        <i class="ri-error-warning-line me-1"></i>
                        <strong>{{ __('Import Failed:') }}</strong> {{ __('Something went wrong while processing your file.') }} 
                        {{ __('Please ensure the file is valid and try again.') }}
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        {{-- Refresh button to check if done --}}
                        <a href="{{ route('assessments.show', $assessment->id) }}"
                           class="btn btn-primary">
                            <i class="ri-refresh-line me-1"></i> {{ __('Refresh') }}
                        </a>
                        <a href="{{ route('assessments.index') }}" class="btn btn-outline-secondary">
                            {{ __('← Back to Assessments') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        function updateProgress() {
            fetch('{{ route('assessments.progress', $assessment->id) }}')
                .then(response => response.json())
                .then(data => {
                    const progressBar = document.getElementById('import-progress-bar');
                    const label = progressBar.querySelector('.label');
                    const processedCount = document.getElementById('processed-count');
                    const totalCount = document.getElementById('total-count');
                    const statusBadge = document.getElementById('current-status');

                    const percent = data.percentage || 0;
                    progressBar.style.width = percent + '%';
                    progressBar.setAttribute('aria-valuenow', percent);
                    label.innerText = percent + '%';

                    processedCount.innerText = data.processed_rows.toLocaleString();
                    totalCount.innerText = (data.total_rows > 0) ? data.total_rows.toLocaleString() : '...';
                    
                    if (data.status === 'failed') {
                        statusBadge.innerText = 'Failed';
                        statusBadge.className = 'badge bg-danger text-white';
                        document.querySelector('.spinner-border').parentElement.classList.add('d-none');
                        document.getElementById('error-container').classList.remove('d-none');
                        progressBar.classList.remove('bg-primary');
                        progressBar.classList.add('bg-danger');
                        return; // Stop further updates or redirects
                    }

                    if (data.status !== 'processing') {
                        statusBadge.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                        statusBadge.className = 'badge bg-success text-white';
                        
                        if (data.status === 'parsed' || data.status === 'completed') {
                            window.location.href = '{{ route('assessments.show', $assessment->id) }}';
                        }
                    }
                })
                .catch(error => console.error('Error fetching progress:', error));
        }

        setInterval(updateProgress, 2000);
        updateProgress();
    </script>
@endsection
