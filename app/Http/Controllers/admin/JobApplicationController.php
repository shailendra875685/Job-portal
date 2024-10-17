<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    public function index(){
         $applications = JobApplication::orderBy('created_at','DESC')
                        ->with('Job','user','employer')
                        ->paginate(10);
                        // dd(  $applications);


                return view('admin.job-applications.list',[
                    'applications'=>$applications
                ]);        
    }
    public function deleteJobApplication(Request $request){
        $id = $request->id;

        $jobapplication = JobApplication::find($id);

        if($jobapplication == null){
            session()->flash('error','Either  JobApplication not delete');
            return response()->json([
                'status'=>false,
            ]);
        }

        $jobapplication->delete();
        session()->flash('success','JobApplication delete successfully');

        return response()->json([
            'status'=>true,
        ]); 
    }

   
}
