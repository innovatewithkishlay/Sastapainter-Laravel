<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Inquiry;
use App\Models\SiteVisit;
use App\Models\Painter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    // Matches Node.js getDashboardStats exactly
    public function getDashboardStats(Request $request)
    {
        $totalUsers = User::count();
        $totalBookings = Inquiry::count();

        $startOfToday = now()->startOfDay();
        $todaysBookings = Inquiry::where('created_at', '>=', $startOfToday)->count();

        $pendingBookings = Inquiry::where('status', 'Pending')->count();
        $ongoingProjects = Inquiry::whereIn('status', ['Contacted', 'In Progress'])->count();
        $completedProjects = Inquiry::where('status', 'Completed')->count();
        $cancelledBookings = Inquiry::where('status', 'Cancelled')->count();

        return $this->sendResponse(200, true, 'Dashboard stats fetched', [
            'stats' => [
                'totalUsers'        => $totalUsers,
                'totalBookings'     => $totalBookings,
                'todaysBookings'    => $todaysBookings,
                'pendingBookings'   => $pendingBookings,
                'ongoingProjects'   => $ongoingProjects,
                'completedProjects' => $completedProjects,
                'cancelledBookings' => $cancelledBookings,
            ]
        ]);
    }

    // Matches Node.js getUsers with search and role filters
    public function getUsers(Request $request)
    {
        $query = User::query()->select('id as _id', 'username', 'email', 'isAdmin', 'created_at as createdAt');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('role') && $request->role) {
            if ($request->role === 'admin') {
                $query->where('isAdmin', true);
            } elseif ($request->role === 'user') {
                $query->where('isAdmin', false);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->get();
        return $this->sendResponse(200, true, 'Users fetched', ['users' => $users]);
    }

    // Matches Node.js deleteUser with admin protection
    public function deleteUser(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendResponse(404, false, 'User not found');
        }
        if ($user->isAdmin) {
            return $this->sendResponse(403, false, 'Cannot delete admin users');
        }
        $user->delete();
        return $this->sendResponse(200, true, 'User deleted successfully');
    }

    // Matches Node.js getBookings with status and search filters — uses 'inquiries' key
    public function getBookings(Request $request)
    {
        $query = Inquiry::query()->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status) {
            if ($request->status === 'Ongoing') {
                $query->whereIn('status', ['Contacted', 'In Progress']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->has('search') && $request->search) {
            $query->where('email', 'LIKE', "%{$request->search}%");
        }

        $inquiries = $query->get();
        return $this->sendResponse(200, true, 'Bookings fetched', ['inquiries' => $inquiries]);
    }

    // Matches Node.js updateBookingStatus with valid status check
    public function updateBookingStatus(Request $request, $id)
    {
        $validStatuses = ['Pending', 'Contacted', 'Scheduled', 'In_Progress', 'Inspection_Done', 'Completed', 'Cancelled'];

        if (!in_array($request->status, $validStatuses)) {
            return $this->sendResponse(400, false, 'Invalid status');
        }

        $booking = Inquiry::find($id);
        if (!$booking) {
            return $this->sendResponse(404, false, 'Booking not found');
        }

        // Push to editHistory like Node.js $push
        $history = $booking->editHistory ?? [];
        $history[] = ['timestamp' => now()->toIso8601String(), 'changes' => ['status' => $request->status]];
        $booking->editHistory = $history;
        $booking->status = $request->status;
        $booking->save();

        return $this->sendResponse(200, true, 'Booking status updated', ['booking' => $booking]);
    }

    // Matches Node.js assignPainter
    public function assignPainter(Request $request, $id)
    {
        $booking = Inquiry::find($id);
        if (!$booking) {
            return $this->sendResponse(404, false, 'Booking not found');
        }

        $painterId = $request->input('painterId');
        $booking->assignedPainter = $painterId;
        $booking->save();

        $booking->load('painter');

        return $this->sendResponse(200, true, 'Painter assigned successfully', ['booking' => $booking]);
    }

    // Matches Node.js getSiteVisits
    public function getSiteVisits(Request $request)
    {
        $siteVisits = SiteVisit::orderBy('created_at', 'desc')->get();
        return $this->sendResponse(200, true, 'Site visits fetched', ['siteVisits' => $siteVisits]);
    }

    // Matches Node.js updateSiteVisitStatus with valid status check
    public function updateSiteVisitStatus(Request $request, $id)
    {
        $validStatuses = ['Pending', 'Confirmed', 'Scheduled', 'Inspection_Done', 'Completed', 'Cancelled'];
        if (!in_array($request->status, $validStatuses)) {
            return $this->sendResponse(400, false, 'Invalid status');
        }

        $siteVisit = SiteVisit::find($id);
        if (!$siteVisit) {
            return $this->sendResponse(404, false, 'Site visit request not found');
        }

        $siteVisit->status = $request->status;
        $siteVisit->save();

        return $this->sendResponse(200, true, 'Site visit status updated', ['siteVisit' => $siteVisit]);
    }

    // Matches Node.js getPainters
    public function getPainters(Request $request)
    {
        $painters = Painter::orderBy('created_at', 'desc')->get();
        return $this->sendResponse(200, true, 'Painters fetched', ['painters' => $painters]);
    }

    // Matches Node.js addPainter with duplicate phone check
    public function addPainter(Request $request)
    {
        $existing = Painter::where('phone', $request->phone)->first();
        if ($existing) {
            return $this->sendResponse(400, false, 'Painter with this phone already exists');
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string',
            'phone'          => 'required|string',
            'specialization' => 'required|string',
            'experience'     => 'required|numeric',
            'address'        => 'required|string',
            'salary'         => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(400, false, $validator->errors()->first());
        }

        $painter = Painter::create($request->only(['name', 'phone', 'specialization', 'experience', 'address', 'salary']));
        return $this->sendResponse(201, true, 'Painter added successfully', ['painter' => $painter]);
    }

    // Matches Node.js deletePainter
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
