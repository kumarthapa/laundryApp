<!DOCTYPE html>
<html>

<head>
    <title>{{ $data['title'] ?? '' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333333;
            font-size: 24px;
        }

        p {
            color: #555555;
            font-size: 16px;
            line-height: 1.5;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777777;
        }

        .highlight {
            color: #4CAF50;
        }
    </style>
</head>

<body>
    <div class="email-container">


        <h1>Hello, <span class="highlight">{{ $data['name'] ?? '' }}!</span></h1>
        <p>{{ $data['message'] ?? '' }}</p>
        <p><strong>Code:</strong> {{ $data['code'] ?? '' }}</p>
        <p><strong>Created Date:</strong> {{ $data['date'] ?? '' }}</p>
        <p><strong>Status:</strong> {{ isset($data['status']) ? strtoupper($data['status']) : '' }}</p>

        @if (isset($data['mobile_number']))
            <p><strong>Mobile number:</strong> {{ $data['mobile_number'] ?? '' }}</p>
        @endif

        @if (isset($data['user_email']))
            <p><strong>Email:</strong> {{ $data['user_email'] ?? '' }}</p>
        @endif

        @if (isset($data['role_name']))
            <p><strong>Role:</strong> {{ $data['role_name'] ?? '' }}</p>
        @endif

        @if (isset($data['username']))
            <h3>Login Details</h3>
            <p><strong>Username:</strong> {{ $data['username'] ?? '' }}</p>
            <p><strong>Password:</strong> {{ $data['password'] ?? '' }}</p>
            <a href="{{ route('auth-login') }}" title="Login Url" class="button">Login Now</a>
        @endif

        <div class="footer">
            <p>If you have any questions, feel free to contact our support team.</p>
            <p>© 2024 Nikou Logistics. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
