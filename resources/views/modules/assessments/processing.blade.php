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

                    <h4 class="fw-bold mb-2" id="stage-title">{{ __('Step 1: Staging Data') }}</h4>
                    <p class="text-muted mb-4" id="stage-subtitle">
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

    {{-- Ingestion Summary Modal (shown on completion, before redirect) --}}
    <div class="modal fade" id="ingestionSummaryModal" tabindex="-1" aria-labelledby="ingestionSummaryModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="ingestionSummaryModalLabel">
                        <i class="ri-check-double-line me-1"></i> {{ __('Ingestion Complete') }}
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">{{ __('Here is the breakdown for this upload:') }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="ri-add-circle-line text-success me-1"></i> {{ __('Records added') }}</span>
                            <span class="badge bg-success rounded-pill" id="summary-inserted">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="ri-refresh-line text-primary me-1"></i> {{ __('Records updated') }}</span>
                            <span class="badge bg-primary rounded-pill" id="summary-updated">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="ri-file-copy-line text-warning me-1"></i> {{ __('Duplicates skipped (no change)') }}</span>
                            <span class="badge bg-warning text-dark rounded-pill" id="summary-duplicate">0</span>
                        </li>
                        <li class="list-group-item d-none" id="summary-cross-row">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="ri-alert-line text-warning me-1"></i> {{ __('Cross-template duplicates updated') }}</span>
                                <span class="badge bg-warning text-dark rounded-pill" id="summary-cross">0</span>
                            </div>
                            <div class="small text-muted mt-1">
                                {{ __('These rows already existed in another assessment using the same template — the original assessment\'s row was updated with the new values.') }}
                                <a href="#" id="summary-cross-toggle" class="ms-1">{{ __('Show details') }}</a>
                            </div>
                            <ul class="list-unstyled small mt-2 d-none" id="summary-cross-list"></ul>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="ri-error-warning-line text-danger me-1"></i> {{ __('Skipped (validation / missing key)') }}</span>
                            <span class="badge bg-danger rounded-pill" id="summary-skipped">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center fw-bold">
                            <span>{{ __('Total records in table') }}</span>
                            <span class="badge bg-dark rounded-pill" id="summary-imported">0</span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('assessments.show', $assessment->id) }}" class="btn btn-primary">
                        {{ __('Continue') }} <i class="ri-arrow-right-line ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        let pollTimer = null;
        let summaryShown = false;

        let crossDetailsLoaded = false;

        function showSummaryModal(data) {
            if (summaryShown) return;
            summaryShown = true;
            document.getElementById('summary-inserted').innerText  = (data.inserted_rows  || 0).toLocaleString();
            document.getElementById('summary-updated').innerText   = (data.updated_rows   || 0).toLocaleString();
            document.getElementById('summary-duplicate').innerText = (data.duplicate_rows || 0).toLocaleString();
            document.getElementById('summary-skipped').innerText   = (data.skipped_rows   || 0).toLocaleString();
            document.getElementById('summary-imported').innerText  = (data.imported_rows  || 0).toLocaleString();

            const crossCount = (data.cross_template_updates || 0);
            const crossRow   = document.getElementById('summary-cross-row');
            document.getElementById('summary-cross').innerText = crossCount.toLocaleString();
            if (crossCount > 0) {
                crossRow.classList.remove('d-none');
            }

            const modalEl = document.getElementById('ingestionSummaryModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        document.addEventListener('click', function (e) {
            if (e.target && e.target.id === 'summary-cross-toggle') {
                e.preventDefault();
                const list = document.getElementById('summary-cross-list');
                const link = e.target;
                if (list.classList.contains('d-none')) {
                    if (!crossDetailsLoaded) {
                        fetch('{{ route('assessments.crossUpdates', $assessment->id) }}')
                            .then(r => r.json())
                            .then(payload => {
                                const items = (payload && payload.details) || [];
                                if (items.length === 0) {
                                    list.innerHTML = '<li class="text-muted">{{ __('No details available.') }}</li>';
                                } else {
                                    list.innerHTML = items.map(d => {
                                        const targetLabel = d.target_assessment_label || ('#' + (d.target_assessment_id || '?'));
                                        const ec = d.target_entry_counter ? (' (entry #' + d.target_entry_counter + ')') : '';
                                        return '<li>'
                                            + '<i class="ri-arrow-right-line me-1"></i>'
                                            + '{{ __('Row') }} ' + (d.row_index || '?')
                                            + ' &rarr; {{ __('updated assessment') }} ' + targetLabel + ec
                                            + '</li>';
                                    }).join('');
                                }
                                crossDetailsLoaded = true;
                            })
                            .catch(() => {
                                list.innerHTML = '<li class="text-danger">{{ __('Failed to load details.') }}</li>';
                            });
                    }
                    list.classList.remove('d-none');
                    link.innerText = '{{ __('Hide details') }}';
                } else {
                    list.classList.add('d-none');
                    link.innerText = '{{ __('Show details') }}';
                }
            }
        });

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

                    // Swap the stage title/subtitle when we're in the commit phase
                    if (data.status === 'committing' || (data.status === 'completed' && data.finalized_rows > 0)) {
                        const title = document.getElementById('stage-title');
                        const subtitle = document.getElementById('stage-subtitle');
                        if (title)    title.innerText = 'Step 2: Committing to Master Data';
                        if (subtitle) subtitle.innerText = 'Merging records — inserting new entries and updating changed ones.';
                    }

                    if (data.status === 'failed') {
                        statusBadge.innerText = 'Failed';
                        statusBadge.className = 'badge bg-danger text-white';
                        document.querySelector('.spinner-border').parentElement.classList.add('d-none');
                        document.getElementById('error-container').classList.remove('d-none');
                        progressBar.classList.remove('bg-primary');
                        progressBar.classList.add('bg-danger');
                        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                        return;
                    }

                    if (data.status !== 'processing') {
                        statusBadge.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                        statusBadge.className = 'badge bg-success text-white';

                        if (data.status === 'completed') {
                            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                            showSummaryModal(data);
                            return;
                        }

                        if (data.status === 'parsed') {
                            window.location.href = '{{ route('assessments.show', $assessment->id) }}';
                        }
                    }
                })
                .catch(error => console.error('Error fetching progress:', error));
        }

        pollTimer = setInterval(updateProgress, 2000);
        updateProgress();
    </script>
@endsection
