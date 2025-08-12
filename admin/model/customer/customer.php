<?php
class ModelCustomerCustomer extends Model {
	public function addCustomer($data) {

		//Convert phone number to original form eg. 50675622500000
		$this->load->model('tool/phone_helper');
		$telephone = (!empty($data['telephone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone']) : '';
		$fax = (!empty($data['fax'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['fax']) : '';
		$user_phone = (!empty($data['user_phone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($this->db->escape($data['user_phone'])) : '';
		$user_res_phone = (!empty($data['user_res_phone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($this->db->escape($data['user_res_phone'])) : '';
		$user_cell_phone = (!empty($data['user_cell_phone'])) ?$this->model_tool_phone_helper->formatPhoneNumberToOriginal($this->db->escape($data['user_cell_phone'])) : '';

		//Insert customer details
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer SET is_main_customer = 1, customer_group_id = '" . (int)$data['customer_group_id'] . "', customer_code = '".$this->db->escape($data['customer_code'])."', customer_name = '".$this->db->escape($data['customer_name'])."', status = '".$this->db->escape($data['status'])."', telephone = '".$this->db->escape($telephone)."', ext = '".$this->db->escape($data['ext'])."', fax = '".$this->db->escape($fax)."'");

		$customer_id = $this->db->getLastId();

		//Insert customer user details
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer SET is_main_contact = 1, customer_group_id = '" . (int)$data['customer_group_id'] . "', customer_code = '".$this->db->escape($data['customer_code'])."', customer_name = '".$this->db->escape($data['customer_name'])."', status = '".$this->db->escape($data['status'])."', title = '".$this->db->escape($data['title'])."', firstname = '".$this->db->escape($data['first_name'])."', lastname = '".$this->db->escape($data['last_name'])."', telephone = '".$this->db->escape($user_phone)."', ext = '".$this->db->escape($data['user_ext'])."', telephone_1 = '".$this->db->escape($user_res_phone)."', telephone_2 = '".$this->db->escape($user_cell_phone)."', job_title = '".$this->db->escape($data['job_title'])."', email = '".$this->db->escape($data['user_email'])."'");

		if (isset($data['address'])) {
			foreach ($data['address'] as $address) {
				if(isset($address['billing']) && !empty($address['billing'])) {
					$is_billing = 1;
				} else {
					$is_billing = '';
				}

				if(isset($address['shipping']) && !empty($address['shipping'])) {
					$is_shipping = 1;
				} else {
					$is_shipping = '';
				}
				
				$this->db->query("INSERT INTO " . DB_PREFIX . "address (customer_id, customer_number, description, firstname, lastname, company, address_1, address_2, city, postcode, country_id, zone_id, is_billing, is_shipping) 
					VALUES ('".$this->db->escape($customer_id)."', '".$this->db->escape($data['customer_code'])."', '".$this->db->escape($address['description'])."', '".$this->db->escape($address['firstname'])."', '".$this->db->escape($address['lastname'])."', '".$this->db->escape($address['company'])."', '".$this->db->escape($address['address_1'])."', '".$this->db->escape($address['address_2'])."', '".$this->db->escape($address['city'])."', '".$this->db->escape($address['postcode'])."', '".$this->db->escape($address['country_id'])."', '".$this->db->escape($address['zone_id'])."', '".$is_billing."', '".$is_shipping."')");
					$address_id = $this->db->getLastId();

				if (isset($address['default'])) {
					$address_id = $this->db->getLastId();

					$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
				}
			}
		}
		
		return $customer_id;
	}

	public function editCustomer(int $customer_id, array $data) {
		//Convert phone number to original form eg. 50675622500000
		$this->load->model('tool/phone_helper');
		$telephone = (!empty($data['telephone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone']) : '';
		$fax = (!empty($data['fax'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['fax']) : '';
		$user_phone = (!empty($data['user_phone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($this->db->escape($data['user_phone'])) : '';
		$user_res_phone = (!empty($data['user_res_phone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($this->db->escape($data['user_res_phone'])) : '';
		$user_cell_phone = (!empty($data['user_cell_phone'])) ?$this->model_tool_phone_helper->formatPhoneNumberToOriginal($this->db->escape($data['user_cell_phone'])) : '';

		//Update details in customer table
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET customer_group_id = '" . (int)$this->db->escape($data['customer_group_id']) . "', telephone = '" . $this->db->escape($telephone) . "', fax = '" . $this->db->escape($fax) . "', ext = '" . $this->db->escape($data['ext']) . "' WHERE customer_id = '" . (int)$customer_id . "'");

		//Update main contact of customer
		if(isset($data['customer_users']) && !empty($data['customer_users']) && is_numeric($data['customer_users'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET is_main_contact = '' WHERE customer_code = '".$this->db->escape($data['cust_code'])."'"); //make all main contact flag null before updating
 
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET is_main_contact = 1, telephone = '".$user_phone."', ext = '".$this->db->escape($data['user_ext'])."', telephone_1 = '".$user_res_phone."', telephone_2 = '".$user_cell_phone."', job_title = '".$this->db->escape($data['job_title'])."', email = '".$this->db->escape($data['user_email'])."' WHERE customer_id = '". (int)$data['customer_users'] ."'");
		}

		//Add/Update address of customer
		if (isset($data['address']) && !empty($data['address'])) {
			foreach ($data['address'] as $address) { 
				if(isset($address['billing']) && !empty($address['billing'])) {
					$is_billing = 1;
				} else {
					$is_billing = '';
				}

				if(isset($address['shipping']) && !empty($address['shipping'])) {
					$is_shipping = 1;
				} else {
					$is_shipping = '';
				}

				$query_add = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "address WHERE address_id = '".$address['address_id']."'");

				if($query_add && ($query_add->num_rows > 0)) {
					$this->db->query("UPDATE " . DB_PREFIX . "address SET description = '".$this->db->escape($address['description'])."', firstname = '".$this->db->escape($address['firstname'])."', lastname = '".$this->db->escape($address['lastname'])."', company = '".$this->db->escape($address['company'])."', address_1 = '".$this->db->escape($address['address_1'])."', address_2 = '".$this->db->escape($address['address_2'])."', city = '".$this->db->escape($address['city'])."', postcode = '".$this->db->escape($address['postcode'])."', country_id = '".$this->db->escape($address['country_id'])."', zone_id = '".$this->db->escape($address['zone_id'])."', is_billing = '".$is_billing."', is_shipping = '".$is_shipping."' WHERE address_id = '".$address['address_id']."'");
					$address_id = $address['address_id'];
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "address (customer_id, customer_number, description, firstname, lastname, company, address_1, address_2, city, postcode, country_id, zone_id, is_billing, is_shipping) 
					VALUES ('".$this->db->escape($customer_id)."', '".$this->db->escape($data['cust_code'])."', '".$this->db->escape($address['description'])."', '".$this->db->escape($address['firstname'])."', '".$this->db->escape($address['lastname'])."', '".$this->db->escape($address['company'])."', '".$this->db->escape($address['address_1'])."', '".$this->db->escape($address['address_2'])."', '".$this->db->escape($address['city'])."', '".$this->db->escape($address['postcode'])."', '".$this->db->escape($address['country_id'])."', '".$this->db->escape($address['zone_id'])."', '".$is_billing."', '".$is_shipping."')");
					$address_id = $this->db->getLastId();
				}

				//Update deafualt address id in customer table
				if (isset($address['default'])) {
					$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
				}
			}
		}	
	}

	public function editCustomerUser(int $customer_id, array $data):bool {
		$res = false;
		$this->load->model('tool/phone_helper');
		
		$telephone = (!empty($data['telephone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone']) : '';
		$telephone1 = (!empty($data['telephone1'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone1']) : '';
		$telephone2 = (!empty($data['telephone2'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone2']) : '';
		
		
		$sql = "UPDATE ".DB_PREFIX."customer SET title = '".$this->db->escape($data['title'])."', firstname = '".$this->db->escape($data['first_name'])."',lastname = '".$this->db->escape($data['last_name'])."',email = '".$this->db->escape($data['email'])."',user_name = '".$this->db->escape($data['username'])."',access_type = '".$this->db->escape($data['access_type'])."',status = '".$this->db->escape($data['status'])."',telephone = '".$telephone."',ext = '".$this->db->escape($data['ext'])."',telephone_1 = '".$telephone1."',telephone_2 = '".$telephone2."' WHERE customer_id = '".$customer_id."'";

		$this->db->query($sql);

		if($this->db->countAffected() == 1) {
			$res = true;
		} 

		return $res;
	}

	public function addCustomerUser($data): int {
		$this->load->model('tool/phone_helper');
		$customer_id = 0;

		$customer_data = $this->getCustomer($data['customer']);
		$telephone = (!empty($data['telephone'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone']) : '';
		$telephone1 = (!empty($data['telephone1'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone1']) : '';
		$telephone2 = (!empty($data['telephone2'])) ? $this->model_tool_phone_helper->formatPhoneNumberToOriginal($data['telephone2']) : '';
		$password = md5("Drive@2025");

		
		$this->db->query("INSERT INTO ".DB_PREFIX."customer (customer_group_id, customer_code, customer_name, customer_class_id, is_main_customer, title, firstname, lastname, email, user_name, password, telephone, telephone_1, telephone_2, ext, access_type, status) VALUES ('".$customer_data['customer_group_id']."', '".$customer_data['customer_code']."','".$customer_data['customer_name']."','".$customer_data['customer_class_id']."', '0', '".$data['title']."', '".$data['first_name']."', '".$data['last_name']."', '".$data['email']."', '".$data['username']."', '".$password."','".$telephone."','".$telephone1."','".$telephone2."','".$data['ext']."','".$data['access_type']."','".$data['status']."')");

		$last_id = $this->db->getLastId();
		if ($last_id > 0) {
			$customer_id = $last_id;
		}

		return $customer_id;
	}

	public function checkDuplicateEmail(string $email, string $type, int $customer_id = 0): bool {
		$is_duplicate = false;
		
		$sql = "SELECT customer_id FROM ".DB_PREFIX."customer WHERE email = '".$email."' AND is_main_customer != 1 AND is_deleted IS NULL";

		if($type == 'edit') {
			$sql .= " AND customer_id != '".$customer_id."'";
		}

		$query = $this->db->query($sql);
		if($query->num_rows > 0) {
			$is_duplicate = true;
		}
		
		return $is_duplicate;
	}

	public function editToken($customer_id, $token) {
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET token = '" . $this->db->escape($token) . "' WHERE customer_id = '" . (int)$customer_id . "'");
	}

	public function deleteCustomer($customer_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_activity WHERE customer_id = '" . (int)$customer_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_affiliate WHERE customer_id = '" . (int)$customer_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_approval WHERE customer_id = '" . (int)$customer_id . "'");
 		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_history WHERE customer_id = '" . (int)$customer_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$customer_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_ip WHERE customer_id = '" . (int)$customer_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");
	}

	public function deleteCustomerUsers(int $customer_user_id) {
		$this->db->query("UPDATE ".DB_PREFIX . "customer SET is_deleted = 1 WHERE customer_id = '" . (int)$customer_user_id . "'");
	}

	public function getCustomer($customer_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "' AND is_deleted IS NULL");

		return $query->row;
	}

	public function getCustomerByEmail($email) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "customer WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row;
	}
	
	public function getCustomers($data = array()) {
		//print_r($data);
		$sql = "SELECT *, c.customer_name AS name, c.customer_code, cgd.name AS customer_group FROM " . DB_PREFIX . "customer c LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id)";
		
		if (!empty($data['filter_affiliate'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "customer_affiliate ca ON (c.customer_id = ca.customer_id)";
		}		
		
		$sql .= " WHERE cgd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND is_main_customer = 1";
		
		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "customer_name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "c.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter'])) {
			$implode[] = "c.newsletter = '" . (int)$data['filter_newsletter'] . "'";
		}

		if (!empty($data['filter_customer_group_id'])) {
			$implode[] = "c.customer_group_id = '".(int)$data['filter_customer_group_id']."'";
		}

		if (!empty($data['filter_affiliate'])) {
			$implode[] = "ca.status = '" . (int)$data['filter_affiliate'] . "'";
		}
		
		if (!empty($data['filter_ip'])) {
			$implode[] = "c.customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] = "DATE(c.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " AND " . implode(" AND ", $implode);
		}

		$sort_data = array(
			'customer_name',
			'c.email',
			'customer_group',
			'c.status',
			'c.ip',
			'c.date_added'
		);
		//echo $data['sort'];
		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.customer_code";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		
		$query = $this->db->query($sql);
		
		return $query->rows;
	}

	public function getCustomersUsers($data = array()) {
		$sql = "SELECT c.customer_id, c.customer_code, c.customer_name, CONCAT(c.firstname, ' ', c.lastname) AS name, c.status FROM " . DB_PREFIX . "customer c ";	
		
		$sql .= " WHERE c.is_main_customer != 1 AND is_deleted IS NULL";
		
		$implode = array();

		if (!empty($data['filter_customer_code'])) {
			$implode[] = "c.customer_code LIKE '%" . $this->db->escape($data['filter_customer_code']) . "%'";
		}
		
		if (!empty($data['filter_customer_name'])) {
			$implode[] = "c.customer_name LIKE '%" . $this->db->escape($data['filter_customer_name']) . "%'";
		}

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "c.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (!empty($data['filter_username'])) {
			$implode[] = "c.user_name LIKE '%" . $this->db->escape($data['filter_username']) . "%'";
		}

		if (!empty($data['filter_access_type'])) {
			$implode[] = "c.access_type = '" . (int)$data['filter_access_type'] . "'";
		}

		if (!empty($data['filter_customer_group_id'])) {
			$implode[] = "c.customer_group_id = '".(int)$data['filter_customer_group_id']."'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if ($implode) {
			$sql .= " AND " . implode(" AND ", $implode);
		}

		$sort_data = array(
			'customer_code',
			'customer_name',
			'name'
		);
		//echo $data['sort'];
		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= ($data['sort'] == 'name') ? " ORDER BY CONCAT(c.firstname, ' ', c.lastname)" : " ORDER BY c." . $data['sort'];
			
		} else {
			$sql .= " ORDER BY c.customer_code";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
		
		$query = $this->db->query($sql);
		
		return $query->rows;
	}

	public function getCustomerAuto(): array {
		$result = [];
		
		$search = $this->db->escape($_GET['q']);
		if (strlen($search) >= 3) 
		{
			$query = $this->db->query("SELECT customer_id, customer_name FROM ".DB_PREFIX."customer WHERE is_main_customer = 1 AND status = 1 AND customer_name LIKE '%".$search."%'");

			if($query && ($query->num_rows > 0)) {
				foreach ($query->rows as $value) {
					$result[] = [
						"id" => $value['customer_id'],
						"text" => $value['customer_name']
					];
				}
			}
		}

		return $result;
	}

	public function getCustomerUserGroup():array {
		$res = [];
		$query = $this->db->query("SELECT `user_group_id`, `name` FROM " .DB_PREFIX. "user_customer_group");
		if($query && ($query->num_rows > 0)) {
			$res = $query->rows;
		}

		return $res;
	}

	public function getAddress($address_id) {
		$address_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "'");

		if ($address_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");

			if ($country_query->num_rows) {
				$country = $country_query->row['name'];
				$iso_code_2 = $country_query->row['code'];
				$iso_code_3 = '';
				$address_format = '';
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$address_query->row['zone_id'] . "'");

			if ($zone_query->num_rows) {
				$zone = $zone_query->row['name'];
				$zone_code = $zone_query->row['code'];
			} else {
				$zone = '';
				$zone_code = '';
			}

			return array(
				'address_id'     => $address_query->row['address_id'],
				'customer_id'    => $address_query->row['customer_id'],
				'is_billing'     => $address_query->row['is_billing'],
				'is_shipping'    => $address_query->row['is_shipping'],
				'description'    => $address_query->row['description'],
				'firstname'      => $address_query->row['firstname'],
				'lastname'       => $address_query->row['lastname'],
				'company'        => $address_query->row['company'],
				'address_1'      => $address_query->row['address_1'],
				'address_2'      => $address_query->row['address_2'],
				'postcode'       => $address_query->row['postcode'],
				'city'           => $address_query->row['city'],
				'zone_id'        => $address_query->row['zone_id'],
				'zone'           => $zone,
				'zone_code'      => $zone_code,
				'country_id'     => $address_query->row['country_id'],
				'country'        => $country,
				'iso_code_2'     => $iso_code_2,
				'iso_code_3'     => $iso_code_3,
				'address_format' => $address_format,
				'custom_field'   => json_decode($address_query->row['custom_field'], true)
			);
		}
	}

	public function getCustomerAddresses(int $customer_id) {
		$address_data = array();

		$query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "address WHERE customer_id = '".$customer_id."'");

		foreach ($query->rows as $result) {
			$address_info = $this->getAddress($result['address_id']);

			if ($address_info) {
				$address_data[$result['address_id']] = $address_info;
			}
		}

		return $address_data;
	}

	public function getAddresses($customer_id) {
		$address_data = array();

		$query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");

		foreach ($query->rows as $result) {
			$address_info = $this->getAddress($result['address_id']);

			if ($address_info) {
				$address_data[$result['address_id']] = $address_info;
			}
		}

		return $address_data;
	}

	public function getTotalCustomers($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer c";

		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(firstname, ' ', lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (isset($data['filter_newsletter']) && !is_null($data['filter_newsletter'])) {
			$implode[] = "newsletter = '" . (int)$data['filter_newsletter'] . "'";
		}

		if (!empty($data['filter_customer_group_id'])) {
			$implode[] = "customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
		}

		if (!empty($data['filter_ip'])) {
			$implode[] = "customer_id IN (SELECT customer_id FROM " . DB_PREFIX . "customer_ip WHERE ip = '" . $this->db->escape($data['filter_ip']) . "')";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$implode[] = "status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] = "DATE(date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode)." AND c.is_main_customer = 1";
		} else {
			$sql .= " WHERE c.is_main_customer = 1";
		}
		
		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalCustomerUsers($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer c";

		$implode = array();

		if (!empty($data['filter_customer_code'])) {
			$implode[] = "c.customer_code LIKE '%" . $this->db->escape($data['filter_customer_code']) . "%'";
		}

		if (!empty($data['filter_customer_name'])) {
			$implode[] = "c.customer_name LIKE '%" . $this->db->escape($data['filter_customer_name']) . "%'";
		}

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "c.email LIKE '%" . $this->db->escape($data['filter_email']) . "%'";
		}

		if (!empty($data['filter_username'])) {
			$implode[] = "c.user_name LIKE '%" . $this->db->escape($data['filter_username']) . "%'";
		}

		if (!empty($data['filter_access_type'])) {
			$implode[] = "c.access_type = '" . (int)$data['filter_access_type'] . "'";
		}

		if (!empty($data['filter_customer_group_id'])) {
			$implode[] = "c.customer_group_id = '" . (int)$data['filter_customer_group_id'] . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$implode[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode)." AND c.is_main_customer != 1";
		} else {
			$sql .= " WHERE c.is_main_customer != 1 AND is_deleted IS NULL";
		}
		
		$query = $this->db->query($sql);
		return $query->row['total'];
	}
        
	public function getAffiliateByTracking($tracking) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_affiliate WHERE tracking = '" . $this->db->escape($tracking) . "'");
							
			return $query->row;
	}
	
	public function getAffiliate($customer_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_affiliate WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row;
	}
	
	public function getAffiliates($data = array()) {
		$sql = "SELECT DISTINCT *, CONCAT(c.firstname, ' ', c.lastname) AS name FROM " . DB_PREFIX . "customer_affiliate ca LEFT JOIN " . DB_PREFIX . "customer c ON (ca.customer_id = c.customer_id)";
		
		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}		
		
		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}
		
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}
						
		$query = $this->db->query($sql . "ORDER BY name");

		return $query->rows;
	}
	
	public function getTotalAffiliates($data = array()) {
		$sql = "SELECT DISTINCT COUNT(*) AS total FROM " . DB_PREFIX . "customer_affiliate ca LEFT JOIN " . DB_PREFIX . "customer c ON (ca.customer_id = c.customer_id)";
		
		$implode = array();

		if (!empty($data['filter_name'])) {
			$implode[] = "CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}		
		
		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}
		
		return $query->row['total'];
	}

	public function getTotalAddressesByCustomerId($customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getTotalAddressesByCountryId($country_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE country_id = '" . (int)$country_id . "'");

		return $query->row['total'];
	}

	public function getTotalAddressesByZoneId($zone_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE zone_id = '" . (int)$zone_id . "'");

		return $query->row['total'];
	}

	public function getTotalCustomersByCustomerGroupId($customer_group_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer WHERE customer_group_id = '" . (int)$customer_group_id . "'");

		return $query->row['total'];
	}

	public function addHistory($customer_id, $comment) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_history SET customer_id = '" . (int)$customer_id . "', comment = '" . $this->db->escape(strip_tags($comment)) . "', date_added = NOW()");
	}

	public function getHistories($customer_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT comment, date_added FROM " . DB_PREFIX . "customer_history WHERE customer_id = '" . (int)$customer_id . "' ORDER BY date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalHistories($customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_history WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function addTransaction($customer_id, $description = '', $amount = '', $order_id = 0) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_transaction SET customer_id = '" . (int)$customer_id . "', order_id = '" . (int)$order_id . "', description = '" . $this->db->escape($description) . "', amount = '" . (float)$amount . "', date_added = NOW()");
	}

	public function deleteTransactionByOrderId($order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_transaction WHERE order_id = '" . (int)$order_id . "'");
	}

	public function getTransactions($customer_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$customer_id . "' ORDER BY date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalTransactions($customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total  FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getTransactionTotal($customer_id) {
		$query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getTotalTransactionsByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_transaction WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function addReward($customer_id, $description = '', $points = '', $order_id = 0) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_reward SET customer_id = '" . (int)$customer_id . "', order_id = '" . (int)$order_id . "', points = '" . (int)$points . "', description = '" . $this->db->escape($description) . "', date_added = NOW()");
	}

	public function deleteReward($order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_reward WHERE order_id = '" . (int)$order_id . "' AND points > 0");
	}

	public function getRewards($customer_id, $start = 0, $limit = 10) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "' ORDER BY date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalRewards($customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getRewardTotal($customer_id) {
		$query = $this->db->query("SELECT SUM(points) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getTotalCustomerRewardsByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_reward WHERE order_id = '" . (int)$order_id . "' AND points > 0");

		return $query->row['total'];
	}

	public function getIps($customer_id, $start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}
		if ($limit < 1) {
			$limit = 10;
		}

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_ip WHERE customer_id = '" . (int)$customer_id . "' ORDER BY date_added DESC LIMIT " . (int)$start . "," . (int)$limit);
		
		return $query->rows;
	}

	public function getTotalIps($customer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_ip WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getTotalCustomersByIp($ip) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_ip WHERE ip = '" . $this->db->escape($ip) . "'");

		return $query->row['total'];
	}

	public function getTotalLoginAttempts($email) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_login` WHERE `email` = '" . $this->db->escape($email) . "'");

		return $query->row;
	}

	public function deleteLoginAttempts($email) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_login` WHERE `email` = '" . $this->db->escape($email) . "'");
	}

	public function getCustomerUser(int $customer_id): array {
		$result = [];

		$query = $this->db->query("SELECT customer_id,is_main_contact, title, job_title, firstname, lastname, telephone, ext, telephone_1, telephone_2 FROM oc_customer WHERE customer_code IN (SELECT customer_code FROM oc_customer WHERE customer_id = '".$customer_id."') AND is_main_customer != 1");

		if($query->num_rows > 0) {
			$result = $query->rows;
		}
		return $result;
	}

	public function getUserById(int $user_id): array {
		$result = [];
		$query = $this->db->query("SELECT customer_id,is_main_contact, title, job_title, firstname, lastname, telephone, ext, telephone_1, telephone_2, email FROM oc_customer WHERE customer_id = '".$user_id."'");

		if($query->num_rows > 0) {
			$this->load->model('tool/phone_helper');
			$result = $query->row;
			if(!empty($result['telephone'])) {
				$result['telephone'] = $this->model_tool_phone_helper->formatPhoneNumber($result['telephone']);
			}
			if(!empty($result['telephone_1'])) {
				$result['telephone_1'] = $this->model_tool_phone_helper->formatPhoneNumber($result['telephone_1']);
			}
			if(!empty($result['telephone_2'])) {
				$result['telephone_2'] = $this->model_tool_phone_helper->formatPhoneNumber($result['telephone_2']);
			}
		}

		return $result;
	}

	public function getJobTitle():array {
		$result = [];

		$query = $this->db->query("SELECT `id`, `name` FROM `oc_job_title` ORDER BY `name` ASC");
		if($query->num_rows > 0) {
			$result = $query->rows;
		}

		return $result;
	}
}
