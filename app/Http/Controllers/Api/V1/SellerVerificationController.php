<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\SubmitSellerVerificationRequest;
use App\Http\Requests\UserVerificationRequest;
use App\Models\SellerVerificationRequest;
use App\Models\User;
use App\Notifications\AdminSellerVerificationRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class SellerVerificationController extends BaseApiController
{
    public function store(SubmitSellerVerificationRequest $request)
    {
        try {
            DB::beginTransaction();

            $verificationRequest = SellerVerificationRequest::create([
                'user_id' => $request->user()->id,
                'documents' => $request->input('documents'),
                'status' => 'pending',
            ]);

            // Notify admins
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new AdminSellerVerificationRequestNotification(
                    $request->user(),
                    $verificationRequest
                ));
            }

            DB::commit();

            return $this->success([
                'request_id' => $verificationRequest->id,
                'status' => $verificationRequest->status,
                'submitted_at' => $verificationRequest->created_at,
            ], 'Seller verification request submitted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Failed to submit verification request: ' . $e->getMessage());
        }
    }

    public function show(Request $request)
    {
        $user = $request->user();
        
        $verificationRequest = SellerVerificationRequest::where('user_id', $user->id)
            ->with('verifiedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verificationRequest) {
            return $this->error(404, 'No verification request found for this user.');
        }

        return $this->success([
            'id' => $verificationRequest->id,
            'status' => $verificationRequest->status,
            'documents' => $verificationRequest->documents,
            'admin_comments' => $verificationRequest->admin_comments,
            'submitted_at' => $verificationRequest->created_at,
            'verified_at' => $verificationRequest->verified_at,
            'verified_by' => $verificationRequest->verifiedBy,
        ], 'Verification request details retrieved successfully.');
    }

    public function index(Request $request)
    {
        // Only admins can view all verification requests
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            return $this->error(403, 'Unauthorized access.');
        }

        $query = SellerVerificationRequest::with(['user:id,name,email,phone,account_type', 'verifiedBy:id,name,email'])
            ->orderBy('created_at', 'desc');

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $verificationRequests = $query->paginate(20);

        return $this->success([
            'requests' => $verificationRequests->items(),
            'pagination' => [
                'current_page' => $verificationRequests->currentPage(),
                'last_page' => $verificationRequests->lastPage(),
                'per_page' => $verificationRequests->perPage(),
                'total' => $verificationRequests->total(),
            ],
        ], 'Verification requests retrieved successfully.');
    }

    public function update(UserVerificationRequest $request, $requestId)
    {
        try {
            $verificationRequest = SellerVerificationRequest::findOrFail($requestId);

            if ($verificationRequest->status !== 'pending') {
                return $this->error(400, 'This verification request has already been processed.');
            }

            DB::beginTransaction();

            $verificationRequest->update([
                'status' => $request->input('status'),
                'admin_comments' => $request->input('admin_comments'),
                'verified_by' => $request->user()->id,
                'verified_at' => Carbon::now(),
            ]);

            // If approved, update user's verification status
            if ($request->input('status') === 'approved') {
                $verificationRequest->user->update([
                    'email_verified_at' => $verificationRequest->user->email_verified_at ?? Carbon::now(),
                    'is_verified' => true,
                ]);
            }

            DB::commit();

            // Refresh to ensure latest values and eager load verifier
            $verificationRequest->refresh()->load('verifiedBy');

            return $this->success([
                'request_id' => $verificationRequest->id,
                'status' => $verificationRequest->status,
                'admin_comments' => $verificationRequest->admin_comments,
                'verified_at' => $verificationRequest->verified_at,
                'verified_by' => $verificationRequest->verifiedBy?->name,
            ], 'Verification request processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error(500, 'Failed to process verification request: ' . $e->getMessage());
        }
    }
}