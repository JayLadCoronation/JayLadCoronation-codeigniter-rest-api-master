<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Otp_model extends CI_Model {

    /**
     * This model use for otp modules
     * like send OTP, verify OTP, delete OTP and so on
     * Controller Author : Jayesh Ladva
     * Date:17-04-2025
     */
    public function __construct() {
        parent::__construct();
    }

    public function generateOtp($phone_or_email, $expiry_minutes = 5) {
        $otp = rand(100000, 999999); // 6-digit OTP
        $expires_at = date('Y-m-d H:i:s', strtotime("+$expiry_minutes minutes"));

        // Save to DB
        $data = array(
            'phone_or_email' => $phone_or_email,
            'otp' => $otp,
            'expires_at' => strtotime($expires_at),
            'is_verified' => 0,
            'created_at' => time(),
        );

        $this->db->insert('otp_verification', $data);

        $is_sent = false;
        if (filter_var($phone_or_email, FILTER_VALIDATE_EMAIL)) {
            $userDetails = $this->User_model->getUserDetails($phone_or_email);
            $userDetails->otp = $otp;
            
            $sent = $this->Email_model->sendOtpToMail($userDetails, 'verify_otp_email');
            
            if($sent == 1) {
                $is_sent = true;
            }
        } elseif (preg_match('/^[0-9]{10,15}$/', $phone_or_email)) {
            $sent = send_sms($phone_or_email, "Your OTP is: $otp"); 
            if($sent == 1) {
                $is_sent = true;
            }
        } 

        if(!$is_sent) {
            return false;
        } else {
            return $otp;
        }
    }

    public function verifyOtp($phone_or_email, $otp) {
        $this->db->where('phone_or_email', $phone_or_email);
        $this->db->where('otp', $otp);
        $this->db->where('is_verified', 0);
        $this->db->where('expires_at >=', time());
        $query = $this->db->get('otp_verification');

        if ($query->num_rows() > 0) {
            // Mark as verified
            $this->db->where('id', $query->row()->id);
            $this->db->update('otp_verification', ['is_verified' => 1]);

            return true;
        }

        return false;
    }

    public function deleteOtp($phone_or_email)
    {
        $this->db->where('phone_or_email', $phone_or_email);
        $this->db->delete('otp_verification');
    }

    

        
}
