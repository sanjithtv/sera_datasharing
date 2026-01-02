@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">
        Manual Assessment Entry — {{ $template->licensee->name_en }} / {{ $template->subfolder->name_en }} (v{{ $template->version }})
    </h4>

    <form action="{{ route('assessments.form.submit', $assessment->id) }}" method="POST">
        @csrf
        <div class="row">
            @foreach($keys as $key)
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ $key->desc_en }} @if($key->mandatory) <span class="text-danger">*</span> @endif</label>

                    @switch($key->type)
                        @case('text')
                            <input type="text" name="{{ $key->short_code }}" class="form-control" value="{{ old($key->short_code) }}">
                            @break

                        @case('number')
                            <input type="number" step="any" name="{{ $key->short_code }}" class="form-control" value="{{ old($key->short_code) }}">
                            @break

                        @case('number_percentage')
                            <input type="text" name="{{ $key->short_code }}" class="form-control" placeholder="e.g. 75%" value="{{ old($key->short_code) }}">
                            @break

                        @default
                            <input type="text" name="{{ $key->short_code }}" class="form-control" value="{{ old($key->short_code) }}">
                    @endswitch

                    @error($key->short_code)
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn btn-success">Save Assessment</button>
            <a href="{{ route('assessments.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
