<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Providers\DBConnService;
use Illuminate\Support\Facades\Log;
use App\Services\CredentialsValidatorService;
use Exception;

class AdminAuthController extends Controller
{
    protected CredentialsValidatorService $credentialsValidatorService;
    protected DBConnService $dbConnService;

    public function __construct(
        DBConnService $dbConnService,
        CredentialsValidatorService $credentialsValidatorService,
    )
    {
        $this->dbConnService = $dbConnService;
        $this->credentialsValidatorService = $credentialsValidatorService;
    }

    public function showAdminLoginForm()
    {
        if (Auth::check() && Auth::user()->role_type == 1) {
            return redirect()->intended('/admin/dashboard');
        }
        return view('admin.auth.login');
    }

    public function handleAdminLogin(Request $request)
    {
        $request_data = $_POST;
        $password = "";

        try {
            $username = $request_data["adminId"];
            $password = $this->credentialsValidatorService->validateAndReturnPassword($request_data);

            $conn = $this->dbConnService->getDBConn();
            $sql = "select user_id, full_name, email, password, role_type from users where user_name = ?";
            $pstm = $conn->prepare($sql);
            $pstm->bind_param("s", $username);
            $pstm->execute();
            $result = $pstm->get_result();

            if ($result->num_rows == 0) {
                Log::debug('User not found in database', [
                    'email' => $username,
                ]);
                throw new Exception("Invalid credentials");
            }

            $row = $result->fetch_assoc();
            Log::debug('User found in database', [
                'user' => $row,
            ]);

            // Check if user is admin
            if ($row['role_type'] != 1) {
                throw new Exception("Unauthorized access");
            }

            // Verify password
            if (!password_verify($password, $row['password'])) {
                throw new Exception("Invalid credentials");
            }

            $user = new User();
            $user->user_id = $row['user_id'];
            $user->full_name = $row['full_name'];
            $user->email = $row['email'];
            $user->password = $row['password'];
            $user->role_type = $row['role_type'];

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/admin/dashboard');

        } catch (Exception $e) {
            Log::error("Admin login error", [
                'error' => $e->getMessage(),
                'username' => $username ?? 'none'
            ]);
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
}