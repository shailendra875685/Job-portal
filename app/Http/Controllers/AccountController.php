<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\ResetPasswordEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\Category;
use App\Models\SavedJob;
use App\Models\JobType;
use App\Models\Job;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AccountController extends Controller
{
    // this method will show user registration page
    public function registration(){
        return view ('front.account.registration');
        
    }


    // this method will show  a user
    public function processRegistration(Request $request){
        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|min:5|same:Confirm_password',
            'Confirm_password'=>'required',
        ]);
        
        if($validator->passes()){

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->name = $request->name;

            $user->save();

            session()->flash('success','you have register successfully');
            
            return response()->json([
                'status'=>true,
                'errors'=>[]
            ]);
        }else{
            return response()->json([
            'status'=>false,
            'errors'=>$validator->errors()
        ]);
        }
        
    } 
    // this method will show user login page
    public function login(){
        return view ('front.account.login');

    }

    public function authenticate(Request $request){
        $validator = validator::make($request->all(),[
            'email' => 'required|email',
            'password'=>'required'
        ]);

        if( $validator->passes()){
      
            if(Auth::attempt([
                'email'=>$request->email,
                'password'=>$request->password]))
                {
                return redirect()->route('account.profile');

            }else{
                return redirect()->route('account.login')->with('error','Either Email Email/Password is incorect');
            }

        }else{
           return redirect()->route('account.login')
                 ->withErrors($validator)
                 ->withInput($request->only('email'));
        }

    }

    //profile
    public function profile(){

        // echo Auth::user()->password;
        // dd(Auth::user());
        $id = Auth::user()->id;
        // dd($id);
        $user = user::where('id',$id)->first();
        // $user = user::find($id);
        // dd($user);
       return view('front.account.profile',[
        'user'=>$user
       ]);
    }

    //updateprofile
    public function updateProfile(Request $request){
       

        $id = Auth::user()->id;
        $validator = validator::make($request->all(),[
            'name'=>'required|min:5',
            'email'=>'required|email|unique:users,email,'.$id.',id',

        ]);

        if($validator->passes()){

            $user= User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->designation = $request->designation;
            $user->mobile = $request->mobile;

            $user->save();
        
            session()->flash('success','Profile updated successfully');
            return response()->json([
                'status'=>true,
                'errors'=>[]
            ]);
            
        }else{
            return response()->json([
                'status'=>false,
                'errors'=>$validator->errors()
            ]);
        }

    }

    //logout
    public function logout(){
        Auth::logout();

        return redirect()->route('account.login');
    }


    //image profile

    public function updateProfilePic(Request $request){
        // dd($request->all());

        $id = Auth::user()->id;
        $validator = validator::make($request->all(),[
            'image'=>'required|image'
        ]);
        if($validator->passes()){

            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = $id.'_'.time().'.'.$ext; //3-234567.jpg
            $image->move(public_path('/profile_pic/'), $imageName);

            //create a small thumnall
            $sourcePath = public_path('/profile_pic/'.$imageName);
            $manager = new ImageManager(Driver::class);
            $image = $manager->read($sourcePath);

            $image->cover(150,150);
            $image->toPng()->save(public_path('/profile_pic/thumb/'.$imageName));

            //Delete old Profile Pic
            File::delete(public_path('/profile_pic/thumb/'.Auth::user()->image));
            File::delete(public_path('/profile_pic/'.Auth::user()->image));



            User::where('id',$id)->update(['image'=>$imageName]);

            session()->flash('success','profile updated successfully.');

            return response()->json([
                'status'=>true,
                'errors'=>[]
            ]);
          
        }else{
          return response()->json([
            'status'=>false,
            'errors'=>$validator->errors(),
          ]);
        }

    }
    //create job
    public function createjob(){

        $categories = Category::orderBy('name','ASC')->where('status',1)->get();

        $jobType = JobType::orderBy('name','ASC')->where('status',1)->get();


        return view ('front.account.job.create',[
            'categories'=>$categories,
            'jobTypes'=>   $jobType
        ]);
    }
    //save job

    public function savejob(Request $request){
        $rules = [
            'title'=>'required|min:5|max:200',
            'category'=>'required',
            'jobType'=>'required',
            'vacancy'=>'required|integer',
            'location'=>'required|max:50',
            'description'=>'required',
            'company_name'=>'required|min:3|max:80',
        ];

        $validator = validator::make($request->all(),$rules);
       

        if($validator->passes()){

            $job = new Job();

            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords	 = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->website;
            
            $job->save();
            
            session()->flash('success','job added successfully');

            return response()->json([
                'status'=>true,
                'errors'=>[]
            ]);

        }else{
            return response()->json([
                'status'=>false,
                'errors'=>$validator->errors()
            ]);
        }

    }

    //myJobs
    public function myJobs(){

        $jobs = Job::where('user_id',Auth::user()->id)->with('jobType')->orderBy('created_at','DESC')->paginate(10);

        // dd($jobs);

        return view('front.account.job.my-jobs',[
            'jobs'=>$jobs
        ]);
    }

    //idit

    public function editJob (Request  $request , $id){

        // dd($id);

        $categories = Category::orderBy('name','ASC')->where('status',1)->get();

        $jobType = JobType::orderBy('name','ASC')->where('status',1)->get();

        $job = Job::where([
            'user_id'=>Auth::user()->id,
            'id'=>$id
        ])->first();

            // dd($job);

        if($job == null){
            abort(404);
        }

        return view('front.account.job.edit',[
            'categories'=>$categories,
            'jobTypes'=>   $jobType,
            'job'=>   $job,

        ]);
    }

    //updateJob
    public function updateJob(Request $request ,$id){
        $rules = [
            'title'=>'required|min:5|max:200',
            'category'=>'required',
            'jobType'=>'required',
            'vacancy'=>'required|integer',
            'location'=>'required|max:50',
            'description'=>'required',
            'company_name'=>'required|min:3|max:80',
        ];

        $validator = validator::make($request->all(),$rules);
       

        if($validator->passes()){

            $job = Job::find($id);

            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords	 = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->website;
            
            $job->save();
            
            session()->flash('success','job updated successfully');

            return response()->json([
                'status'=>true,
                'errors'=>[]
            ]);

        }else{
            return response()->json([
                'status'=>false,
                'errors'=>$validator->errors()
            ]);
        }

    }

    //deleteJob
    public function deleteJob(Request $request){

       $job = job::where([
            'user_id'=>Auth::user()->id,
            'id'=>$request->jobId
        ])->first();

        if($job == null){
            session()->flash('error','Either job delete or not found.');
            return response()->json([
                'status'=>true,
            ]);
        }


        job::where('id',$request->jobId)->delete();
        session()->flash('success',' job delete successfully.');
        return response()->json([
            'status'=>true,
        ]);

    }

    public function myJobApplications(){
        $jobApplications = JobApplication::where('user_id',Auth::user()->id)
        ->with(['job','job.jobType','job.applications'])
        ->orderBy('created_at','DESC')
        ->paginate(10);
        // dd($jobs);
        return view('front.account.job.my-job-applications',[
            'jobApplications'=> $jobApplications,
        ]);

    }

    public function removeJobs(Request $request){
        $jobApplication = JobApplication::where([
                        'id'=>$request->id,
                        'user_id'=>Auth::user()->id]
                    )->first();

             if( $jobApplication == null){
                session()->flash('error','Job application not found');
                return response()->json([
                    'status'=>false,
                ]);
             }   
             
             JobApplication::find($request->id)->delete();
                    session()->flash('success','Job application remove successefully');
                    return response()->json([
                        'status'=>true,
                    ]);
            }

            //savedjob
            public function savedJobs(){
                // $jobApplications = JobApplication::where('user_id',Auth::user()->id)
                // ->with(['job','job.jobType','job.applications'])
                // ->paginate(10);
                // dd($jobs);


                $savedJobs = SavedJob::where([
                    'user_id'=>Auth::user()->id
                ])->with(['job','job.jobType','job.applications'])
                ->orderBy('created_at','DESC')
                ->paginate(10);


                return view('front.account.job.saved-jobs',[
                    'savedJobs'=>$savedJobs,
                ]);
            }

            //removesaved job
            public function removeSavedJob(Request $request){
                $savedJob = SavedJob::where([
                                'id'=>$request->id,
                                'user_id'=>Auth::user()->id]
                            )->first();
        
                     if( $savedJob == null){
                        session()->flash('error','Job  not found');
                        return response()->json([
                            'status'=>false,
                        ]);
                     }   
                     
                     SavedJob::find($request->id)->delete();
                            session()->flash('success','Job  remove successefully');
                            return response()->json([
                                'status'=>true,
                            ]);
                    }


                    //UpdatePassword
                    public function updatePassword(Request $request){
                        $validator = validator::make($request->all(),[
                            'old_password'=>'required',
                            'new_password'=>'required|min:5',
                            'confirm_password'=>'required|same:new_password',
                        ]);

                        if($validator->fails()){
                            return response()->json([
                                'status'=>false,
                                'errors'=> $validator->errors(), 
                            ]);
                        }

                        if(Hash::check($request->old_password,Auth::user()->password) == false){
                            session()->flash('error','Your old password is incorrect');
                            return response()->json([
                                'status'=>true,
                              
                            ]);

                        }

                        $user = User::find(Auth::user()->id);
                        $user->password = Hash::make($request->new_password);
                        $user->save();


                        session()->flash('success',' Password updated successfully');
                        return response()->json([
                            'status'=>true,
                          
                        ]);

                    }

                    //forgotPassword

                    public function forgotPassword(){
                        return view('front.account.forgot-password');
                    }
                    //processForgotpassword

                    public function processForgotPassword(Request $request){
                        $validator = validator::make($request->all(),[
                            'email'=>'required|email|exists:users,email',
                        ]);

                        if($validator->fails()){
                            return redirect()->route('forgotPassword')->withInput()->withErrors($validator);
                        }



                        $token = Str::random(60);
                        \DB::table('password_reset_tokens')->where('email',$request->email)->delete();

                        \DB::table('password_reset_tokens')->insert([
                            'email'=>$request->email,
                            'token'=>$token,
                            'created_at'=>now(),
                        ]);

                        //Send Email here

                        $user = User::where('email',$request->email)->first();
                        $mailData = [
                            'token'=>$token,
                            'user'=>$user,
                            'subject'=>'You have requested to change your password'
                        ];

                        Mail::to($request->email)->send(new ResetPasswordEmail($mailData));
                        // Mail::to($request->email)->send(new ResetPasswordEmail($mailData));
                        return redirect()->route('forgotPassword')->with('success','reset password email has been send to your inbox');
                    }
                    // reset password

                    public function resetPassword(){
                        
                    }
}
