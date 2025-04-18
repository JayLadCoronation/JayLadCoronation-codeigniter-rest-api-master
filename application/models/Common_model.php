<?php
class Common_model extends CI_Model
{
    /**
     * This model use for common logic building like
     * get list, get single record, common insert and update function 
     * Controller Author : Jayesh Ladva
     * Date:17-04-2025
     */
    public function __construct(){
        parent::__construct();
    }
    
    public function get_list($params = [])
    {
        $table         = $params['table'] ?? '';
        $columns       = $params['columns'] ?? '*';
        $limit         = $params['limit'] ?? "";
        $offset        = $params['offset'] ?? "";
        $where_cond    = $params['where_cond'] ?? []; // ['is_active' => 1, 'gender' => 'male']
        $search        = $params['search'] ?? null;
        $searchColumns = $params['searchColumns'] ?? []; // ['first_name', 'last_name']
        $single        = $params['single'] ?? false;
        $deleted_at    = $params['deleted_at'] ?? false;
        $joins         = $params['join'] ?? [];

        if (empty($table)) {
            return ['records' => [], 'total' => 0]; // Or throw exception
        }

        // Add joins
        $joinClause = '';
        if (!empty($joins)) {
            foreach ($joins as $join) {
                $type = strtoupper($join['type'] ?? 'INNER'); // default to INNER JOIN
                $joinClause .= " $type JOIN {$join['table']} ON {$join['on']} ";
            }
        }

        $where = "WHERE 1=1"; 
        // Build WHERE clause
        if($deleted_at) {
            $where .= " AND deleted_at IS NULL";
        }

        foreach ($where_cond as $key => $value) {
            if ($value !== null) {
                $where .= " AND $key = " . $this->db->escape($value);
            }
        }

        // Search clause
        $searchClause = '';
        if (!empty($search) && !empty($searchColumns)) {
            $escapedSearch = $this->db->escape_like_str($search);
            $escapedSearch = "%$escapedSearch%";
            $likeParts = [];

            foreach ($searchColumns as $col) {
                $likeParts[] = "$col LIKE " . $this->db->escape($escapedSearch);
            }

            if (!empty($likeParts)) {
                $searchClause = " AND (" . implode(' OR ', $likeParts) . ")";
            }
        }

        //limit clause
        $limit_cond = "";
        if(!empty($offset) && !empty($limit)) {
            $limit_cond = "LIMIT {$offset}, {$limit}";
        }

        // Final Query
        $query = $this->db->query("SELECT SQL_CALC_FOUND_ROWS {$columns} FROM {$table} {$joinClause} {$where} {$searchClause} {$limit_cond}");

        //echo $this->db->last_query();
        $result = $single ? $query->row() : $query->result();
        
        $total = $this->db->query("SELECT FOUND_ROWS() AS total")->row()->total;

        return [
            'records' => $result,
            'total'   => $total
        ];
    }

    public function get_single_record($params = [])
    {
        $table         = $params['table'] ?? '';
        $where_cond       = $params['where_cond'] ?? []; 
        $deleted_at    = $params['deleted_at'] ?? false;
        $single        = true;

        if (empty($table)) {
            return ['records' => [], 'total' => 0]; // Or throw exception
        }

        $where = "WHERE 1=1"; 
        // Build WHERE clause
        if($deleted_at) {
            $where .= " AND deleted_at IS NULL";
        }

        foreach ($where_cond as $key => $value) {
            if ($value !== null) {
                $where .= " AND $key = " . $this->db->escape($value);
            }
        }

        // Final Query
        $query = $this->db->query("SELECT SQL_CALC_FOUND_ROWS * FROM $table $where");
        echo $this->db->last_query();
        $result = $query->result();

        return [
            'records' => $result,
            'total'   => 1
        ];
    }

    public function commonInsert($prepareInsertData)
    {   
        if(!empty($prepareInsertData['table'])) {
            $this->db->insert($prepareInsertData['table'], $prepareInsertData['data']);
            return $this->db->insert_id();
        }
    }

    public function commonUpdate($prepareInsertData)
    {   
        if(!empty($prepareInsertData['table'])) {
            foreach($prepareInsertData['where'] as $key => $value) {
                $this->db->where($key,$value);
            } 
            $result = $this->db->update($prepareInsertData['table'], $prepareInsertData['data']);
            //echo $this->db->last_query();
            return $result;
        }
    }
}
