<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Inquiry;
use App\Models\SiteVisit;
use App\Models\Review;
use App\Models\Painter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class MainController extends Controller
{
    private function sendResponse($status, $success, $message, $data = null)
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    public function getHome(Request $request)
    {
        // Limiting to 6 as in original Node.js logic
        $services = Service::limit(6)->get();
        return $this->sendResponse(200, true, 'Home data fetched', ['services' => $services]);
    }

    public function getServices(Request $request)
    {
        // Cache for 5 mins (300 seconds)
        $services = Cache::remember('services_list', 300, function () {
            return Service::all();
        });
        return $this->sendResponse(200, true, 'Services fetched', ['services' => $services]);
    }

    public function postBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'city' => 'required|in:Delhi,Noida',
            'service_type' => 'required|string',
            'message' => 'nullable|string',
            'address' => 'required|string',
            'pincode' => ['required', 'regex:/^\d{6}$/'],
            'preferred_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        if (!in_array($request->city, ['Delhi', 'Noida'])) {
            return $this->sendResponse(400, false, 'Service available only in Delhi and Noida.');
        }

        $inquiry = new Inquiry($request->all());
        $inquiry->user_id = $request->user() ? $request->user()->id : null;
        $inquiry->save();

        return $this->sendResponse(201, true, 'Booking submitted successfully');
    }

    public function postSiteVisit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string',
            'city' => 'required|string|in:Bangalore,Delhi,Noida,Mumbai,Pune,Hyderabad,Chennai',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, 'Please fill in all fields correctly.');
        }

        $siteVisit = new SiteVisit([
            'name' => $request->name,
            'phone' => $request->phone,
            'city' => $request->city,
            'user_id' => $request->user() ? $request->user()->id : null,
        ]);
        $siteVisit->save();

        return $this->sendResponse(201, true, 'Site Visit Booked Successfully!');
    }

    public function getMyBookings(Request $request)
    {
        $userId = $request->user()->id;
        $inquiries = Inquiry::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
        $siteVisits = SiteVisit::where('user_id', $userId)->orderBy('created_at', 'desc')->get();

        return $this->sendResponse(200, true, 'User bookings fetched', [
            'inquiries' => $inquiries,
            'siteVisits' => $siteVisits
        ]);
    }

    public function getEditBooking(Request $request, $id)
    {
        $inquiry = Inquiry::where('id', $id)->where('user_id', $request->user()->id)->first();
        
        if (!$inquiry) {
            return $this->sendResponse(404, false, 'Booking not found');
        }
        if (in_array($inquiry->status, ['Completed', 'Contacted'])) {
            return $this->sendResponse(400, false, 'Cannot edit bookings that are Completed or Contacted.');
        }

        return $this->sendResponse(200, true, 'Booking details fetched', ['inquiry' => $inquiry]);
    }

    public function updateBooking(Request $request, $id)
    {
        $inquiry = Inquiry::where('id', $id)->where('user_id', $request->user()->id)->first();
        
        if (!$inquiry) {
            return $this->sendResponse(404, false, 'Booking not found');
        }
        if (in_array($inquiry->status, ['Completed', 'Contacted'])) {
            return $this->sendResponse(400, false, 'Cannot edit processing booking');
        }

        $changes = [];
        if ($inquiry->city !== $request->city) $changes['city'] = $inquiry->city;
        if ($inquiry->service_type !== $request->service_type) $changes['service_type'] = $inquiry->service_type;
        if ($inquiry->message !== $request->message) $changes['message'] = $inquiry->message;

        if (count($changes) > 0) {
            $history = $inquiry->editHistory ?? [];
            $history[] = [
                'timestamp' => now()->toIso8601String(),
                'changes' => $changes
            ];
            $inquiry->editHistory = $history;
        }

        if ($request->has('city')) $inquiry->city = $request->city;
        if ($request->has('service_type')) $inquiry->service_type = $request->service_type;
        if ($request->has('message')) $inquiry->message = $request->message;
        
        $inquiry->save();

        return $this->sendResponse(200, true, 'Booking updated');
    }

    public function deleteBooking(Request $request, $id)
    {
        $inquiry = Inquiry::where('id', $id)->where('user_id', $request->user()->id)->first();
        if ($inquiry) {
            $inquiry->delete();
        }
        return $this->sendResponse(200, true, 'Booking deleted');
    }

    public function submitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inquiryId' => 'required|string',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        $userId = $request->user()->id;
        $inquiry = Inquiry::where('id', $request->inquiryId)->where('user_id', $userId)->first();

        if (!$inquiry) {
            return $this->sendResponse(404, false, 'Booking not found');
        }

        if ($inquiry->status !== 'Completed') {
            return $this->sendResponse(400, false, 'Can only review completed bookings');
        }

        $existingReview = Review::where('inquiry_id', $request->inquiryId)->first();
        if ($existingReview) {
            return $this->sendResponse(400, false, 'You have already reviewed this service');
        }

        $review = Review::create([
            'user_id' => $userId,
            'inquiry_id' => $request->inquiryId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'isPublic' => true
        ]);

        if ($inquiry->assignedPainter) {
            $painter = Painter::find($inquiry->assignedPainter);
            if ($painter) {
                $currentRating = $painter->rating ?: 0;
                $currentCount = $painter->reviews_count ?: 0;

                $totalRating = ($currentRating * $currentCount) + $request->rating;
                $newCount = $currentCount + 1;
                $painter->rating = $totalRating / $newCount;
                $painter->reviews_count = $newCount;
                $painter->save();
            }
        }

        return $this->sendResponse(201, true, 'Review submitted successfully', ['review' => $review]);
    }

    public function getPublicReviews(Request $request)
    {
        $reviews = Cache::remember('public_reviews', 300, function () {
            return Review::with('user:id,username')
                ->where('isPublic', true)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get();
        });

        return $this->sendResponse(200, true, 'Public reviews fetched', ['reviews' => $reviews]);
    }
}
