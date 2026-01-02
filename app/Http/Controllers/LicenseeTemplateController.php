<?php

namespace App\Http\Controllers;

use App\Models\LicenseeTemplate;
use App\Models\LicenseeTemplateKey;
use App\Models\Licensee;
use App\Models\LicenseeSubfolder;
use App\Models\LicenseeTemplateSheet;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LicenseeTemplateController extends Controller
{
    /**
     * Display all templates
     */
    public function index()
    {
        $templates = LicenseeTemplate::with(['licensee', 'subfolder'])
        ->withCount('keys') // 👈 Adds count of related LicenseeTemplateKey records
        ->whereIn('status',['active','inactive'])
        ->orderBy('id')
        ->paginate(100);

        return view('modules.licensee_templates.index', compact('templates'));
    }

    /**
     * Show form to create new Licensee Template
     */
    public function create()
    {
        $licensees = Licensee::pluck('name_en', 'id');
        $subfolders = LicenseeSubfolder::pluck('name_en', 'id');
        $departments = Department::pluck('name_en', 'id');
        return view('modules.licensee_templates.create', compact('licensees', 'subfolders','departments'));
    }

    /**
     * Store new Licensee Template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'licensee_id' => 'required|exists:sr_licensees,id',
            'subfolder_id' => 'required|exists:sr_subfolders,id',
            'version' => 'required|string|max:50',
            'department_id' => 'required|exists:sr_departments,id',
            'sheet_name' => 'required|string|max:50',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $template = LicenseeTemplate::create($validated);
        if($template){
            $sheet_data = [
                'template_id' => $template->id,
                'sheet_name' => $template->sheet_name,
                'status' => 1,
            ];
            LicenseeTemplateSheet::create($sheet_data);
        }

        return redirect()->route('forms.licensee_templates.edit', $template->id)
            ->with('success', 'Template created successfully. You can now add keys.');
    }

    /**
     * Edit existing Licensee Template and its dynamic keys
     */
    public function edit(LicenseeTemplate $licenseeTemplate)
    {
        $licensees = Licensee::pluck('name_en', 'id');
        $subfolders = LicenseeSubfolder::pluck('name_en', 'id');
        $departments = Department::pluck('name_en', 'id');
        $templateKeys = $licenseeTemplate->keys;
        $sheets = $licenseeTemplate->sheets()
        ->where('status', '1') // optional
        ->pluck('sheet_name', 'id');


        return view('modules.licensee_templates.edit', compact('licenseeTemplate', 'licensees', 'subfolders', 'templateKeys','departments','sheets'));
    }

    /**
     * Update Licensee Template
     */
    public function update(Request $request)
    {
        $licenseeTemplate_id = $request->licenseeTemplate_id;
        $licenseeTemplate = LicenseeTemplate::find($licenseeTemplate_id);
        $validated = $request->validate([
            'licensee_id' => 'required|exists:sr_licensees,id',
            'subfolder_id' => 'required|exists:sr_subfolders,id',
            'version' => 'required|string|max:50',
            'department_id' => 'required|exists:sr_departments,id',
            'sheet_name' => 'required|string|max:50',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $licenseeTemplate->update($validated);

        return back()->with('success', 'Template updated successfully.');
    }

    /**
     * Add new dynamic key (AJAX or form)
     */
    public function storeKey(Request $request, $templateId)
    {
        $validated = $request->validate([
            'licensee_id' => 'required|exists:sr_licensees,id',
            'licensee_template_id' => 'required|exists:sr_licensee_templates,id',
            'short_code' => 'required|string|max:100',
            'desc_en' => 'required|string|max:255',
            'desc_ar' => 'nullable|string|max:255',
            'mandatory' => 'required|boolean',
            'sheet_id' => 'required',
            'type' => ['required', Rule::in(['number','text','select','number_percentage','date','datetime','time'])],
        ]);
        
        $validated['licensee_template_id'] = $templateId;

        LicenseeTemplateKey::create($validated);

        return back()->with('success', 'Key added successfully.');
    }

    /**
     * Delete a template or its keys
     */
    public function destroy(LicenseeTemplate $licenseeTemplate)
    {
        $licenseeTemplate->keys()->delete();
        $licenseeTemplate->delete();

        return redirect()->route('licensee_templates.index')->with('success', 'Template deleted successfully.');
    }

    /**
 * Update a Licensee Template Key (AJAX)
 */
public function updateKey(Request $request, $key)
{
    try {
        $validated = $request->validate([
            'desc_en' => 'required|string|max:255',
            'desc_ar' => 'nullable|string|max:255',
            'mandatory' => 'required|boolean',
            'type' => ['required', Rule::in(['number','text','select','number_percentage','date','datetime','time'])],
        ]);

        $templateKey = LicenseeTemplateKey::findOrFail($key);
        $templateKey->update($validated);
        
        return back()->with('success', 'Key added successfully.');
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }

}

/**
 * Delete a Licensee Template Key (AJAX)
 */
public function deleteKey(LicenseeTemplateKey $key)
{
    $key->delete();

    return response()->json([
        'success' => true,
        'message' => 'Key deleted successfully.'
    ]);
}
}
