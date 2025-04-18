<?php
class User_model extends CI_Model
{
    /**
     * This model use for user related opartions like
     * get users list, user details, user insert and update,
     * Controller Author : Jayesh Ladva
     * Date:17-04-2025
     */
    public function __construct(){
        parent::__construct();
    }
    private $table = 'users';

    public function getAllUsers() {
        $this->db->where('deleted_at', NULL);
        $user =  $this->db->get($this->table)->result();
    }

    public function insert_user($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_user($id, $data) {
        $this->db->where('user_id', $id);
        return $this->db->update($this->table, $data);
    }

    public function getUserDetails($phone_or_email) {
        $this->db->where('deleted_at', NULL);
        $this->db->or_where('email', $phone_or_email);
        $this->db->or_where('mobile', $phone_or_email);
        $this->db->or_where('user_id', $phone_or_email);
        return $user =  $this->db->get($this->table)->row();
    }

    public function resolveUserLogin($username, $password) {
		
		$this->db->select('user_id,first_name,last_name,middle_name,profile_pic,email,mobile,password,access_token,gender,dob,is_active,created_at');
		$this->db->from('users');
		$this->db->where('email', $username);
        $this->db->or_where('mobile', $username);
        $this->db->where('password', $password);
		return $this->db->get()->row();
		
	}

    
    // public function get_users($limit, $offset, $is_active = null, $gender = null, $search = null,$userId = 0)
    // {
    //     // Base where clause
    //     $where = "WHERE deleted_at IS NULL";
    //     if ($is_active !== null) {
    //         $where .= " AND is_active = " . $this->db->escape($is_active);
    //     }
    //     if ($gender !== null) {
    //         $where .= " AND gender = " . $this->db->escape($gender);
    //     }

    //     if (!empty($user_id)) {
    //         $where .= " AND user_id = " . $userId;
    //     }

    //     $searchClause = '';
    //     $searchColumns = ['first_name', 'last_name', 'email', 'mobile'];
        
    //     if (!empty($search) && !empty($searchColumns)) {
    //         $escapedSearch = $this->db->escape_like_str($search);
    //         $escapedSearch = "%$escapedSearch%";
    //         $likeParts = [];
    //         foreach ($searchColumns as $column) {
    //             $likeParts[] = "$column LIKE " . $this->db->escape($escapedSearch);
    //         }
        
    //         if (!empty($likeParts)) {
    //             $searchClause = " AND (" . implode(' OR ', $likeParts) . ")";
    //         }
    //     }

    //     // Final query
    //     $query = $this->db->query("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table} $where $searchClause LIMIT $offset, $limit");
    //     // echo $this->db->last_query();
    //     if (!empty($user_id)) { 
    //         $users = $query->row();
    //     } else {
    //         $users = $query->result();
    //     }

    //     // Get total count
    //     $totalQuery = $this->db->query("SELECT FOUND_ROWS() as total");
    //     $total = $totalQuery->row()->total;

    //     return [
    //         'users' => $users,
    //         'total' => $total
    //     ];
    // }
}
