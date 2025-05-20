<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | BytesMe Food Delivery</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        
        body {
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('/assets/images/admin/background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        /* Content container */
        .content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            width: 360px;
        }
        
        /* Form container */
        .form-container {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            width: 478px;
        }
        
        /* Form styles */
        .form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
        }
        
        /* Input field styles */
        .input-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            width: 100%;
        }
        
        .input-label {
            font-weight: 500;
            font-size: 18px;
            color: #48505E;
        }
        
        .input-wrapper {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            background: #FFFFFF;
            border: 1px solid #D0D5DD;
            border-radius: 8px;
            box-shadow: 0px 1px 2px 0px rgba(16, 24, 40, 0.05);
            height: 55px;
        }
        
        .input-wrapper input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 16px;
            color: #101828;
            background: transparent;
            width: 100%;
        }
        
        .input-wrapper input::placeholder {
            color: #667085;
        }
        
        /* Password visibility toggle */
        .password-toggle {
            cursor: pointer;
            color: #667085;
        }
        
        /* Logo styles */
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .logo {
            width: 120px;
            height: auto;
        }
        
        /* Button styles */
        .btn-login {
            background: #FF7E1B;
            color: white;
            font-weight: 500;
            font-size: 16px;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }
        
        .btn-login:hover {
            background: #E86D0D;
        }
        
        /* Error message */
        .error-message {
            color: #E11D48;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        /* Login header */
        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #101828;
            margin-bottom: 10px;
        }
        
        .login-header p {
            font-size: 16px;
            color: #667085;
        }

        /* Password field related styles */
        .password-input-container {
            position: relative;
            width: 100%;
        }

        #togglePassword {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #667085;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="form-container">
            <div class="logo-container">
                <img src="/assets/images/admin/logo.png" alt="BytesMe Logo" class="logo">
            </div>
            
            <div class="login-header">
                <h1>Log in to Dashboard</h1>
                <p>Enter your credentials to access your admin account</p>
            </div>
            
            <form id="loginForm" class="form" action="{{ route('admin.login.submit') }}" method="POST">
                @csrf
                
                <div class="input-field">
                    <label for="adminId" class="input-label">AdminID</label>
                    <div class="input-wrapper">
                        <input type="text" id="adminId" name="username" placeholder="Enter your username" required>
                    </div>
                    @error('email')
                        <div class="error-message" style="display: block;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="input-field">
                    <label for="password" class="input-label">Password</label>
                    <div class="input-wrapper password-input-container">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span id="togglePassword">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                    @error('password')
                        <div class="error-message" style="display: block;">{{ $message }}</div>
                    @enderror
                </div>
                
                <button type="submit" class="btn-login">Log In</button>
                
                @if (session('error'))
                    <div class="error-message" style="display: block; text-align: center; margin-top: 15px;">
                        {{ session('error') }}
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                
                // Toggle icon
                if (type === 'text') {
                    togglePassword.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    `;
                } else {
                    togglePassword.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    `;
                }
            });
            
            // Form validation
            const loginForm = document.getElementById('loginForm');
            loginForm.addEventListener('submit', function(event) {
                const adminId = document.getElementById('adminId').value;
                const password = document.getElementById('password').value;
                
                if (!adminId || !password) {
                    event.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
                
                // Form will submit if all validations pass
                return true;
            });
        });
    </script>
</body>
</html>