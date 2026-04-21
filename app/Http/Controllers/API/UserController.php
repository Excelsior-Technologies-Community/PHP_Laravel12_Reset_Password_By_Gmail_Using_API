<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;

class UserController extends Controller
{
    // 1. Get Users with Search
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    // 2. Toggle User Status
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->status = !$user->status;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User status updated',
            'data' => $user
        ]);
    }

    // 3. Soft Delete User
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'User soft deleted'
        ]);
    }

    // 4. Get Trashed Users
    public function trash()
    {
        $users = User::onlyTrashed()->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    // 5. Restore User
    public function restore($id)
    {
        User::withTrashed()->findOrFail($id)->restore();

        return response()->json([
            'status' => true,
            'message' => 'User restored'
        ]);
    }

    // 6. Export Users
    public function export()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }
}