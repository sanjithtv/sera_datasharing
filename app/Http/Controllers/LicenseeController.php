<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Licensee;
use App\Models\LicenseeSubfolder;

class LicenseeController extends Controller
{
    public function __construct()
    {
        // Role/permission protection
        $this->middleware(['permission:read-licensee'])->only('index');
        $this->middleware(['permission:create-licensee'])->only(['create', 'store']);
        $this->middleware(['permission:edit-licensee'])->only(['edit', 'update']);
        $this->middleware(['permission:delete-licensee'])->only('archive');
    }

    /** 1. List all Licensees other than archived/deleted */
    public function index()
    {
        $licensees = Licensee::where('status', '!=', 'archived')->get();
        return view('modules.licensees.index', compact('licensees'));
    }

    /** 2. Show form to create */
    public function create()
    {
        return view('modules.licensees.create');
    }

    /** 2. Create new Licensee */
    public function store(Request $request)
    {
        // Manually create a validator
        $validator = \Validator::make($request->all(), [
            'code' => 'required|unique:sr_licensees,code|max:50',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        // If validation fails, redirect back with flash messages
        if ($validator->fails()) {
            // Collect all messages into one string (optional)
            $messages = implode(' ', $validator->errors()->all());

            return redirect()->back()
                ->withInput() // Keep old input values
                ->with('error', $messages); // Flash message
        }

        // Validation passed — create the record
        Licensee::create($validator->validated());

        return redirect()->route('licensees.index')
            ->with('success', 'Licensee created successfully.');
    }

    /** 3. Show form to edit */
    public function edit(Request $request,$licenseeId)
    {
        $licensee = Licensee::find($licenseeId);
        return view('modules.licensees.edit', compact('licensee'));
    }

    /** 3. Update existing Licensee */
    public function update(Request $request, $licenseeId)
    {
        $licensee = Licensee::find($licenseeId);
        $validated = $request->validate([
            'code' => 'required|max:50|unique:sr_licensees,code,' . $licensee->id,
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        $licensee->update($validated);

        return redirect()->route('licensees.index')
            ->with('success', 'Licensee updated successfully.');
    }

    /** 4. Archive a Licensee */
    public function archive(Licensee $licensee)
    {
        $licensee->update(['status' => 'archived']);
        $licensee->delete(); // Soft delete

        return redirect()->route('licensees.index')
            ->with('success', 'Licensee archived successfully.');
    }

    //==================================================//
    /** List all Licensee Subfolders other than archived/deleted */
    public function subfolders()
    {
        $subfolders = LicenseeSubfolder::where('status', '!=', 'archived')->get();
        return view('modules.subfolders.index', compact('subfolders'));
    }
}
