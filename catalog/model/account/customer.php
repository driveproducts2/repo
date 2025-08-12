<?php

require_once DIR_ROOT . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class ModelAccountCustomer extends Model
{

	public function addCustomer($data)
	{
		//var_dump($data['customer_group_id'],
		//$this->config->get('config_customer_group_display')); die;
		if (isset($data['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($data['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $data['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$this->load->model('account/customer_group');

		$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

		$this->db->query("INSERT INTO " . DB_PREFIX . "customer SET customer_group_id = '" . $data['customer_group_id'] . "', title = '" . $data['title'] . "', language_id = '" . $data['language'] . "', firstname = '" . $data['firstname'] . "', lastname = '" . $data['lastname'] . "', company = '" . $data['company'] . "', email = '" . $data['email'] . "', telephone = '" . $data['phone'] . "', ext = '" . $data['ext'] . "', fax = '" . $data['fax'] . "'");

		$customer_id = $this->db->getLastId();

		//var_dump($customer_group_info['approval']); die;

		if ($customer_group_info['approval']) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_approval` SET customer_id = '" . (int)$customer_id . "', type = 'customer', date_added = NOW()");
		}

		$this->db->query("INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . $customer_id . "',
		firstname = '" . $data['firstname'] . "', lastname = '" . $data['lastname'] . "', company = '" . $data['company'] . "',address_1 = '" . $data['address'] . "',address_2 = '" . $data['address2'] . "',city = '" . $data['city'] . "',postcode = '" . $data['zip'] . "',country_id = '" . $data['country'] . "',zone_id = '" . $data['state'] . "'");

		$address_id = $this->db->getLastId();

		$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . $address_id . "' WHERE customer_id = '" . $customer_id . "' LIMIT 1");

		return $customer_id;
	}

	public function editCustomer($customer_id, $data)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['account']) ? json_encode($data['custom_field']['account']) : '') . "' WHERE customer_id = '" . (int)$customer_id . "'");
	}

	public function editPassword($email, $password)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET salt = '" . $this->db->escape($salt = token(9)) . "', password = '" . $this->db->escape(sha1($salt . sha1($salt . sha1($password)))) . "', code = '' WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");
	}

	public function editAddressId($customer_id, $address_id)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
	}

	public function editCode($email, $code)
	{
		$this->db->query("UPDATE `" . DB_PREFIX . "customer` SET code = '" . $this->db->escape($code) . "' WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");
	}

	public function editNewsletter($newsletter)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET newsletter = '" . (int)$newsletter . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");
	}

	public function getCustomer($customer_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row;
	}

	public function getCustomerByEmail($email)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row;
	}

	public function getCustomerByCode($code)
	{
		$query = $this->db->query("SELECT customer_id, firstname, lastname, email FROM `" . DB_PREFIX . "customer` WHERE code = '" . $this->db->escape($code) . "' AND code != ''");

		return $query->row;
	}

	public function getCustomerByToken($token)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE token = '" . $this->db->escape($token) . "' AND token != ''");

		$this->db->query("UPDATE " . DB_PREFIX . "customer SET token = ''");

		return $query->row;
	}

	public function getTotalCustomersByEmail($email)
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row['total'];
	}

	public function addTransaction($customer_id, $description, $amount = '', $order_id = 0)
	{
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_transaction SET customer_id = '" . (int)$customer_id . "', order_id = '" . (float)$order_id . "', description = '" . $this->db->escape($description) . "', amount = '" . (float)$amount . "', date_added = NOW()");
	}

	public function deleteTransactionByOrderId($order_id)
	{
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_transaction WHERE order_id = '" . (int)$order_id . "'");
	}

	public function getTransactionTotal($customer_id)
	{
		$query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getTotalTransactionsByOrderId($order_id)
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_transaction WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getRewardTotal($customer_id)
	{
		$query = $this->db->query("SELECT SUM(points) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}

	public function getIps($customer_id)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_ip` WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->rows;
	}

	public function addLoginAttempt($email)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_login WHERE email = '" . $this->db->escape(utf8_strtolower((string)$email)) . "'");

		if (!$query->num_rows) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "customer_login SET email = '" . $this->db->escape(utf8_strtolower((string)$email)) . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', total = 1, date_added = '" . $this->db->escape(date('Y-m-d H:i:s')) . "', date_modified = '" . $this->db->escape(date('Y-m-d H:i:s')) . "'");
		} else {
			$this->db->query("UPDATE " . DB_PREFIX . "customer_login SET total = (total + 1), date_modified = '" . $this->db->escape(date('Y-m-d H:i:s')) . "' WHERE customer_login_id = '" . (int)$query->row['customer_login_id'] . "'");
		}
	}

	public function getLoginAttempts($email)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_login` WHERE email = '" . $this->db->escape(utf8_strtolower($email)) . "'");

		return $query->row;
	}

	public function deleteLoginAttempts($email)
	{
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_login` WHERE email = '" . $this->db->escape(utf8_strtolower($email)) . "'");
	}

	public function addAffiliate($customer_id, $data)
	{
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_affiliate SET `customer_id` = '" . (int)$customer_id . "', `company` = '" . $this->db->escape($data['company']) . "', `website` = '" . $this->db->escape($data['website']) . "', `tracking` = '" . $this->db->escape(token(64)) . "', `commission` = '" . (float)$this->config->get('config_affiliate_commission') . "', `tax` = '" . $this->db->escape($data['tax']) . "', `payment` = '" . $this->db->escape($data['payment']) . "', `cheque` = '" . $this->db->escape($data['cheque']) . "', `paypal` = '" . $this->db->escape($data['paypal']) . "', `bank_name` = '" . $this->db->escape($data['bank_name']) . "', `bank_branch_number` = '" . $this->db->escape($data['bank_branch_number']) . "', `bank_swift_code` = '" . $this->db->escape($data['bank_swift_code']) . "', `bank_account_name` = '" . $this->db->escape($data['bank_account_name']) . "', `bank_account_number` = '" . $this->db->escape($data['bank_account_number']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['affiliate']) ? json_encode($data['custom_field']['affiliate']) : json_encode(array())) . "', `status` = '" . (int)!$this->config->get('config_affiliate_approval') . "'");

		if ($this->config->get('config_affiliate_approval')) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_approval` SET customer_id = '" . (int)$customer_id . "', type = 'affiliate', date_added = NOW()");
		}
	}

	public function editAffiliate($customer_id, $data)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "customer_affiliate SET `company` = '" . $this->db->escape($data['company']) . "', `website` = '" . $this->db->escape($data['website']) . "', `commission` = '" . (float)$this->config->get('config_affiliate_commission') . "', `tax` = '" . $this->db->escape($data['tax']) . "', `payment` = '" . $this->db->escape($data['payment']) . "', `cheque` = '" . $this->db->escape($data['cheque']) . "', `paypal` = '" . $this->db->escape($data['paypal']) . "', `bank_name` = '" . $this->db->escape($data['bank_name']) . "', `bank_branch_number` = '" . $this->db->escape($data['bank_branch_number']) . "', `bank_swift_code` = '" . $this->db->escape($data['bank_swift_code']) . "', `bank_account_name` = '" . $this->db->escape($data['bank_account_name']) . "', `bank_account_number` = '" . $this->db->escape($data['bank_account_number']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['affiliate']) ? json_encode($data['custom_field']['affiliate']) : json_encode(array())) . "' WHERE `customer_id` = '" . (int)$customer_id . "'");
	}

	public function getAffiliate($customer_id)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_affiliate` WHERE `customer_id` = '" . (int)$customer_id . "'");

		return $query->row;
	}

	public function getAffiliateByTracking($tracking)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_affiliate` WHERE `tracking` = '" . $this->db->escape($tracking) . "'");

		return $query->row;
	}

	public function getCountry(): array
	{
		$query = $this->db->query("SELECT `country_id`, `name` FROM `" . DB_PREFIX . "country` WHERE `status` = 1");
		if ($query->num_rows > 0) {
			return $query->rows;
		}
		return [];
	}

	public function getState(int $country_id): array
	{
		$query = $this->db->query("SELECT `id`, `region_en` FROM `" . DB_PREFIX . "regions` WHERE `country_id` = '$country_id'");
		if ($query->num_rows > 0) {
			return $query->rows;
		}
		return [];
	}

	//Added to fetch logged in user details
	public function getUserDetail(int $user_id): array
	{
		$res = [];
		$this->load->model('tool/phone_helper');

		if(isset($user_id) && !empty($user_id) && is_int($user_id)) {
			$query = $this->db->query("SELECT `customer_code`, `customer_name`, `title`, `firstname`, `lastname`, `email`, `telephone` FROM `".DB_PREFIX."customer` WHERE `customer_id` = '".$user_id."'");

			if($query->num_rows > 0) {
				$res['user'] = $query->row;
				$res['user']['telephone'] = $this->model_tool_phone_helper->formatPhoneNumber($res['user']['telephone']);

				$customer_code = $query->row['customer_code'];
				$query_account = $this->db->query("SELECT `address1`, `address2`, `country`, `city`, `state`, `zip`, `telephone`, `ext`, `fax`, `telephone_1` FROM `".DB_PREFIX."customer` WHERE `customer_code` = '".$customer_code."' AND `is_main_customer` = 1");

				if($query_account->num_rows > 0) {
					$res['customer'] = $query_account->row;

					$res['customer']['telephone'] = $this->model_tool_phone_helper->formatPhoneNumber($res['customer']['telephone']);
					$res['customer']['fax'] = $this->model_tool_phone_helper->formatPhoneNumber($res['customer']['fax']);
				}
			}
		}
		
		return $res;
	}

	//Update customer user details in entered in billing address
	public function updateUserDetails(int $user_id, array $user_data)
	{
		$this->load->model('tool/phone_helper');
		$email = trim($this->db->escape($user_data['email']));
		$title = trim($this->db->escape($user_data['title']));
		$firstname = trim($this->db->escape($user_data['firstname']));
		$lastname = trim($this->db->escape($user_data['lastname']));
		$cellphone = $this->model_tool_phone_helper->formatPhoneNumberToOriginal(trim($this->db->escape($user_data['cellphone'])));

		$sql = "UPDATE ".DB_PREFIX."customer SET email = '".$email."', title = '".$title."', firstname = '".$firstname."', lastname = '".$lastname."', telephone = '".$cellphone."' WHERE customer_id = '".$user_id."' LIMIT 1";
		$this->db->query($sql);
	}

	// Add sales person in opencart database from GP database if missing 
	public function insertSalesPerson(): array
	{
		// Load sql srv model
		$res_arr = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			//Query to fetch records from customer class table
			$query = "SELECT SLPRSNID, SLPRSNFN, SPRSNSMN, SPRSNSLN, ADDRESS1, ADDRESS2, ADDRESS3, CITY, STATE, ZIP, COUNTRY, PHONE1, PHONE2, PHONE3, FAX, INACTIVE, SALSTERR FROM RM00301";
			$result = sqlsrv_query($connection, $query);

			// Check for errors in the query
			if ($result === false) {
				$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
				$res_arr = ['error' => 'Error while fetching records'];
				return $res_arr;
			} else {
				// Process the query result as needed
				$ins_count = 0;
				$misng_count = 0;
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$mysql_query = $this->db->query("SELECT sales_person_id FROM oc_sales_person WHERE sales_person_id = '" . trim($this->db->escape($row['SLPRSNID'])) . "'");

					if ($mysql_query->num_rows == 0) {
						$misng_count++;
						$this->db->query("INSERT INTO oc_sales_person (sales_person_id, fname, mname, lname, address1, address2, address3, city, state, country, zip, phone1, phone2, phone3, fax, status, sales_territory) VALUES ('" . trim($this->db->escape($row['SLPRSNID'])) . "', '" . trim($this->db->escape($row['SLPRSNFN'])) . "', '" . trim($this->db->escape($row['SPRSNSMN'])) . "', '" . trim($this->db->escape($row['SPRSNSLN'])) . "', '" . trim($this->db->escape($row['ADDRESS1'])) . "', '" . trim($this->db->escape($row['ADDRESS2'])) . "', '" . trim($this->db->escape($row['ADDRESS3'])) . "', '" . trim($this->db->escape($row['CITY'])) . "', '" . trim($this->db->escape($row['STATE'])) . "',  '" . trim($this->db->escape($row['COUNTRY'])) . "', '" . trim($this->db->escape($row['ZIP'])) . "', '" . trim($this->db->escape($row['PHONE1'])) . "', '" . trim($this->db->escape($row['PHONE2'])) . "', '" . trim($this->db->escape($row['PHONE3'])) . "', '" . trim($this->db->escape($row['FAX'])) . "', '" . trim($this->db->escape($row['INACTIVE'])) . "', '" . trim($this->db->escape($row['SALSTERR'])) . "')");

						if ($this->db->getLastId() > 0) {
							$ins_count++;
						}
					}
				}

				// Free result and close connection to database after processing
				sqlsrv_free_stmt($result);
				$this->model_extension_sqlsrv_connect_ms_sql->close();
				$res_arr = ['ins_count' => $ins_count, 'misng_count' => $misng_count];
				return $res_arr;
			}
		} else {
			$this->log->write('Database Connection Error: Unable to connect');
			$res_arr = ['error' => 'Error while connecting to database'];
			return $res_arr;
		}
	}

	//Insert customer class and related customer
	public function insertCustomerClass(): array
	{	
		ini_set('max_execution_time', 600);
		$res_arr = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect(); //connect to GP SQL
		
		if ($connection) {
			//Fetch all records from customer class in GP
			$query = "SELECT CLASSID, CLASDSCR, SLPRSNID, INACTIVE FROM RM00201";
			$result = sqlsrv_query($connection, $query);

			if ($result === false) {
				$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
				$res_arr = ['code' => '500', 'msg' => 'Enable to fetch records from GP'];
				return $res_arr;
			} else {
				$ins_count = 0;
				$misng_count = 0;
				$cust_count = 0;
				$customer_id_arr = [];

				//iterage from each m-group	
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) { //while 1 
					$var_ClassId = trim($this->db->escape($row['CLASSID']));
					$customer_code_ids = array();

					//fetch all customer code from webstore db related to that customer class
					$query_select_exist_cust = $this->db->query("SELECT customer_code, customer_group_id FROM oc_customer WHERE customer_group_id = '" . $var_ClassId . "'");

					if ($query_select_exist_cust === false) {
						$this->log->write("Query failed: " . $this->db->error, true);
					} else {
						if ($query_select_exist_cust->num_rows > 0) {
							foreach ($query_select_exist_cust->rows as $exist_cust_row) {
								$customer_code_ids[] = trim($this->db->escape($exist_cust_row['customer_code']));
							}
						}
					}

					//fetch all customer code from GP db related to that customer class
					$query_SelectCustomers = "SELECT CUSTNMBR, CUSTNAME, CUSTCLAS, CNTCPRSN, ADRSCODE, ADDRESS1, ADDRESS2, COUNTRY, CITY, STATE, ZIP, PHONE1, PHONE2, FAX, PRBTADCD, PRSTADCD, STADDRCD, SLPRSNID, INACTIVE FROM RM00101 WHERE CUSTCLAS = ?";

					$params = array($var_ClassId);

					$result_SelectCustomers = sqlsrv_query($connection, $query_SelectCustomers, $params);
					if ($result_SelectCustomers === false) {
						$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					} else {
						while ($customer_row = sqlsrv_fetch_array($result_SelectCustomers, SQLSRV_FETCH_ASSOC)) {
							$cust_escape = trim($this->db->escape($customer_row['CUSTNMBR'])); //while 2
							$status = ['0' => '1', '1' => '0'];
							if (!in_array($cust_escape, $customer_code_ids, true)) {
								//insert customer in webstore db if not present
								if(!empty(trim($this->db->escape($customer_row['COUNTRY'])))) {
									$query_country = $this->db->query("SELECT country_id FROM oc_country WHERE name = '".trim($this->db->escape($customer_row['COUNTRY']))."' OR `code` = '".trim($this->db->escape($customer_row['COUNTRY']))."' LIMIT 1");
									$country_id = isset($query_country->row['country_id']) ? $query_country->row['country_id'] : null;
								} else {
									$country_id = null;
								}
								
								if(!empty(trim($this->db->escape($customer_row['STATE']))) && (!empty($country_id))) {
									$query_state = $this->db->query("SELECT id FROM oc_regions WHERE country_id = ".$country_id." AND (region LIKE '%".trim($this->db->escape($customer_row['STATE']))."%' COLLATE utf8_general_ci OR region_en = '".trim($this->db->escape($customer_row['STATE']))."' COLLATE utf8_general_ci) LIMIT 1");
									$state_id = isset($query_state->row['id']) ? $query_state->row['id'] : null;
								} else {
									$state_id = null;
								}

								$this->db->query("INSERT INTO oc_customer (customer_code, customer_name, customer_class_id, is_main_customer, contact_person, address_code, address1, address2, country, city, zip, state, telephone, telephone_1, fax,primary_billing_code, primary_shipping_code, primary_standard_code, sales_person_id, inactive, status) VALUES ('" . trim($this->db->escape($customer_row['CUSTNMBR'])) . "', '" . trim($this->db->escape($customer_row['CUSTNAME'])) . "', '" . trim($this->db->escape($customer_row['CUSTCLAS'])) . "', '1', '" . trim($this->db->escape($customer_row['CNTCPRSN'])) . "','" . trim($this->db->escape($customer_row['ADRSCODE'])) . "', '" . trim($this->db->escape($customer_row['ADDRESS1'])) . "', '" . trim($this->db->escape($customer_row['ADDRESS2'])) . "', '" . $country_id . "', '" . trim($this->db->escape($customer_row['CITY'])) . "', '" . trim($this->db->escape($customer_row['ZIP'])) . "', '" . $state_id . "', '" . trim($this->db->escape($customer_row['PHONE1'])) . "', '" . trim($this->db->escape($customer_row['PHONE2'])) . "', '" . trim($this->db->escape($customer_row['FAX'])) . "', '" . trim($this->db->escape($customer_row['PRBTADCD'])) . "', '" . trim($this->db->escape($customer_row['PRSTADCD'])) . "', '" . trim($this->db->escape($customer_row['STADDRCD'])) . "', '" . trim($this->db->escape($customer_row['SLPRSNID'])) . "', '" . trim($this->db->escape($customer_row['INACTIVE'])) . "', '" . trim($this->db->escape($status[$customer_row['INACTIVE']])) . "')");

								$last_ins_cust_id = $this->db->getLastId();
								//$customer_id_arr[] = $last_ins_cust_id;

								if ($last_ins_cust_id > 0) {

									$cust_count++;
									$custNmb = trim($customer_row['CUSTNMBR']);
									//Get all address related to that customer from GP db
									$cust_addrs_query = "SELECT CUSTNMBR, ADRSCODE, SLPRSNID, CNTCPRSN, ADDRESS1, ADDRESS2, COUNTRY, CITY, STATE, ZIP, PHONE1, PHONE2, FAX, ShipToName FROM RM00102 WHERE CUSTNMBR = ?";
									$params = array($custNmb);

									$result_cust_addrs = sqlsrv_query($connection, $cust_addrs_query, $params);

									if ($result_cust_addrs === false) {
										$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
									} else {
										while ($customer_addrs = sqlsrv_fetch_array($result_cust_addrs, SQLSRV_FETCH_ASSOC)) { //while 3
											//insert address found in webstore db
											if(!empty(trim($this->db->escape($customer_addrs['COUNTRY'])))) {
												$query_country = $this->db->query("SELECT country_id FROM oc_country WHERE name = '".trim($this->db->escape($customer_addrs['COUNTRY']))."' OR `code` = '".trim($this->db->escape($customer_addrs['COUNTRY']))."' LIMIT 1");
												$country_id = isset($query_country->row['country_id']) ? $query_country->row['country_id'] : null;
											} else {
												$country_id = null;
											}
											
											if(!empty(trim($this->db->escape($customer_addrs['STATE']))) && (!empty($country_id))) {
												$query_state = $this->db->query("SELECT id FROM oc_regions WHERE country_id = ".$country_id." AND (region LIKE '%".trim($this->db->escape($customer_addrs['STATE']))."%' COLLATE utf8_general_ci OR region_en = '".trim($this->db->escape($customer_addrs['STATE']))."' COLLATE utf8_general_ci) LIMIT 1");
												$state_id = isset($query_state->row['id']) ? $query_state->row['id'] : null;
											} else {
												$state_id = null;
											}
											

											$this->db->query("INSERT INTO oc_address (customer_id, customer_number, address_code, sales_person_id, contact_person, address_1, address_2, city, postcode, country_id, zone_id, phone1, phone2, fax, ship_to_name) VALUES ('" . $last_ins_cust_id . "', '" . trim($this->db->escape($customer_addrs['CUSTNMBR'])) . "', '" . trim($this->db->escape($customer_addrs['ADRSCODE'])) . "', '" . trim($this->db->escape($customer_addrs['SLPRSNID'])) . "', '" . trim($this->db->escape($customer_addrs['CNTCPRSN'])) . "', '" . trim($this->db->escape($customer_addrs['ADDRESS1'])) . "', '" . trim($this->db->escape($customer_addrs['ADDRESS2'])) . "', '" . trim($this->db->escape($customer_addrs['CITY'])) . "', '" . trim($this->db->escape($customer_addrs['ZIP'])) . "', '" . trim($this->db->escape($country_id)) . "', '" . trim($this->db->escape($state_id)) . "', '" . trim($this->db->escape($customer_addrs['PHONE1'])) . "', '" . trim($this->db->escape($customer_addrs['PHONE2'])) . "', '" . trim($this->db->escape($customer_addrs['FAX'])) . "', '" . trim($this->db->escape($customer_addrs['ShipToName'])) . "')");
										} //while 3 ends here 
										sqlsrv_free_stmt($result_cust_addrs);
									}
								}
							}
						} //while 2 ends here
						sqlsrv_free_stmt($result_SelectCustomers);
					}

					$mysql_query = $this->db->query("SELECT class_id FROM oc_customer_group WHERE class_id = '" . trim($this->db->escape($row['CLASSID'])) . "'");

					if ($mysql_query === false) {
						$this->log->write("Query failed: " . $this->db->error, true);
					} else {
						if ($mysql_query->num_rows == 0) {
							$misng_count++;
							$this->db->query("INSERT INTO oc_customer_group (class_id, class_description, sales_person, inactive) VALUES ('" . trim($this->db->escape($row['CLASSID'])) . "', '" . trim($this->db->escape($row['CLASDSCR'])) . "', '" . trim($this->db->escape($row['SLPRSNID'])) . "', '" . trim($this->db->escape($row['INACTIVE'])) . "')");
							$cust_grp_id = $this->db->getLastId();

							if ($cust_grp_id > 0) {
								$ins_count++;
								$this->db->query("INSERT INTO oc_customer_group_description (customer_group_id, language_id, name, description) VALUES ('" . $cust_grp_id . "', '" . (int)$this->config->get('config_language_id') . "', '" . trim($this->db->escape($row['CLASSID'])) . "', '" . trim($this->db->escape($row['CLASDSCR'])) . "')");
							}
						}
					}
				} //while 1 ends here

				//Newly added to update customer group id in customer table
				//foreach ($customer_id_arr as $arr_element) {
				$this->db->query("UPDATE oc_customer AS c JOIN oc_customer_group AS cg ON c.customer_class_id = cg.class_id SET c.customer_group_id = cg.customer_group_id");
				//}
				
				// Free result and close connection to database after processing
				sqlsrv_free_stmt($result);
				$this->model_extension_sqlsrv_connect_ms_sql->close(); //close connection to SQL database
				$res_arr = ["code" => "200", "msg" => "New customer groups records inserted : $ins_count, New customer records inserted : $cust_count"];
				return $res_arr;
			}
		} else {
			$this->log->write('Database Connection Error: Unable to connect');
			$res_arr = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $res_arr;
		}
	}

	//method to add users of webstore
	public function addCustomerUsers()
	{
		$filePath = $_FILES['file']['tmp_name'];
		$spreadsheet = IOFactory::load($filePath);
		$worksheet = $spreadsheet->getActiveSheet();

		$dataArray = [];
		$headers = [];

		//get all customer group id from customer code
		$cust_grp_id_arr = [];
		$getCustGrpId = $this->db->query("SELECT customer_group_id, customer_code, customer_class_id FROM oc_customer WHERE is_main_customer = 1");
		foreach($getCustGrpId->rows as $value) {
			$cust_grp_id_arr[$value['customer_code']]['cust_grp_id'] = $value['customer_group_id'];
			$cust_grp_id_arr[$value['customer_code']]['cust_cls_id'] = $value['customer_class_id'];
		}
		
		$beforeQuery = $this->db->query("SELECT COUNT(*) AS before_count FROM  oc_customer");
		$before_count = $beforeQuery->row['before_count'];
		// Iterate over each row in the spreadsheet
		foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
			$cellIterator = $row->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(false);

			$rowData = [];

			foreach ($cellIterator as $cell) {
				$rowData[] = $this->db->escape($cell->getValue());
			}

			// If it's the first row, treat it as headers
			if ($rowIndex == 1) {
				$headers = $rowData;
				$headers = array_map(function ($element) {
					return strtolower(str_replace(" ", "_", $element));
				}, $headers);
			} else {
				// For other rows, create associative array with headers as keys
				if (!empty($headers)) {
					$rowAssoc = array_combine($headers, $rowData);
					$dataArray[] = $rowAssoc;
				}
			}
		}

		$existingUsers = [];
		$usersAlreadyExist = $this->db->query("SELECT customer_code, firstname, lastname FROM oc_customer");
		if ($usersAlreadyExist->num_rows > 0) {
			foreach ($usersAlreadyExist->rows as $exist_cust_row) {
				$uecustomer_code = str_replace('\\', '', $exist_cust_row['customer_code']);
				$uefname =  str_replace('\\', '', $exist_cust_row['firstname']);
				$uelname = str_replace('\\', '', $exist_cust_row['lastname']);
				$uniqueKey = $uecustomer_code . '-' . $uefname . '-' . $uelname;
        $existingUsers[$uniqueKey] = true;
			}
		}

		$final_arr = [];
		foreach ($dataArray as $user) {
			$exc_customer_code = trim(str_replace('\\', '', $user['customer_code']));
			$uname = explode(",", $user['name']);
			$first_name = trim(str_replace('\\', '', $uname[0]));
			$last_name = trim(str_replace('\\', '', $uname[1]));
			list($customer_code, $ufname, $ulname) = [$exc_customer_code, $first_name, $last_name];
			$uniqueKey = $customer_code . '-' . $ufname . '-' . $ulname;
			if (isset($existingUsers[$uniqueKey])) {
				// Skip this user as they already exist
				continue;
			}
			$final_arr[] = $user;
		}
		//print_r($final_arr); die;

		$dataArrayChunk = array_chunk($final_arr, 24);
		$password = md5("Drive@123");
		foreach ($dataArrayChunk as $subArray) {
			$insValStr = '';
			foreach ($subArray as $eachRow) {
				$name = explode(",", $eachRow['name']);
				$fname = trim($name[0]);
				$lname = trim($name[1]);
				$cust_grp_id = isset($cust_grp_id_arr[$eachRow['customer_code']]['cust_grp_id']) ? $cust_grp_id_arr[$eachRow['customer_code']]['cust_grp_id'] : '';
				$cust_cls_id = isset($cust_grp_id_arr[$eachRow['customer_code']]['cust_cls_id']) ? $cust_grp_id_arr[$eachRow['customer_code']]['cust_cls_id'] : '' ;
				$insValStr .= "('".$cust_grp_id."', '".$cust_cls_id."', '" . $eachRow['customer_code'] . "', '" . $eachRow['customer_name'] . "', '" . $fname . "','" . $lname . "','" . $eachRow['access_type'] . "','" . $eachRow['email'] . "','" . $password . "', '0', '1'),";
			}
			$insValStr = rtrim($insValStr, ",");
			$this->db->query("INSERT INTO oc_customer (customer_group_id, customer_class_id, customer_code, customer_name, firstname, lastname, access_type, email, password, is_main_customer, status) VALUES $insValStr");
		}

		$afterQuery = $this->db->query("SELECT COUNT(*) AS after_count FROM oc_customer");
		$after_count = $afterQuery->row['after_count'];

		$message = "Number of users before : $before_count \n Number of users after : $after_count";
		return $message;
	}

	//Add customer and address detail in opencart database from GP database using customer number
	public function insertApiData(string $cust_num)
	{
		$result = [];

		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			//First check customer already exist in opencart table
			$cust_num = $this->db->escape($cust_num);
			$check_cust_query = $this->db->query("SELECT customer_code FROM oc_customer WHERE customer_code = '" . $cust_num . "'");
			if ($check_cust_query->num_rows) {
				$result = ["code" => "200", "msg" => "Customer with the customer number '$cust_num' already exist"];
				return $result;
			}
			// SQL query to select the customer based on customer number
			$query = "SELECT CUSTNMBR, CUSTNAME, CUSTCLAS, CNTCPRSN, ADRSCODE, ADDRESS1, ADDRESS2, COUNTRY, CITY, STATE, 
			ZIP, PHONE1, PHONE2, FAX, PRBTADCD, PRSTADCD, STADDRCD, SLPRSNID, INACTIVE FROM RM00101 WHERE CUSTNMBR = ?";
			$params = array($cust_num);
			$stmt = sqlsrv_query($connection, $query, $params);

			if ($stmt === false) {
				$result = ['code' => '500',	'msg' => 'Failed to fetch customer record'];
				$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
				return $result;
			}

			$customer = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
			sqlsrv_free_stmt($stmt);

			if ($customer) {
				$msg = '';
				$this->db->query("INSERT INTO oc_customer (customer_code, customer_name, customer_group_id, contact_person, 
				address_code, address1, address2, country, city, zip, state, telephone, telephone_1, fax,primary_billing_code,
				 primary_shipping_code, primary_standard_code, sales_person_id, inactive) VALUES 
				 ('" . trim($this->db->escape($customer['CUSTNMBR'])) . "', '" . trim($this->db->escape($customer['CUSTNAME'])) . "',
				  '" . trim($this->db->escape($customer['CUSTCLAS'])) . "', '" . trim($this->db->escape($customer['CNTCPRSN'])) . "',
					'" . trim($this->db->escape($customer['ADRSCODE'])) . "', '" . trim($this->db->escape($customer['ADDRESS1'])) . "',
					'" . trim($this->db->escape($customer['ADDRESS2'])) . "', '" . trim($this->db->escape($customer['COUNTRY'])) . "', 
					'" . trim($this->db->escape($customer['CITY'])) . "', '" . trim($this->db->escape($customer['ZIP'])) . "', 
					'" . trim($this->db->escape($customer['STATE'])) . "', '" . trim($this->db->escape($customer['PHONE1'])) . "', 
					'" . trim($this->db->escape($customer['PHONE2'])) . "', '" . trim($this->db->escape($customer['FAX'])) . "', 
					'" . trim($this->db->escape($customer['PRBTADCD'])) . "', '" . trim($this->db->escape($customer['PRSTADCD'])) . "',
					'" . trim($this->db->escape($customer['STADDRCD'])) . "', '" . trim($this->db->escape($customer['SLPRSNID'])) . "',
					'" . trim($this->db->escape($customer['INACTIVE'])) . "')");

				$last_cust_id = $this->db->getLastId();

				if ($last_cust_id > 0) {

					$msg .= "Customer details inserted successfully<br>";

					$query_add = "SELECT CUSTNMBR, ADRSCODE, SLPRSNID, CNTCPRSN, ADDRESS1, ADDRESS2, COUNTRY, CITY, STATE, 
					ZIP, PHONE1, PHONE2, FAX, ShipToName FROM RM00102 WHERE CUSTNMBR = ?";
					$params_add = array($this->db->escape($cust_num));
					$stmt_add = sqlsrv_query($connection, $query_add, $params_add);

					if ($stmt_add === false) {
						$result = ['code' => '500', 'msg' => 'Failed to fetch address record'];
						$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
						return $result;
					}

					$total_addrs = 0;
					$addrs_added = 0;
					while ($row_add = sqlsrv_fetch_array($stmt_add, SQLSRV_FETCH_ASSOC)) {
						$total_addrs++;
						$this->db->query("INSERT INTO oc_address (customer_id, customer_number, address_code, sales_person_id, 
						contact_person, address_1, address_2, city, postcode, country_id, zone_id, phone1, phone2, fax, 
						ship_to_name) VALUES ('" . $last_cust_id . "', '" . trim($this->db->escape($row_add['CUSTNMBR'])) . "', 
						'" . trim($this->db->escape($row_add['ADRSCODE'])) . "', '" . trim($this->db->escape($row_add['SLPRSNID'])) . "', 
						'" . trim($this->db->escape($row_add['CNTCPRSN'])) . "', '" . trim($this->db->escape($row_add['ADDRESS1'])) . "', 
						'" . trim($this->db->escape($row_add['ADDRESS2'])) . "', '" . trim($this->db->escape($row_add['CITY'])) . "', 
						'" . trim($this->db->escape($row_add['ZIP'])) . "', '" . trim($this->db->escape($row_add['COUNTRY'])) . "', 
						'" . trim($this->db->escape($row_add['STATE'])) . "', '" . trim($this->db->escape($row_add['PHONE1'])) . "', 
						'" . trim($this->db->escape($row_add['PHONE2'])) . "', '" . trim($this->db->escape($row_add['FAX'])) . "', 
						'" . trim($this->db->escape($row_add['ShipToName'])) . "')");

						if ($this->db->getLastId() > 0) {
							$addrs_added++;
						}
					}
					$msg .= "Total address records found : $total_addrs <br> Total address records inserted : $addrs_added";

					sqlsrv_free_stmt($stmt_add);
				}
				$result = ['code' => '200', 'msg' => $msg];
				return $result;
			} else {
				$result = ['code' => '200', 'msg' => 'Customer not found with the customer number : ' . $cust_num];
				return $result;
			}
			// Free the statement after use
		} else {
			$result = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $result;
		}
	}

	public function getCustomerGroupByClassId(string $class_id):string
	{	
		$res = '0';
		$query = $this->db->query("SELECT customer_group_id FROM oc_customer_group_test WHERE class_id = '".$class_id."'");
		if($query->num_rows > 0) {
			$res = $query->row['customer_group_id'];
		}
		return $res;
	}

	public function getCustomerCount()
	{
		$query = $this->db->query("SELECT COUNT(*) as records FROM oc_customer_test");
		return $query->row['records'];
	}
}
