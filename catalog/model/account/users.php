<?php
class ModelAccountUsers extends Model {

  public function fetchCustomerUsers(string $customer_id, string $username, string $status, bool $access_type, int $recds_per_page): array {

    $this->load->model('extension/encryption/encryption_model');
    $result = [];
    $customer_id = $this->db->escape($customer_id);
    $username = $this->db->escape($username);
    $status = $this->db->escape($status);
    $customer_id = $this->model_extension_encryption_encryption_model->decrypt_data($customer_id);

    $customer_query = $this->db->query("SELECT customer_code FROM oc_customer WHERE customer_id = '".$customer_id."' AND status = 1");
    if($customer_query->num_rows > 0) {
      $customer_code = $customer_query->row['customer_code'];
    }

    $sql = "SELECT customer_id, firstname, lastname, email FROM oc_customer WHERE is_main_customer != 1 AND customer_id != '".$customer_id."'";

    if(isset($customer_code) && !empty($customer_code)) {
      $sql .= " AND customer_code = '".$customer_code."'";
    }

    if(isset($username) && !empty($username)) {
      $sql .= " AND (email LIKE '%".$username."%' OR firstname LIKE '%".$username."%' OR lastname LIKE '%".$username."%')";
    }

    if($status == 'active') {
      $sql .= " AND status = 1";
    } elseif($status == 'inactive') {
      $sql .= " AND status = 0"; 
    }

    if($access_type === true) {
      $sql .= " AND access_type = 'Contact only'";
    }

    $sql .= " LIMIT $recds_per_page";

    $user_query = $this->db->query($sql);

    if($user_query->num_rows > 0) {
      $result = $user_query->rows;

      $final_result['final_res'] = array_map(function($customer) {
        $customer['customer_id'] = $this->model_extension_encryption_encryption_model->encrypt_data($customer['customer_id']);
        return $customer;
      }, $result);


      $final_result['count'] = $user_query->num_rows;
      $result = $final_result;
    } else {
      $result['final_res'] = '';
      $result['count'] = '';
    }
    
    return $result;
  }
}