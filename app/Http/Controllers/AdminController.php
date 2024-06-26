<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage; // Add this line


use App\Models\AppSetting;
use App\Models\UserProfile;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerProfileView;
use App\Models\Commission;
use App\Models\SpecificService;
use App\Models\Booking;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $settings = AppSetting::first();

        $feature = $request->query('feature');

        // [1] Account Verification
        $unverifiedUsers = UserProfile::where('is_verified', 0)->get();
        // UserProfile::where('is_verified', '!=', 1)->get();

        // Fetch all workers with completed bookings count and specific services prices
        $workers = WorkerProfile::with(['user', 'category', 'service'])
            ->withCount(['bookings as completed_bookings' => function ($query) {
                $query->where('status', 'Completed');
            }])
            ->get();

        // Calculate commission to pay for each eligible worker
        foreach ($workers as $worker) {
            // Get lowest and highest price from specific services
            $specificServices = $worker->specificServices()->pluck('price');
            $worker->lowest_price = $specificServices->min();
            $worker->highest_price = $specificServices->max();

            // Calculate commission
            $worker->commission = $worker->completed_bookings * $worker->lowest_price * 0.1;

            // Calculate verified payments
            $worker->verified_payment = $worker->commissions()->where('is_verified', 1)->sum('amount');
        }

        // Determine eligible workers
        $eligibleWorkers = $workers->filter(function ($worker) {
            return $worker->commission - $worker->verified_payment > 0;
        });

        // Count eligible workers
        $eligibleWorkersCount = $eligibleWorkers->count();

        // Fetch all commissions for display
        $commissions = Commission::all();

        // [4] Income Monitoring
        $earnings = Commission::getTotalEarningsByMonth();
        $totalEarnings = Commission::getTotalEarnings();


        return view('pages.admin-home', [
            'settings' => $settings,
            'feature' => $feature,
            'unverifiedUsers' => $unverifiedUsers,
            'workers' => $workers,
            'eligibleWorkers' => $eligibleWorkers,
            'commissions' => $commissions,
            'eligibleWorkersCount' => $eligibleWorkersCount,
            'totalEarnings' => $totalEarnings,
            'earnings' => $earnings,
        ]);
    }

    // [1] Account Verification
    public function verifyAccountVerification($id)
    {
        $user = UserProfile::find($id);
        $user->verified_at = now();
        $user->is_verified = 1;
        $user->save();

        return redirect()->back()->with('success', 'User account verified successfully.');
    }

    public function rejectAccountVerification($id)
    {
        $user = UserProfile::find($id);
        $user->verified_at = now();
        $user->is_verified = 2;
        $user->save();

        return redirect()->back()->with('success', 'User account verification rejected.');
    }


    public function deactivateWorker($id)
    {
        $worker = WorkerProfile::find($id);
        $worker->status = 0;
        $worker->save();

        return redirect()->back();
    }

    public function reactivateWorker($id)
    {
        $worker = WorkerProfile::find($id);
        $worker->status = 1;
        $worker->save();

        return redirect()->back();
    }

    // Request Commission Payment for All Eligible Workers
    public function requestCommissionPaymentAll()
    {
        // Fetch all workers
        $workers = WorkerProfile::with(['user', 'category', 'service', 'specificServices'])
            ->withCount(['bookings as completed_bookings' => function ($query) {
                $query->where('status', 'Completed');
            }])
            ->get();

        // Determine eligible workers
        $eligibleWorkers = $workers->filter(function ($worker) {
            $commissionToPay = $worker->commissionToPay();
            $verifiedPayment = $worker->commissions()->where('is_verified', 1)->sum('amount');
            $difference = $commissionToPay - $verifiedPayment;

            // Check if the worker already has a pending commission request
            $hasPendingRequest = $worker->commissions()->where('is_paid', 0)->exists();

            // Check if the worker has any unverified commission request
            $hasUnverifiedRequest = $worker->commissions()->where('is_verified', 0)->exists();

            return $difference > 0 && !$hasPendingRequest && !$hasUnverifiedRequest;
        });

        // Prepare data for insertion
        $insertionData = $eligibleWorkers->map(function ($worker) {
            $amount = $worker->commissionToPay() - $worker->verified_payment;

            return [
                'worker_id' => $worker->id,
                'amount' => $amount,
                'is_paid' => 0,
                'is_verified' => 0,
                'paid_at' => null,
                'verified_at' => null,
                'requested_at' => Carbon::now(),
            ];
        });

        // dd($insertionData);

        // Insert eligible workers into the commissions table
        foreach ($insertionData as $data) {
            Commission::create($data);
        }

        return redirect()->back()->with('success', 'Payment requests have been successfully made for all eligible workers.');
    }




    // Verify Reject Proof of Payment
    public function verifyCommission($id)
    {
        $commission = Commission::findOrFail($id);

        $commission->is_verified = 1;
        $commission->verified_at = Carbon::now();
        $commission->save();

        return redirect()->back()->with('success', 'Commission verified successfully.');
    }

    // Reject Proof of Payment
    public function rejectCommission($id)
    {
        $commission = Commission::findOrFail($id);

        $commission->is_verified = 2;
        $commission->verified_at = Carbon::now();
        $commission->save();

        return redirect()->back()->with('success', 'Commission rejected successfully.');
    }

    // [5] App Settings
    public function updateName(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
        ]);

        $settings = AppSetting::first();
        $settings->app_name = $request->input('app_name');
        $settings->save();

        Cache::forget('app_settings');

        return redirect()->back()->with('success', 'App Name updated successfully!');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'app_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $settings = AppSetting::first();

        if ($request->hasFile('app_logo')) {
            $logoPath = $request->file('app_logo')->store('public/app_settings', 'public');

            if ($settings->app_logo && Storage::exists($settings->app_logo)) {
                Storage::delete($settings->app_logo);
            }

            $settings->app_logo = $logoPath;
        }

        $settings->save();


        return redirect()->back()->with('success', 'App Logo updated successfully!');
    }
}
