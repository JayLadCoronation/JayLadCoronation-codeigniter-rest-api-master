<?php
function api_response($controller, $data = [], $http_code = null) {
    $response = $data;

    if (is_array($data) && !isset($data['error_code']) && $http_code !== REST_Controller::HTTP_OK) {
        $response['error_code'] = $http_code;
    }

    $controller->response($response, $http_code);
}

function validateToken($headers) {

    if (!isset($headers['Authorization'])) {
        return api_response($this, [
            "status" => 0,
            "message" => "Authorization header missing"
        ], REST_Controller::HTTP_UNAUTHORIZED);
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    $decoded = validate_jwt($token, 'your_secret_key');

    if (!$decoded) {
        return api_response($this, [
            "status" => 0,
            "message" => "Invalid or expired token"
        ], REST_Controller::HTTP_UNAUTHORIZED);
    }
}

function send_sms($mobile ="", $message ="") {
        
    $sid = "XXXXXXXXXXXXXX";
    $token = "XXXXXXXXXXXXXX";
    $twilio_number = "+XXXXXXXXXXXXXX";

    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
    
    $data = [
        "To" => "+91".$mobile,
        "From" => $twilio_number,
        "Body" => $message
    ];

    $headers = [
        "Authorization: Basic " . base64_encode("$sid:$token"),
        "Content-Type: application/x-www-form-urlencoded"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return true;
}
