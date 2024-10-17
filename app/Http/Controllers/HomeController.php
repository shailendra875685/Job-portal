<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Str;
use App\Models\Category;
use App\Models\Job;

class HomeController extends Controller
{
    public function index()
    {

       $categories =  Category::where('status',1)->orderBy('name','ASC')->take(8)->get();
       $newcategories = Category::where('status',1)->orderBy('name','ASC')->get();

        // $categories =  Category::where('status',1)->orderBy('name','ASC')->fake(8)->get();
        $featureJobs = Job::where('status',1)
                        
                    ->orderBy('created_at','DESC')
                    ->with('jobType')
                    ->where('isFeatured',0)
                    ->take(6)
                    ->get();
                    // dd( $featureJobs);


        $latestJobs = Job::where('status',1)
                    ->with('jobType')
                    ->orderBy('created_at','DESC')
                    ->take(6)
                    ->get();
                    // dd($latestJobs);


        return view('front.home',[
           
            'categories'=> $categories,
            'featureJobs'=>$featureJobs,
            'latestJobs'=> $latestJobs,
            'newcategories'=> $newcategories
        ]);
    }
}
