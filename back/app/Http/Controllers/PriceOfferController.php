<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PriceOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewPriceOfferNotification;
use App\Notifications\PriceOfferAcceptedNotification;
use App\Notifications\PriceOfferRejectedNotification;

class PriceOfferController extends Controller
{
    public function sendOffer(Request $request)
    {
        $request->validate([
            'product_id'     => 'required|exists:products,id',
            'type'           => 'required|in:sale,rent',
            'proposed_price' => 'required|numeric|min:0.1'
        ]);

        $product = Product::findOrFail($request->product_id);

        if (!$product->repricing) {
            return response()->json(['message' => __('messages.repricing_not_allowed')], 400);
        }

        if ($product->owner_id === Auth::id()) {
            return response()->json(['message' => __('messages.cannot_offer_own_product')], 400);
        }

        $existingOffer = PriceOffer::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->where('status', 'pending')
            ->first();

        if ($existingOffer) {
            return response()->json(['message' => __('messages.offer_already_pending')], 400);
        }

        $offer = PriceOffer::create([
            'user_id'        => Auth::id(),
            'product_id'     => $product->id,
            'type'           => $request->type,
            'proposed_price' => $request->proposed_price,
            'status'         => 'pending'
        ]);

        $product->owner->notify(new NewPriceOfferNotification($offer->id, $product->title, $request->proposed_price));

        return response()->json([
            'message' => __('messages.offer_sent_successfully'),
             'data' => $offer
             ], 201);
    }

    public function acceptOffer($id)
    {
        $offer = PriceOffer::findOrFail($id);

        if ($offer->product->owner_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized_action')], 403);
        }
        if ($offer->status !== 'pending') {
            return response()->json(['message' => __('messages.offer_already_responded')], 400);
        }

        $offer->update(['status' => 'accepted']);
        $offer->user->notify(new PriceOfferAcceptedNotification($offer->product->title));

        return response()->json(['message' => __('messages.offer_accepted_successfully')]);
    }

    public function rejectOffer($id)
    {
        $offer = PriceOffer::findOrFail($id);

        if ($offer->product->owner_id !== Auth::id()) {
            return response()->json(['message' => __('messages.unauthorized_action')], 403);
        }
        if ($offer->status !== 'pending') {
            return response()->json(['message' => __('messages.offer_already_responded')], 400);
        }

        $offer->update(['status' => 'rejected']);
        $offer->user->notify(new PriceOfferRejectedNotification($offer->product->title));

        return response()->json(['message' => __('messages.offer_rejected_successfully')]);
    }
}
