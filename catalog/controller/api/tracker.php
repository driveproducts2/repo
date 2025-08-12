<?php

class ControllerApiTracker extends Controller
{   
    public function check_last_sync()
    {
        $this->load->helper('logger');
        $this->load->helper('auth_helper');
        $this->load->helper('response');
        header("Content-Type: application/json");

        $auth_obj = new AuthHelper();
        if (!$auth_obj->authenticate($this->registry)) {
            http_response(403, ["error" => "Unathorized access"]);
        }
    
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
           http_response(405, ["error" => "Method not allowed"]);
        }

        $input = file_get_contents("php://input");
        $jsonData = gzdecode($input);
        $sync_type = json_decode($jsonData, true);

        if(isset($sync_type['module']) && !empty($sync_type['module'])) {
            $query = $this->db->query("SELECT max(tbl_max_timestmp) as last_sync FROM oc_sync_tracker WHERE module_name = '".$this->db->escape($sync_type['module'])."'");

            if($query && $query->num_rows > 0) {
               http_response(200, ["res" => $query->row['last_sync']]);
            } else {
               http_response(200, ["res" => null]);
            }
        } else {
            Logger::log("Bad request", "ERROR");
           http_response(400, ["error" => "Bad request"]);
        }
    }

    public function update_last_sync()
    {
        try {
            $this->load->helper('logger');
            $this->load->helper('auth_helper');
            $this->load->helper('response');
            header("Content-Type: application/json");
    
            $auth_obj = new AuthHelper();
            if (!$auth_obj->authenticate($this->registry)) {
                http_response(403, ["error" => "Unathorized access"]);
            }

            header("Content-Type: application/json");
        
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
               http_response(405, ["error" => "Method not allowed"]);
            }

            $input = file_get_contents("php://input");
            $jsonData = gzdecode($input);
            
            $data = json_decode($jsonData, true);

            if (isset($data['module_name']) && isset($data['rows_affected']))
            {
                $module_name = (isset($data['module_name'])) ? $this->db->escape($data['module_name']) : '';
                $rows_affected = (isset($data['rows_affected'])) ? $this->db->escape($data['rows_affected']) : '';
                $last_updated_date_time = (isset($data['last_updated_date_time'])) ? $this->db->escape($data['last_updated_date_time']) : ''; 
                $gp_db_timestamp = (isset($data['gp_db_timestamp'])) ? $this->db->escape($data['gp_db_timestamp']) : '';
                $created_date = (isset($data['created_date'])) ? $this->db->escape($data['created_date']) : '';
                
                if (!empty($last_updated_date_time)) {
                    $sql = "INSERT INTO oc_sync_tracker (module_name, rows_affected, tbl_max_timestmp, gp_db_timestmp, created_at) VALUES ('$module_name', '$rows_affected', '$last_updated_date_time', '$gp_db_timestamp', '$created_date')";
                } else {
                    $sql = "INSERT INTO oc_sync_tracker (module_name, rows_affected, gp_db_timestmp, created_at) VALUES ('$module_name', '$rows_affected', '$gp_db_timestamp', '$created_date')";
                }
                
                $this->db->query($sql);

                if($this->db->getLastId() > 0) {
                   http_response(200, ["res" => true]);
                } else {
                    Logger::log("Not able to update sync table", "ERROR");
                   http_response(500, ["error" => "Internal server error"]);
                }
            } else {
                Logger::log("Bad request", "ERROR");
               http_response(400, ["error" => "Bad request"]);
            }
        } catch (\Exception $e) {
            Logger::log("Not able to update sync table", "ERROR");
            http_response(500, ["error" => "Internal server error"]);
        }
        
    }

    public function get_max_sync_date_time(): void
    {
        $this->load->helper('logger');
        $this->load->helper('auth_helper');
        $this->load->helper('response');
        header("Content-Type: application/json");

        $auth_obj = new AuthHelper();
        if (!$auth_obj->authenticate($this->registry)) {
            Logger::log("Unauthorized access to fetch max gp time stamp module", "ERROR");
            http_response(403, ["error" => "Unathorized access"]);
        }
    
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            Logger::log("Method not allowed to fetch max gp time stamp module", "ERROR");
           http_response(405, ["error" => "Method not allowed"]);
        }

        $input = file_get_contents("php://input");
        $jsonData = gzdecode($input);
        $data = json_decode($jsonData, true);

        if (isset($data['table_name']) && !empty($data['table_name']) && isset($data['column_name']) && !empty($data['column_name'])) {
            $query = $this->db->query("SELECT MAX(".$data['column_name'].") as max_stamp FROM ".$data['table_name']);

            if($query && $query->num_rows > 0) {
               http_response(200, ["res" => $query->row['max_stamp']]);
            } else {
               http_response(200, ["res" => null]);
            }
        } else {
            Logger::log("Bad request", "ERROR");
            http_response(400, ["error" => "Bad request"]);
        }
    }
}