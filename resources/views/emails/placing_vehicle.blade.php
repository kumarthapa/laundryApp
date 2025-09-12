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


        <h1>Vehicle Placed Trip Id : <span class="highlight">{{ $data['trip_id'] ?? '' }}</span></h1>
        <p>{{ $data['message'] ?? '' }}</p>
        <p><strong>Tour Id:</strong> {{ $data['tour_id'] ?? '' }}</p>
        <p><strong>Trip Date:</strong> {{ $data['trip_date'] ?? '' }}</p>
        <p><strong>Status:</strong> {{ isset($data['status']) ? strtoupper($data['status']) : 'Placed' }}</p>
        <p><strong>Customer Name:</strong> {{ $data['customer_name'] ?? '' }}</p>
        <p><strong>Customer Code:</strong> {{ $data['customer_code'] ?? '' }}</p>

        @if (isset($data['vehicle_number']))
            <h3>Vehicle Details</h3>
            <p><strong>Vehicle Number:</strong> {{ $data['vehicle_number'] ?? '' }}</p>
            <p><strong>Vehicle Size:</strong> {{ $data['vehicle_size'] ?? '' }}</p>
            <p><strong>Vehicle Type:</strong> {{ $data['vehicle_type'] ?? '' }}</p>
        @endif
        @if (isset($data['driver_name']))
            <h3>Driver Details</h3>
            <p><strong>Driver Name:</strong> {{ $data['driver_name'] ?? '' }}</p>
            <p><strong>Driver Number:</strong> {{ $data['driver_number'] ?? '' }}</p>
        @endif
        @if (isset($data['starting_point_location']))
            <h3>Location Details</h3>
            <p><strong>Starting Location:</strong> {{ $data['starting_point_location'] ?? '' }}</p>
            <p><strong>Destination Location:</strong> {{ $data['destination_location'] ?? '' }}</p>
        @endif


        <div class="footer">
            <p>If you have any questions, feel free to contact our support team.</p>
            <p>© 2024 Nikou Logistics. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
