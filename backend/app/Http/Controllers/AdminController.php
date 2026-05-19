<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Inquiry;
use App\Models\SiteVisit;
use App\Models\Painter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
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

    public function getDashboardStats(Request $request)
    {
        $totalUsers = User::count();
        $totalBookings = Inquiry::count();
        $totalSiteVisits = SiteVisit::count();
        
        $recentBookings = Inquiry::with('user:id,username')->orderBy('created_at', 'desc')->limit(5)->get();

        return $this->sendResponse(200, true, 'Stats fetched', [
            'totalUsers' => $totalUsers,
            'totalBookings' => $totalBookings,
            'totalSiteVisits' => $totalSiteVisits,
            'recentBookings' => $recentBookings
        ]);
    }

    public function getUsers(Request $request)
    {
        $users = User::select('id as _id', 'username', 'email', 'isAdmin', 'created_at as createdAt')->get();
        return $this->sendResponse(200, true, 'Users fetched', ['users' => $users]);
    }

    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendResponse(404, false, 'User not found');
        }
        $user->delete();
        return $this->sendResponse(200, true, 'User deleted successfully');
    }

    public function getBookings(Request $request)
    {
        $bookings = Inquiry::with(['user:id,username,email', 'painter:id,name'])->orderBy('created_at', 'desc')->get();
        return $this->sendResponse(200, true, 'Bookings fetched', ['bookings' => $bookings]);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        $booking = Inquiry::find($id);
        if (!$booking) {
            return $this->sendResponse(404, false, 'Booking not found');
        }
        
        $booking->status = $request->status;
        $booking->save();

        return $this->sendResponse(200, true, 'Booking status updated successfully', ['booking' => $booking]);
    }

    public function assignPainter(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'painterId' => 'required|string' // frontend might send painterId or painter_id, assuming painterId
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        $booking = Inquiry::find($id);
        if (!$booking) {
            return $this->sendResponse(404, false, 'Booking not found');
        }

        $painter = Painter::find($request->painterId);
        if (!$painter) {
            return $this->sendResponse(404, false, 'Painter not found');
        }

        $booking->assignedPainter = $painter->id;
        $booking->save();

        // Optional: Update painter's current_bookings array
        $currentBookings = $painter->current_bookings ?? [];
        if (!in_array($booking->id, $currentBookings)) {
            $currentBookings[] = $booking->id;
            $painter->current_bookings = $currentBookings;
            $painter->save();
        }

        return $this->sendResponse(200, true, 'Painter assigned successfully', ['booking' => $booking]);
    }

    public function getSiteVisits(Request $request)
    {
        $siteVisits = SiteVisit::with('user:id,username,email')->orderBy('created_at', 'desc')->get();
        return $this->sendResponse(200, true, 'Site visits fetched', ['siteVisits' => $siteVisits]);
    }

    public function updateSiteVisitStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        $siteVisit = SiteVisit::find($id);
        if (!$siteVisit) {
            return $this->sendResponse(404, false, 'Site visit not found');
        }
        
        $siteVisit->status = $request->status;
        $siteVisit->save();

        return $this->sendResponse(200, true, 'Site visit status updated successfully', ['siteVisit' => $siteVisit]);
    }

    public function getPainters(Request $request)
    {
        $painters = Painter::orderBy('created_at', 'desc')->get();
        return $this->sendResponse(200, true, 'Painters fetched', ['painters' => $painters]);
    }

    public function addPainter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'phone' => 'required|string|unique:painters',
            'specialization' => 'required|string',
            'experience' => 'required|numeric',
            'address' => 'required|string',
            'salary' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        $painter = Painter::create($request->all());

        return $this->sendResponse(201, true, 'Painter added successfully', ['painter' => $painter]);
    }

    public function deletePainter(Request $request, $id)
    {
        $painter = Painter::find($id);
        if (!$painter) {
            return $this->sendResponse(404, false, 'Painter not found');
        }
        $painter->delete();
        return $this->sendResponse(200, true, 'Painter deleted successfully');
    }
}
