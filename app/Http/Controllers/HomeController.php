<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\App;
use Session;

use Illuminate\Support\Facades\DB;
use App\Models\Assessment;



class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        if (view()->exists($request->path())) {
            return view($request->path());
        }
        return abort(404);
    }

    public function root()
    {
        return view('index');
    }

    public function dashboard()
    {

        $totalAssessments = DB::table('sr_licensee_assessments')
        ->where('status','!=','archived')
        ->count();

        $totalForms = DB::table('sr_licensee_templates')
            ->where('status','active')
            ->count();

        $totalLicensees = DB::table('sr_licensees')
            ->where('status','active')
            ->count();

        $totalDepartments = DB::table('sr_departments')
            ->where('status','active')
            ->count();

        $statusData = DB::table('sr_licensee_assessments')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $statusLabels = $statusData->pluck('status');
        $statusCounts = $statusData->pluck('total');

        /* ============================================================
           1️⃣ Assessments by Licensee → BAR CHART
        ============================================================ */

        $licenseeData = DB::table('sr_licensee_assessments as a')
            ->join('sr_licensees as l', 'l.id', '=', 'a.licensee_id')
            ->select(
                'l.name_en as licensee',
                DB::raw('COUNT(a.id) as total')
            )
            ->groupBy('l.name_en')
            ->orderBy('total', 'desc')
            ->get();

        $licenseeLabels = $licenseeData->pluck('licensee');
        $licenseeCounts = $licenseeData->pluck('total');

        /* ============================================================
           2️⃣ Department-wise Assessments → PIE CHART
        ============================================================ */

        $departmentData = DB::table('sr_licensee_assessments as a')
            ->join('sr_licensee_templates as t', 't.id', '=', 'a.licensee_template_id')
            ->join('sr_departments as d', 'd.id', '=', 't.department_id')
            ->select(
                'd.name_en as department',
                DB::raw('COUNT(a.id) as total')
            )
            ->groupBy('d.name_en')
            ->orderBy('total', 'desc')
            ->get();

        $departmentLabels = $departmentData->pluck('department');
        $departmentCounts = $departmentData->pluck('total');


        /* ============================================================
           3️⃣ Template Usage Analytics → LINE CHART
        ============================================================ */

        $templateData = DB::table('sr_licensee_assessments as a')
            ->join('sr_licensee_templates as t', 't.id', '=', 'a.licensee_template_id')
            ->select(
                't.sheet_name',
                DB::raw('COUNT(a.id) as usage_count')
            )
            ->groupBy('t.sheet_name')
            ->orderBy('usage_count', 'desc')
            ->get();

        $templateLabels = $templateData->pluck('sheet_name');
        $templateCounts = $templateData->pluck('usage_count');

        $assessments = Assessment::with(['licenseeTemplate.subfolder'])
        ->orderByDesc('created_at')
        ->get();


        return view('index', compact(
            'totalAssessments',
            'totalForms',
            'totalLicensees',
            'totalDepartments',
            'statusLabels',
            'statusCounts',
            'licenseeLabels',
            'licenseeCounts',
             'departmentLabels',
            'departmentCounts',

            'templateLabels',
            'templateCounts',

            'assessments'
        ));
    }

    /*Language Translation*/
    public function lang($locale)
    {
        
        if ($locale) {
            App::setLocale($locale);
            Session::put('lang', $locale);
            Session::save();
            return redirect()->back()->with('locale', $locale);
        } else {
            return redirect()->back();
        }
    }

    public function updateProfile(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        $user = User::find($id);
        $user->name = $request->get('name');
        $user->email = $request->get('email');

        if ($request->file('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
            $avatarPath = public_path('/images/');
            $avatar->move($avatarPath, $avatarName);
            $user->avatar =  $avatarName;
        }

        $user->update();
        if ($user) {
            Session::flash('message', 'User Details Updated successfully!');
            Session::flash('alert-class', 'alert-success');
            // return response()->json([
            //     'isSuccess' => true,
            //     'Message' => "User Details Updated successfully!"
            // ], 200); // Status code here
            return redirect()->back();
        } else {
            Session::flash('message', 'Something went wrong!');
            Session::flash('alert-class', 'alert-danger');
            // return response()->json([
            //     'isSuccess' => true,
            //     'Message' => "Something went wrong!"
            // ], 200); // Status code here
            return redirect()->back();

        }
    }

    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!(Hash::check($request->get('current_password'), Auth::user()->password))) {
            return response()->json([
                'isSuccess' => false,
                'Message' => "Your Current password does not matches with the password you provided. Please try again."
            ], 200); // Status code
        } else {
            $user = User::find($id);
            $user->password = Hash::make($request->get('password'));
            $user->update();
            if ($user) {
                Session::flash('message', 'Password updated successfully!');
                Session::flash('alert-class', 'alert-success');
                return response()->json([
                    'isSuccess' => true,
                    'Message' => "Password updated successfully!"
                ], 200); // Status code here
            } else {
                Session::flash('message', 'Something went wrong!');
                Session::flash('alert-class', 'alert-danger');
                return response()->json([
                    'isSuccess' => true,
                    'Message' => "Something went wrong!"
                ], 200); // Status code here
            }
        }
    }
}
