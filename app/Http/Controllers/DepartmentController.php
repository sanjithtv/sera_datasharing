<?php

namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function __construct()
    {
        // Spatie permission middleware
        $this->middleware('permission:read-department')->only('index');
        $this->middleware('permission:create-department')->only(['create','store']);
        $this->middleware('permission:edit-department')->only(['edit','update']);
        $this->middleware('permission:delete-department')->only('archive');
    }

    /** 1️⃣ List all departments (not archived) */
    public function index()
    {
        $departments = Department::where('status', '!=', 'archived')->get();
        return view('modules.departments.index', compact('departments'));
    }

    /** 2️⃣ Show create form */
    public function create()
    {
        return view('modules.departments.create');
    }

    /** 2️⃣ Store new department */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'     => 'required|unique:departments,code|max:50',
            'name_en'  => 'required|string|max:255',
            'name_ar'  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $validator->errors()->all()));
        }

        Department::create($validator->validated());

        return redirect()->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    /** 3️⃣ Show edit form */
    public function edit(Request $request,$departmentId)
    {
        $department = Department::find($departmentId); 
        return view('modules.departments.edit', compact('department'));
    }

    /** 3️⃣ Update existing department */
    public function update(Request $request, $departmentId)
    {
        $department = Department::find($departmentId); 
        $validator = Validator::make($request->all(), [
            'code'     => 'required|max:50|unique:sr_departments,code,' . $department->id,
            'name_en'  => 'required|string|max:255',
            'name_ar'  => 'required|string|max:255',
            'status'  => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $validator->errors()->all()));
        }

        $department->update($validator->validated());

        return redirect()->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /** 4️⃣ Archive a department */
    public function archive(Department $department)
    {
        $department->update(['status' => 'archived']);

        return redirect()->route('departments.index')
            ->with('success', 'Department archived successfully.');
    }
}
