<?php

namespace App\Http\Requests;

use App\Models\CaishhaOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeleteCaishhaOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Support both route model binding and raw id parameter
        $routeOffer = $this->route('offer');
        
        if ($routeOffer instanceof CaishhaOffer) {
            $offer = $routeOffer;
        } else {
            $offer = CaishhaOffer::find($routeOffer);
        }

        if (!$offer) {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Offer owner or admin can delete
        return $offer->user_id === $user->id || (method_exists($user, 'isAdmin') && $user->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'code' => 403,
            'message' => 'Unauthorized',
            'errors' => ['authorization' => ['You can only delete your own offers']]
        ], 403));
    }
}
