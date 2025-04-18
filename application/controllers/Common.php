<?php
defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH.'libraries/REST_Controller.php';

class Common extends REST_Controller
{   
    /**
     * This controller use for common logic building like
     * country ,state, city contact inquiry api 
     * all required model loaded in autoload file.
     * config/autoload.php
     * Controller Author : Jayesh Ladva
     * Date:17-04-2025
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('Authorization_Token');
    }

    public function __call($method, $params) {
        api_response($this,[
            "status" => false,
            "message" => "Invalid endpoint or method [$method]."
        ], REST_Controller::HTTP_NOT_FOUND);
    }

    public function countryList_get() {
        $records = $this->Common_model->get_list(array(
            'table' => 'countries',
            'columns' => 'id,name,phonecode',
            'searchColumns' => array('name')
        ));
       
        api_response($this,[
            'status' => true,
            'total'   => $records['total'],
            'data'    => !empty($records['records']) ?$records['records'] : []
        ], REST_Controller::HTTP_OK);
    }

    public function stateList_get($country_id = NULL) {
        
        if (empty($country_id)) {
            api_response($this, [
                'status' => false,
                'message' => "Country id missing"
            ], REST_Controller::HTTP_OK);
            return;
        } else {
            $records = $this->Common_model->get_list(array(
                'table' => 'states',
                'columns' => 'id,name',
                'where_cond' => array('country_id' => $country_id),
                'searchColumns' => array('name')
            ));
       
            api_response($this,[
                'status' => true,
                'total'   => $records['total'],
                'data'    => !empty($records['records']) ?$records['records'] : []
            ], REST_Controller::HTTP_OK);
        }
    }

    public function cityList_get($state_id = NULL) {
        
        if (empty($state_id)) {
            api_response($this, [
                'status' => false,
                'message' => "State id missing"
            ], REST_Controller::HTTP_OK);
            return;
        } else {
            $records = $this->Common_model->get_list(array(
                'table' => 'cities',
                'columns' => 'id,name',
                'where_cond' => array('state_id' => $state_id),
                'searchColumns' => array('name')
            ));
       
            api_response($this,[
                'status' => true,
                'total'   => $records['total'],
                'data'    => !empty($records['records']) ?$records['records'] : []
            ], REST_Controller::HTTP_OK);
        }
    }

    public function contactInquiry_post() {
        // Set validation rules
        $this->form_validation->set_data($this->input->post());
        $this->form_validation->set_rules('first_name', 'first name', 'required|trim');
        $this->form_validation->set_rules('last_name', 'last name', 'required|trim');
        $this->form_validation->set_rules('email', 'email', 'required|valid_email|trim');
        $this->form_validation->set_rules('mobile', 'mobile', 'required|numeric|trim');
        $this->form_validation->set_rules('description', 'description', 'required|trim');

        // Run validation
        if (!$this->form_validation->run()) {
            $errors = $this->form_validation->error_array();
            $first_error = reset($errors); 
            api_response($this, [
                'status' => false,
                'message' => $first_error
            ], REST_Controller::HTTP_OK);
            return;
        } else {
            $post = $this->input->post();
            $first_name = $this->security->xss_clean($post['first_name']);
            $last_name = $this->security->xss_clean($post['last_name']);
            $email = $this->security->xss_clean($post['email']);
            $mobile = $this->security->xss_clean($post['mobile']);
            $description = $this->security->xss_clean($post['description']);

            $data['first_name'] = $first_name ?? "";
            $data['last_name'] = $last_name ?? "";
            $data['email'] = $email ?? "";
            $data['mobile'] = $mobile ?? "";
            $data['description'] = $description ?? "";
            $data['created_at'] = time();
            $data['status'] = 'new inquiry';

            //COMMON INSERT FUNCTION STARTS
            $prepareInsertData = array(
                'table' => 'inquiry_master',
                'data' => $data,
            );
            $inquiry_id = $this->Common_model->commonInsert($prepareInsertData);
            //COMMON INSERT FUNCTION STARTS

            if ($inquiry_id) {
                api_response($this,['status' => true, 'message' => 'Inquiry sent successfully', 'inquiry_id' => $inquiry_id], REST_Controller::HTTP_OK);
            } else {
                api_response($this,['status' => false, 'message' => 'Whoops!..something went wrong'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
}
