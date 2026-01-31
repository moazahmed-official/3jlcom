<?php

namespace App\Http\Requests;

use App\Models\Ad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CloseAuctionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $ad = $this->route('ad');
        
        // Handle route model binding or raw ID
        if ($ad instanceof Ad) {
            $adModel = $ad;
        } else {
            $adModel = Ad::with('auction')->find($ad);
        }

        if (!$adModel || $adModel->type !== 'auction' || !$adModel->auction) {
            return false;
        }

        // Owner or admin can close
        return $adModel->user_id === auth()->id() || auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // No additional fields needed for closing
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $ad = $this->route('ad');
            
            if ($ad instanceof Ad) {
                $adModel = $ad;
            } else {
                $adModel = Ad::with('auction')->find($ad);
            }

            if (!$adModel || !$adModel->auction) {
                return;
            }

            $auction = $adModel->auction;

            // Cannot close an already closed/cancelled auction
            if ($auction->status === 'closed') {
                $validator->errors()->add(
                    'auction',
                    'This auction has already been closed.'
                );
                return;
            }

            if ($auction->status === 'cancelled') {
                $validator->errors()->add(
                    'auction',
                    'This auction has been cancelled.'
                );
                return;
            }

            // Non-admin can only close after end_time
            if (!auth()->user()->isAdmin() && !$auction->hasEnded()) {
                $validator->errors()->add(
                    'auction',
                    'You can only close the auction after it has ended. ' .
                    'The auction ends at ' . $auction->end_time->toDateTimeString() . '.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'code' => 422,
                'message' => 'Cannot close auction',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'code' => 403,
                'message' => 'Unauthorized',
                'errors' => ['authorization' => ['You do not have permission to close this auction']]
            ], 403)
        );
    }
}
