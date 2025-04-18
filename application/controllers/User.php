<?php
defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH.'libraries/REST_Controller.php';

class User extends REST_Controller
{   
    /**
     * all required model loaded in autoload file.
     * config/autoload.php
     * Controller Author : Jayesh Ladva
     * Date:17-04-2025
     */
    public function __construct() {
        parent::__construct();
        $this->load->library('Authorization_Token');
    }

    /**
     * Magic fuction to check the method
     */
    public function __call($method, $params) {
        api_response($this,[
            "status" => false,
            "message" => "Invalid endpoint or method [$method]."
        ], REST_Controller::HTTP_NOT_FOUND);
    }

    /**
     * List users
     * limit,page,search 
     */
    public function userList_get($userId = NULL) {
        // print_r($this->get());
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                // Pagination
                $limit = $this->get('limit') ?? 10;
                $page  = $this->get('page') ?? 1;
                $offset = ($page - 1) * $limit;

                // Filters
                $is_active = $this->get('is_active'); // Y or N
                $gender    = $this->get('gender');

                // Search
                $search = $this->get('search');

                // Get users from model
                if(!empty($userId)){ 
                    //$users = $this->User_model->get_users($limit, $offset, $is_active, $gender, $search, $userId);
                    $users = $this->Common_model->get_single_record([
                        'table' => 'users as u',
                        'where_cond' => array('u.is_active' => $is_active, 'u.gender' => $gender,'user_id' => $userId),
                        'user_id' => $userId,
                        'join' => array(
                            array(
                                'table' => 'countries c',
                                'on'    => 'u.country_id = c.id',
                                'type'  => 'LEFT'
                            ),
                            array(
                                'table' => 'states s',
                                'on'    => 'u.state_id = s.id',
                                'type'  => 'LEFT'
                            ),
                            array(
                                'table' => 'cities cty',
                                'on'    => 'u.city_id = cty.id',
                                'type'  => 'LEFT'
                            )
                        )
                    ]);
                } else {
                    $users = $this->Common_model->get_list(array(
                        'table' => 'users as u',
                        'columns' => 'u.*, c.name as country_name, s.name as state_name,cty.name as city_name',
                        'limit' => $limit,
                        'offset' => $offset,
                        'where_cond' => array('u.is_active' => $is_active, 'u.gender' => $gender),
                        'search' => $search,
                        'searchColumns' => array('u.first_name', 'u.last_name', 'u.email', 'u.mobile'),
                        'user_id' => $userId,
                        'join' => array(
                            array(
                                'table' => 'countries c',
                                'on'    => 'u.country_id = c.id',
                                'type'  => 'LEFT'
                            ),
                            array(
                                'table' => 'states s',
                                'on'    => 'u.state_id = s.id',
                                'type'  => 'LEFT'
                            ),
                            array(
                                'table' => 'cities cty',
                                'on'    => 'u.city_id = cty.id',
                                'type'  => 'LEFT'
                            )
                        )
                    ));
                }
                 
                $result = [];
                foreach ($users['records'] as $user) {
                    $result[] = [
                        'user_id'      => $user->user_id,
                        'first_name'   => $user->first_name ?? "",
                        'last_name'    => $user->last_name ?? "",
                        'middle_name'  => $user->middle_name ?? "",
                        'profile_pic'  => $user->profile_pic ? base_url('assets/profile_pic/' . $user->profile_pic) : "",
                        'email'        => $user->email ?? "",
                        'mobile'       => $user->mobile ?? "",
                        'gender'       => $user->gender ?? "",
                        'dob'          => $user->dob ? date('Y-m-d', $user->dob) : "",
                        'country_id'   => $user->country_id ?? "",
                        'country_name' => $user->country_name ?? "",
                        'state_id'     => $user->state_id ?? "",
                        'state_name'   => $user->state_name ?? "",
                        'city_id'      => $user->city_id ?? "",
                        'city_name'    => $user->city_name ?? "",
                        'is_active'    => $user->is_active,
                        'created_at'   => $user->created_at ? date('Y-m-d H:i:s', $user->created_at) : "",
                        'updated_at'   => $user->updated_at ? date('Y-m-d H:i:s', $user->updated_at) : "",
                        'deleted_at'   => $user->deleted_at ? date('Y-m-d H:i:s', $user->deleted_at) : "",
                    ];
                }

                api_response($this,[
                    'status' => true,
                    'message' => "user list found",
                    'total'   => $users['total'],
                    'data'    => !empty($userId) ? $result : $result
                ], REST_Controller::HTTP_OK);
            } else {
                api_response($this,$decodedToken, REST_Controller::HTTP_BAD_REQUEST);
            }
        }else {
            api_response($this,[
                "status" => false,
                "message" => "Authentication failed"
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Create user 
     */
    public function userCreate_post() {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {

                // Set validation rules
                $this->form_validation->set_data($this->input->post());
                $this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
                $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
                $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|callback_unique_email');
                $this->form_validation->set_rules('mobile', 'Mobile', 'required|numeric|callback_unique_mobile');
                $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
                $this->form_validation->set_rules('gender', 'Gender', 'required');
                $this->form_validation->set_rules('dob', 'Date of Birth', 'required|numeric');

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
                    $data = array(
                        'first_name' => $post['first_name'] ?? "",
                        'last_name' => $post['last_name'] ?? "",
                        'middle_name' => $post['middle_name'] ?? "",
                        'email' => $post['email'] ?? "",
                        'mobile' => $post['mobile'] ?? "",
                        'password' => $post['password'] ?? "",
                        'gender' => $post['gender'] ?? "",
                        'dob' => $post['dob'] ?? "",
                        'is_active' => $post['is_active'] ?? "",
                        'profile_pic' => $post['profile_pic'] ?? "",
                        'created_at' => time(),
                    );

                    if (!empty($_FILES['profile_pic']['name'])) {
                        $upload_path = 'assets/profile_pic/';

                        if (!is_dir($upload_path)) {
                            mkdir($upload_path, 0777, true); 
                        }
                        $config['upload_path']   = $upload_path;
                        $config['allowed_types'] = 'jpg|png|jpeg';
                        $config['file_name']     = time() . '_' . $_FILES['profile_pic']['name'];
                        $this->load->library('upload', $config);

                        if (!$this->upload->do_upload('profile_pic')) {
                            api_response($this,['status' => false, 'message' => $this->upload->display_errors()], REST_Controller::HTTP_BAD_REQUEST);
                            return;
                        }

                        $uploadData = $this->upload->data();
                        $data['profile_pic'] = $uploadData['file_name'];
                    }

                    $prepareInsertData = array(
                        'table' => 'users',
                        'data' => $data,
                    );
                    $user_id = $this->Common_model->commonInsert($prepareInsertData);

                    if ($user_id) {
                        api_response($this,['status' => true, 'message' => 'User created', 'user_id' => $user_id], REST_Controller::HTTP_OK);
                    } else {
                        api_response($this,['status' => false, 'message' => 'Insert failed'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
            } else {
                api_response($this,$decodedToken, REST_Controller::HTTP_BAD_REQUEST);
            }
        }else {
            api_response($this,[
                "status" => false,
                "message" => "Authentication failed"
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Update user 
     */
    public function userUpdate_post() {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                $post = $this->input->post();
                $user_id = $this->input->post('user_id');
                // Set validation rules
                $this->form_validation->set_data($this->input->post());
                $this->form_validation->set_rules('user_id', 'User Id', 'required');
                $this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
                $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
                $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|callback_unique_email['.$user_id.']');
                $this->form_validation->set_rules('mobile', 'Mobile', 'required|numeric|callback_unique_mobile['.$user_id.']');
                $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
                $this->form_validation->set_rules('gender', 'Gender', 'required');
                $this->form_validation->set_rules('dob', 'Date of Birth', 'required|numeric');

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
                    $data = array(
                        'first_name' => $post['first_name'] ?? "",
                        'last_name' => $post['last_name'] ?? "",
                        'middle_name' => $post['middle_name'] ?? "",
                        'email' => $post['email'] ?? "",
                        'mobile' => $post['mobile'] ?? "",
                        'password' => $post['password'] ?? "",
                        'gender' => $post['gender'] ?? "",
                        'dob' => $post['dob'] ?? "",
                        'is_active' => $post['is_active'] ?? "",
                        'profile_pic' => $post['profile_pic'] ?? "",
                        'updated_at' => time(),
                    );

                    if (!empty($_FILES['profile_pic']['name'])) {
                        $upload_path = 'assets/profile_pic/';

                        // Create directory if it doesn't exist
                        if (!is_dir($upload_path)) {
                            mkdir($upload_path, 0777, true); // Create folder with permissions and recursive
                        }
                        $config['upload_path']   = $upload_path;
                        $config['allowed_types'] = 'jpg|png|jpeg';
                        $config['file_name']     = time() . '_' . $_FILES['profile_pic']['name'];
                        $this->load->library('upload', $config);

                        if (!$this->upload->do_upload('profile_pic')) {
                            api_response($this,['status' => false, 'message' => $this->upload->display_errors()], REST_Controller::HTTP_BAD_REQUEST);
                            return;
                        }

                        $uploadData = $this->upload->data();
                        $data['profile_pic'] = $uploadData['file_name'];
                    }

                    //COMMON UPDATE START
                    $prepareUpdateData = array(
                        'table' => 'users',
                        'data' => $data,
                        'where' => array('user_id' => $user_id)
                    );
                    $updated = $this->Common_model->commonUpdate($prepareUpdateData);
                    //COMMON UPDATE END

                    if ($updated) {
                        api_response($this,['status' => true, 'message' => 'User updated'], REST_Controller::HTTP_OK);
                    } else {
                        api_response($this,['status' => false, 'message' => 'Update failed'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
            } else {
                api_response($this,$decodedToken, REST_Controller::HTTP_BAD_REQUEST);
            }
        }else {
            api_response($this,[
                "status" => false,
                "message" => "Authentication failed"
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Delete user - soft delete 
     * $userId
     * 
     */
    public function userDelete_delete($userId = 0) {
        $headers = $this->input->request_headers(); 
        if (isset($headers['Authorization'])) {
            $decodedToken = $this->authorization_token->validateToken($headers['Authorization']);
            if ($decodedToken['status'])
            {
                if (empty($userId)) {
                    return $this->response([
                        'status' => false,
                        'message' => 'User ID is required.'
                    ], REST_Controller::HTTP_OK);
                }
                $data['deleted_at'] = time();
                $deleted = $this->User_model->update_user($userId, $data);

                if ($deleted) {
                    api_response($this,['status' => true, 'message' => 'User deleted'], REST_Controller::HTTP_OK);
                } else {
                    api_response($this,['status' => false, 'message' => 'Delete failed'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                api_response($this,$decodedToken, REST_Controller::HTTP_BAD_REQUEST);
            }
        }else {
            api_response($this,[
                "status" => false,
                "message" => "Authentication failed"
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Loging fuction 
     * $username = email or mobile
     * $password
     */
    public function login_post() {
		
		$this->form_validation->set_rules('username', 'Username', 'required');
		$this->form_validation->set_rules('password', 'Password', 'required');
		
		if ($this->form_validation->run() == false) {
			
			$errors = $this->form_validation->error_array();
            $first_error = reset($errors); 
            api_response($this, [
                'status' => false,
                'message' => $first_error
            ], REST_Controller::HTTP_OK);
            return;

		} else {
			
			// set variables from the form
			$username = $this->input->post('username');//mobile or email
			$password = $this->input->post('password');
			$user = $this->User_model->resolveUserLogin($username, $password);
            
			if ($user) {
				// user login ok
                $token_data['user_id'] = $user->user_id;
                $token_data['email'] = $user->email;
                $token_data['mobile'] = $user->mobile;
                $tokenData = $this->authorization_token->generateToken($token_data);
                $user->access_token = $tokenData;

                $response['status'] = true;
                $response['message'] = "Login success!";
                $response['user'] = $user;

                $this->User_model->update_user($user->user_id, array('access_token'=>$tokenData));

                $this->response($response, REST_Controller::HTTP_OK); 
				
			} else {
				// login failed
                api_response($this, [
                    'status' => false,
                    'message' => 'Invalid login credentials!'
                ], REST_Controller::HTTP_OK);
                return;
			}
			
		}
		
	}

    /**
     * Check unique email while creating and updating User.
     * $email 
     * $user_id  for update
     */
    public function unique_email($email, $user_id = null) {
        $user_id = (int) $user_id;
        $this->db->where('email', $email);
        if ($user_id) {
            $this->db->where('user_id !=', $user_id); // skip current user during update
        }
        $exists = $this->db->get('users')->num_rows() > 0;
    
        if ($exists) {
            $this->form_validation->set_message('unique_email', 'The {field} already exists.');
            return false;
        }
        return true;
    }
    
    /**
     * Check unique mobile number while creating and updating User.
     * $mobile 
     * $user_id  for update
     */
    public function unique_mobile($mobile, $user_id = null) {
        $user_id = (int) $user_id;
        $this->db->where('mobile', $mobile);
        if ($user_id) {
            $this->db->where('user_id !=', $user_id);
        }
        $exists = $this->db->get('users')->num_rows() > 0;
    
        if ($exists) {
            $this->form_validation->set_message('unique_mobile', 'The {field} already exists.');
            return false;
        }
        return true;
    }

    /**
     * Send OTP to email or mobile 
     * email_mobile [string]
     */
    public function forgotPassword_post() {
        // Set validation rules
        $post = $this->input->post();
        $this->form_validation->set_data($this->input->post());
        $this->form_validation->set_rules('email_mobile', '', 'required|trim|callback_is_registerd_emai_or_mobile');
        
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
            $email_mobile = $post['email_mobile'];
            $otp = rand(100000, 999999); // 6-digit OTP
            $expires_at = date('Y-m-d H:i:s', strtotime("+5 minutes"));

            // Save to DB
            $data = array(
                'phone_or_email' => $email_mobile,
                'otp' => $otp,
                'expires_at' => strtotime($expires_at),
                'is_verified' => 0,
                'created_at' => time(),
            );

            $this->db->insert('otp_verification', $data);
            
            $userDetails = $this->User_model->getUserDetails($email_mobile);
            $userDetails->otp = $otp;
            
            $is_sent = false;
            if (filter_var($email_mobile, FILTER_VALIDATE_EMAIL)) {
                $sent = $this->Email_model->sendOtpToMail($userDetails,'forgot_password');//template_name
    
                if($sent == 1) {
                    $is_sent = true;
                }
            } elseif (preg_match('/^[0-9]{10,15}$/', $email_mobile)) {
                $sent = send_sms($email_mobile, "Your OTP is: $otp"); 
                if($sent == 1) {
                    $is_sent = true;
                }
            } 
    
            if(!$is_sent) {
                return false;
            } else {
                // return $otp;
                api_response($this, [
                    'status' => true,
                    'message' => 'OTP sent successfully'
                ], REST_Controller::HTTP_OK);
                return;
            }
          
        }
    }

    /**
     * callback function to check email or mobile registered with us or not 
     */
    public function is_registerd_emai_or_mobile($email_mobile) {
        $this->db->where('mobile', $email_mobile);
        $this->db->or_where('email', $email_mobile);
        $exists = $this->db->get('users')->num_rows() > 0;
    
        if (!$exists) {
            $this->form_validation->set_message('is_registerd_emai_or_mobile', 'Whoops!..'.$email_mobile.' is not registred with us.');
            return false;
        }
        return true;
    }
}
