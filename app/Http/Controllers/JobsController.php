<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Mail\JobNotificationEmail;

use Illuminate\Http\Request;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Mail;
use  App\Models\Category;
use  App\Models\JobType;
use App\Models\SavedJob;
use  App\Models\User;
use App\Models\Job;

class JobsController extends Controller
{
    public function index(Request $request){

       $categories =  Category::where('status',1)->get();
       $jobType = JobType::where('status',1)->get();

        $jobs = Job::where('status',1);

        // search using keyword
        if(!empty($request->keyword)){
            $jobs = $jobs->Where(function($query) use($request){
                $query->orWhere('title','like','%'.$request->keyword.'%');
                $query->orWhere('keywords','like','%'.$request->keyword.'%');
            });

        }
        // search using location
        if(!empty($request->location)){
            $jobs = $jobs->where('location',$request->locaton);
        }

        //Search using category
        if(!empty($request->category)){
            $jobs = $jobs->where('category_id',$request->category);
        }

        $jobTypeArray = [];

        //Search using job Type
        if(!empty($request->jobType)){
            //1,2,3
            $jobTypeArray = explode(',',$request->jobType);
            $jobs = $jobs->whereIn('job_type_id',$jobTypeArray);
        }
         //Search using experience
         if(!empty($request->experience)){
            $jobs = $jobs->where('experience',$request->experience);
        }



       $jobs =  $jobs->with(['jobType','category']);

       if($request->sort == '0'){
        $jobs =  $jobs->orderBy('created_at', 'ASC');
       }
       else{
        $jobs =   $jobs->orderBy('created_at','DESC');

       }

       $jobs  =  $jobs->paginate(5);

        // dd($jobs);

        return view ('front.jobs',[
            'categories'=>  $categories ,
            'jobTypes'  =>  $jobType,
            'jobs'      =>  $jobs,
            'jobTypeArray'  =>  $jobTypeArray
        ]);

    }

    //this method will show detail page

    public function detail($id){

        $job = Job::where([
                            'id'=> $id,
                            'status'=>1
                        ])->with(['jobType','category'])->first();

            if( $job == null){
                abort(404);
            }


            $count= 0;
            if(Auth::user()){
                $count = SavedJob::where([
                    'user_id'=> Auth::user()->id,
                    'job_id'=> $id
            ])->count();

            }

            // fetch applications
           $applications =  JobApplication::where('job_id',$id)->with('user')->get();
        //    dd( $applications);

       return view ('front.jobDetail',['job' => $job,
                                        'count'=> $count,
                                        'applications'=>$applications
                                    ]);

    }

    //Apply Jobs
    public function applyJob(Request $request){
        $id = $request->id;
        $job = Job::where('id',$id)->first();
        //if job not found in db

        if($job == null){

            $message = 'Job does not exist';
            session()->flash('error', $message);
            return response()->json([
                'status'=>false,
                'message'=> $message
            ]);
        }

        // you can not apply on your own job
        $employer_id = $job->user_id;

        if($employer_id == Auth::user()->id){

            $message = ' you can not apply on your own job';
            session()->flash('error', $message);
            return response()->json([
                'status'=>false,
                'message'=> $message,
               
            ]);
            // echo "kijkv";

        }

        //You can not apply on a job twise
        $jobApplicationCount = JobApplication::where([
            'user_id'=>Auth::user()->id,
            'job_id'=>$id
        ])->count();
        
        if($jobApplicationCount > 0){
            $message = ' you already applied on this job.';
            session()->flash('error', $message);
            return response()->json([
                'status'=>false,
                'message'=> $message,
               
            ]);

        }

          
        $application = new JobApplication();
        $application->job_id = $id;
        $application->user_id = Auth::user()->id;
        $application->employer_id = $employer_id;
        $application->applied_date = now();
        $application->save();
       
        // send Notification Em(ail to Employer
        $employer = User::where('id',$employer_id)->first();

        $mailData = [
            'employer'=>$employer,
            'user'=> Auth::user(),
            'job'=>$job,

        ];

        Mail::to($employer->email)->send(new JobNotificationEmail($mailData));
        $message = 'you have successfully applied';

        session()->flash('success', $message);
        return response()->json([
            'status'=>true,
            'message'=> $message
        ]);

    }

    //saveJob

    public function saveJob(Request $request){
        $id = $request->id;
        $job = Job::find($id);

        if( $job == null){

            session()->flash('error','Job not found');
            return response()->json([
                'status'=>false,
               
            ]);

        }

        //check if user already saved the job
          $count = SavedJob::where([
                'user_id'=>Auth::user()->id,
                'job_id'=>$id
        ])->count();

         
        if($count > 0){
            session()->flash('error','You already save  this job');
            return response()->json([
                'status'=>false,
            
            ]);

            }

            $savedJob = new SavedJob;
            $savedJob->job_id = $id;
            $savedJob->user_id = Auth::user()->id;
            $savedJob->save();

            session()->flash('success','You have successfully saved the job');
            return response()->json([
                'status'=>true,
                
            ]);
    

    


        }

   
}
