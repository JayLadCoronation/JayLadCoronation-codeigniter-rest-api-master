<?php

require APPPATH.'libraries/REST_Controller.php';

class Otp extends REST_Controller{

    /**
     * This controller use to send opt and verify that otp
     * all required model loaded in autoload file.
     * config/autoload.php
     * Controller Author : Jayesh Ladva
     * Date:17-04-2025
     */
    public function __construct() {
        parent::__construct();
    }

    public function __call($method, $params) {
        api_response($this,[
            "status" => $this->response->status,
            "message" => "Invalid endpoint or method [$method]."
        ], REST_Controller::HTTP_NOT_FOUND);
    }

    public function send_post() {
        $phone_or_email = $this->security->xss_clean($this->input->post("phone_or_email"));

        $this->form_validation->set_rules("phone_or_email", "Phone or Email", "required");

        if ($this->form_validation->run() === FALSE) {
            // Validation failed
            api_response($this,[
                "status" => false,
                "message" => "Phone or Email is required"
            ], REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $otp = $this->Otp_model->generateOtp($phone_or_email);
            if(strlen($otp) > 1) {
                api_response($this,[
                    "status" => true,
                    "message" => "OTP sent successfully",
                    //"otp" => $otp  // For testing; remove in production
                ], REST_Controller::HTTP_OK);
            } else {
                api_response($this,[
                    "status" => false,
                    "message" => "Whoops!...something went wrong while sending OTP"
                ], REST_Controller::HTTP_OK);
            }
        }
    }

    public function verify_post() {
        // Clean input
        $phone_or_email = $this->security->xss_clean($this->input->post("phone_or_email"));
        $otp = $this->security->xss_clean($this->input->post("otp"));

        // Validate input
        $this->form_validation->set_rules("phone_or_email", "Phone or Email", "required");
        $this->form_validation->set_rules("otp", "OTP", "required|numeric");

        if ($this->form_validation->run() === FALSE) {
            // Validation failed
            api_response($this,[
                "status" => false,
                "message" => "Phone/Email and OTP are required"
            ], REST_Controller::HTTP_BAD_REQUEST);
        } else {
            // Verify OTP
            $verified = $this->Otp_model->verifyOtp($phone_or_email, $otp);

            if ($verified) {
                $this->Otp_model->deleteOtp($phone_or_email);
                api_response($this,[
                    "status" => true,
                    "message" => "OTP verified successfully"
                ], REST_Controller::HTTP_OK);
            } else {
                api_response($this,[
                    "status" => false,
                    "message" => "Invalid or expired OTP"
                ], REST_Controller::HTTP_UNAUTHORIZED);
            }
        }
    }
}

 ?>
