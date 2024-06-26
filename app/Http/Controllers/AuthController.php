<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Models\AppSetting;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerProfileView;

use App\Models\Booking;

class AuthController extends Controller
{
    // Landing page
    public function index()
    {
        $settings = AppSetting::first();

        return view('pages.landing', [
            'settings' => $settings
        ]);
    }

    // Go to sign up
    public function sign_up()
    {
        $settings = AppSetting::first();

        return view('auth.sign-up', [
            'settings' => $settings
        ]);
    }

    // Go to sign up and Become a Worker
    public function signUpAndBecomeAWorker()
    {
        $settings = AppSetting::first();

        return view('auth.sign-up-and-become-a-worker', [
            'settings' => $settings
        ]);
    }

    // Login
    public function login(Request $request)
    {
        $settings = AppSetting::first();

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            // $workers = WorkerProfile::with('user')->get();
            if (Auth::user()->profile->role_id == 1) {
                return redirect()->route('admin.home', [
                    'settings' => $settings,
                    'feature' => 1
                ]);
            }
            return redirect()->route('home', [
                'settings' => $settings
            ]);
        }

        $user = User::where('username', $credentials['username'])->first();

        // Username doesn't exist
        if (!$user) {
            return redirect()->back()->withInput($request->only('username'))->withErrors(['error' => 'Account does not exist.']);
        }

        // Incorrect password
        return redirect()->back()->withInput($request->only('username'))->withErrors(['error' => 'Incorrect password.']);
    }

    // Home
    public function home()
    {
        $settings = AppSetting::first();

        return view('pages.home', [
            'settings' => $settings
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    // Go to sign up Worker
    public function sign_up_worker()
    {
        $settings = AppSetting::first();

        return view('auth.sign-up-worker', [
            'settings' => $settings
        ]);
    }

    public function becomeWorker(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        $working_days_string = implode(',', array_map(function ($day) {
            return $day ? '1' : '0';
        }, $this->working_days));

        // Insert into worker_profiles
        $user->workerprofile()->create([
            'user_id' => $user->id,
            'category_id' => $this->category,
            'service_id' => $this->service,
            'description' => $this->description,
            'pricing' => $this->pricing,
            'minimum_duration' => $this->minimum_duration,
            'maximum_duration' => $this->maximum_duration,
            'working_days' => $working_days_string,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'valid_id' => $this->valid_id,
            'resume' => $this->resume,
        ]);

        // Update the role_id to 3 in the user_profiles table
        $user->profile()->update(['role_id' => 3]);

        // dd([
        //     'user_id' => $user->id,
        //     'category_id' => $this->category,
        //     'service_id' => $this->service,
        //     'description' => $this->description,
        //     'pricing' => $this->pricing,
        //     'minimum_duration' => $this->minimum_duration,
        //     'maximum_duration' => $this->maximum_duration,
        //     'working_days' => $working_days_string,
        //     'start_time' => $this->start_time,
        //     'end_time' => $this->end_time,
        //     'valid_id' => $this->valid_id,
        //     'resume' => $this->resume,
        // ]);

        // Create a new worker record associated with the user
        // $userWorker = UserWorker::create([
        //     'user_id' => $user->id,
        //     'category_id' => $request->input('category'),
        //     'service_id' => $request->input('service'),
        //     'description' => $request->input('description'),
        //     'pricing' => $request->input('pricing'),
        //     'minimum_duration' => $request->input('minimum_duration'),
        //     'maximum_duration' => $request->input('maximum_duration'),
        //     'working_days' => $request->input('working_days'),
        //     'start_time' => $request->input('start_time'),
        //     'end_time' => $request->input('end_time'),
        //     'valid_id' => $request->input('valid_id'),
        //     'resume' => $request->input('resume'),
        // ]);

        // Redirect the user to a success page or any other appropriate destination
        return redirect('home');
    }












    /**
     * Display the specified worker profile.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showWorkerProfile($userId, $workerProfileId)
    {
        $settings = AppSetting::first();

        // return view('pages.worker-profile', compact('user', 'worker', 'userProfile', 'workerProfile', 'availableDays'));
        return view('pages.worker-profile', [
            'settings' => $settings,
            'userId' => $userId,
            'workerProfileId' => $workerProfileId,
        ]);

    }

    // Tracking of Viewing a Worker's Profile
    public function trackView(Request $request)
    {
        $userId = auth()->id();
        $workerId = $request->worker_id;

        // Get the last view for the current user and worker
        $lastView = WorkerProfileView::where('user_id', $userId)
                    ->where('worker_id', $workerId)
                    ->latest('viewed_at')
                    ->first();

        // Check if the last view was more than 15 minutes ago
        if ($lastView && $lastView->viewed_at->gt(Carbon::now()->subMinutes(15))) {
            // If less than 15 minutes, redirect without inserting
            return redirect()->route('worker.profile', ['userId' => $request->user_id, 'workerProfileId' => $workerId]);
        }

        // Insert new view record
        WorkerProfileView::create([
            'user_id' => $userId,
            'worker_id' => $workerId,
            'category_id' => $request->category_id,
            'service_id' => $request->service_id,
            'viewed_at' => now(),
        ]);

        return redirect()->route('worker.profile', ['userId' => $request->user_id, 'workerProfileId' => $workerId]);
        }




        // Book a Service
        public function book(Request $request)
        {
            // Validate the incoming request data
            // $validatedData = $request->validate([
            //     'user_id' => 'required',
            //     'worker_id' => 'required',
            //     'specific_service_id' => 'required',
            //     'booking_date' => 'required|date|after:today',
            //     'booking_time' => 'required',
            //     'booking_notes' => 'required|string',
            // ]);
            $validatedData = $request->validate([
                'user_id' => 'required',
                'worker_id' => 'required',
                'specific_service_id' => 'required',
                'booking_date' => 'required|date|after:today',
                'booking_time' => 'required',
                'booking_notes' => 'required|string',
            ], [
                'user_id.required' => 'User ID is required',
                'worker_id.required' => 'Worker ID is required',
                'specific_service_id.required' => 'Specific Service ID is required',
                'booking_date.required' => 'Booking date is required',
                'booking_date.date' => 'Booking date must be a valid date',
                'booking_date.after' => 'Booking date must be after today',
                'booking_time.required' => 'Booking time is required',
                'booking_notes.required' => 'Booking notes are required',
                'booking_notes.string' => 'Booking notes must be a string',
            ]);

            // Insert into bookings table
            $booking = new Booking();
            $booking->user_id = $validatedData['user_id'];
            $booking->worker_id = $validatedData['worker_id'];
            $booking->specific_service_id = $validatedData['specific_service_id'];
            $booking->date = $validatedData['booking_date'];
            $booking->time = $validatedData['booking_time'];
            $booking->notes = $validatedData['booking_notes'];
            $booking->status = "Pending";
            $booking->booked_datetime = Carbon::now();
            $booking->save();

            return redirect()->back()->with('success', 'You\'ve successfully booked a service!');
        }

    }
