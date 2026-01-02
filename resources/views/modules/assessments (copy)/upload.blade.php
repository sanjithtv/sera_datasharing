<form action="{{ route('forms.assessments.upload') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label>Licensee</label>
        <select name="licensee_id" class="form-select" required>
            @foreach($licensees as $licensee)
                <option value="{{ $licensee->id }}">{{ $licensee->name_en }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Template</label>
        <select name="licensee_template_id" class="form-select" required>
            @foreach($templates as $template)
                <option value="{{ $template->id }}">{{ $template->name_en }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Assessment Date</label>
        <input type="date" name="assessment_date" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-select">
            <option value="draft">Draft</option>
            <option value="final">Final</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Upload Excel/CSV</label>
        <input type="file" name="file" class="form-control" required>
    </div>

    <button class="btn btn-primary">Upload</button>
</form>
