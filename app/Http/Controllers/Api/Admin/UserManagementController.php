<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\CollectorSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * Get all users with their status and recent activity
     */
    public function index()
    {
        try {
            $users = Admin::with(['role'])
                ->leftJoin('mumineen', 'admins.its_id', '=', 'mumineen.its_id')
                ->select('admins.*', 'mumineen.fullname')
                ->orderBy('admins.status')
                ->orderBy('mumineen.fullname')
                ->get();

            // Add recent session info for collectors
            $users->each(function ($user) {
                if ($user->role->name === 'collector') {
                    $recentSession = CollectorSession::where('its_id', $user->its_id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    $user->recent_session = $recentSession;
                    
                    // Check if user has any unreconciled sessions
                    $unreconciled = CollectorSession::where('its_id', $user->its_id)
                        ->where('status', 'ended')
                        ->where('reconciliation_status', 'pending')
                        ->count();
                    $user->unreconciled_sessions = $unreconciled;
                }
            });

            return response()->json([
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate a user
     */
    public function deactivate($userId)
    {
        try {
            $user = Admin::findOrFail($userId);
            
            // Check if collector has unreconciled sessions
            if ($user->role->name === 'collector') {
                $unreconciled = CollectorSession::where('its_id', $user->its_id)
                    ->where('status', 'ended')
                    ->where('reconciliation_status', 'pending')
                    ->count();
                
                if ($unreconciled > 0) {
                    return response()->json([
                        'error' => 'Cannot deactivate collector with unreconciled sessions',
                        'unreconciled_sessions' => $unreconciled
                    ], 400);
                }
            }

            $user->update(['status' => 'inactive']);

            return response()->json([
                'message' => 'User deactivated successfully',
                'user' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to deactivate user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive a user
     */
    public function archive($userId)
    {
        try {
            $user = Admin::findOrFail($userId);
            
            // Check if collector has unreconciled sessions
            if ($user->role->name === 'collector') {
                $unreconciled = CollectorSession::where('its_id', $user->its_id)
                    ->where('status', 'ended')
                    ->where('reconciliation_status', 'pending')
                    ->count();
                
                if ($unreconciled > 0) {
                    return response()->json([
                        'error' => 'Cannot archive collector with unreconciled sessions',
                        'unreconciled_sessions' => $unreconciled
                    ], 400);
                }
            }

            $user->update(['status' => 'archived']);

            return response()->json([
                'message' => 'User archived successfully',
                'user' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to archive user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a user (for reactivating collectors)
     */
    public function activate($userId)
    {
        try {
            $user = Admin::findOrFail($userId);
            
            $user->update(['status' => 'active']);

            return response()->json([
                'message' => 'User activated successfully',
                'user' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to activate user',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a collector can be activated (for when they come back)
     */
    public function checkCollectorStatus($itsId)
    {
        try {
            $user = Admin::where('its_id', $itsId)->first();
            
            if (!$user) {
                return response()->json([
                    'exists' => false,
                    'can_activate' => false
                ]);
            }

            $canActivate = in_array($user->status, ['inactive', 'archived']);
            
            // If collector exists but is inactive/archived, auto-activate them
            if ($canActivate && $user->role->name === 'collector') {
                $user->update(['status' => 'active']);
                
                return response()->json([
                    'exists' => true,
                    'can_activate' => true,
                    'activated' => true,
                    'user' => $user->fresh(),
                    'message' => 'Collector reactivated successfully'
                ]);
            }

            return response()->json([
                'exists' => true,
                'can_activate' => $canActivate,
                'user' => $user,
                'status' => $user->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check collector status',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
