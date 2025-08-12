<?php
class ModelAccountOrder extends Model {
	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND customer_id != '0' AND order_status_id > '0'");

		if ($order_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

			if ($country_query->num_rows) {
				$payment_iso_code_2 = $country_query->row['iso_code_2'];
				$payment_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$payment_iso_code_2 = '';
				$payment_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$payment_zone_code = $zone_query->row['code'];
			} else {
				$payment_zone_code = '';
			}

			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

			if ($country_query->num_rows) {
				$shipping_iso_code_2 = $country_query->row['iso_code_2'];
				$shipping_iso_code_3 = $country_query->row['iso_code_3'];
			} else {
				$shipping_iso_code_2 = '';
				$shipping_iso_code_3 = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

			if ($zone_query->num_rows) {
				$shipping_zone_code = $zone_query->row['code'];
			} else {
				$shipping_zone_code = '';
			}

			return array(
				'order_id'                => $order_query->row['order_id'],
				'gp_order_no'                => $order_query->row['gp_order_no'],
				'invoice_no'              => $order_query->row['invoice_no'],
				'invoice_prefix'          => $order_query->row['invoice_prefix'],
				'store_id'                => $order_query->row['store_id'],
				'store_name'              => $order_query->row['store_name'],
				'store_url'               => $order_query->row['store_url'],
				'customer_id'             => $order_query->row['customer_id'],
				'firstname'               => $order_query->row['firstname'],
				'lastname'                => $order_query->row['lastname'],
				'telephone'               => $order_query->row['telephone'],
				'email'                   => $order_query->row['email'],
				'payment_firstname'       => $order_query->row['payment_firstname'],
				'payment_lastname'        => $order_query->row['payment_lastname'],
				'payment_company'         => $order_query->row['payment_company'],
				'payment_address_1'       => $order_query->row['payment_address_1'],
				'payment_address_2'       => $order_query->row['payment_address_2'],
				'payment_postcode'        => $order_query->row['payment_postcode'],
				'payment_city'            => $order_query->row['payment_city'],
				'payment_zone_id'         => $order_query->row['payment_zone_id'],
				'payment_zone'            => $order_query->row['payment_zone'],
				'payment_zone_code'       => $payment_zone_code,
				'payment_country_id'      => $order_query->row['payment_country_id'],
				'payment_country'         => $order_query->row['payment_country'],
				'payment_iso_code_2'      => $payment_iso_code_2,
				'payment_iso_code_3'      => $payment_iso_code_3,
				'payment_address_format'  => $order_query->row['payment_address_format'],
				'payment_method'          => $order_query->row['payment_method'],
				'shipping_firstname'      => $order_query->row['shipping_firstname'],
				'shipping_lastname'       => $order_query->row['shipping_lastname'],
				'shipping_company'        => $order_query->row['shipping_company'],
				'shipping_address_1'      => $order_query->row['shipping_address_1'],
				'shipping_address_2'      => $order_query->row['shipping_address_2'],
				'shipping_postcode'       => $order_query->row['shipping_postcode'],
				'shipping_city'           => $order_query->row['shipping_city'],
				'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
				'shipping_zone'           => $order_query->row['shipping_zone'],
				'shipping_zone_code'      => $shipping_zone_code,
				'shipping_country_id'     => $order_query->row['shipping_country_id'],
				'shipping_country'        => $order_query->row['shipping_country'],
				'shipping_iso_code_2'     => $shipping_iso_code_2,
				'shipping_iso_code_3'     => $shipping_iso_code_3,
				'shipping_address_format' => $order_query->row['shipping_address_format'],
				'shipping_method'         => $order_query->row['shipping_method'],
				'comment'                 => $order_query->row['comment'],
				'total'                   => $order_query->row['total'],
				'order_status_id'         => $order_query->row['order_status_id'],
				'language_id'             => $order_query->row['language_id'],
				'currency_id'             => $order_query->row['currency_id'],
				'currency_code'           => $order_query->row['currency_code'],
				'currency_value'          => $order_query->row['currency_value'],
				'date_modified'           => $order_query->row['date_modified'],
				'date_added'              => $order_query->row['date_added'],
				'ip'                      => $order_query->row['ip']
			);
		} else {
			return false;
		}
	}

	public function getOrders($start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 1;
		}

		$query = $this->db->query("SELECT o.order_id, o.gp_order_no, o.firstname, o.lastname, os.name as status, o.date_added, o.total, o.currency_code, o.currency_value FROM `" . DB_PREFIX . "order` o LEFT JOIN " . DB_PREFIX . "order_status os ON (o.order_status_id = os.order_status_id) WHERE o.customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.order_id DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getOrderProduct($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->row;
	}

	public function getOrderProducts($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderOptions($order_id, $order_product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

		return $query->rows;
	}

	public function getOrderVouchers($order_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

		return $query->rows;
	}

	public function getOrderTotals($order_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order");

		return $query->rows;
	}

	public function getOrderHistories($order_id) {
		$query = $this->db->query("SELECT date_added, os.name AS status, oh.comment, oh.notify FROM " . DB_PREFIX . "order_history oh LEFT JOIN " . DB_PREFIX . "order_status os ON oh.order_status_id = os.order_status_id WHERE oh.order_id = '" . (int)$order_id . "' AND os.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY oh.date_added");

		return $query->rows;
	}

	public function getTotalOrders() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o WHERE customer_id = '" . (int)$this->customer->getId() . "' AND o.order_status_id > '0' AND o.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['total'];
	}

	public function getTotalOrderProductsByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getTotalOrderVouchersByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order_voucher` WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	/*Dynamics GP related functions here */
	//Import orders from dynamics GP
	public function insertOrdersFromGP():array {
		ini_set('max_execution_time', 900);
		$result = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			$existing_orders = [];
			$query = $this->db->query("SELECT order_number FROM oc_order");
			if($query->num_rows > 0) {
				foreach($query->rows as $values) {
					if(!empty($values['order_number'])) {
						$existing_orders[$values['order_number']] = true;
					}
				}
			}
			
			$batch_size = 10000; // Define batch size for each iteration
			$offset = 0; // Start from the first record

			while (true) {
				$query = "SELECT SOPTYPE, SOPNUMBE, ORDRDATE, CUSTNMBR, CUSTNAME, CNTCPRSN, ADDRESS1, ADDRESS2, CITY, STATE, ZIPCODE, COUNTRY, PHNUMBR1, PHNUMBR2, FAXNUMBR, FORMAT(SUBTOTAL, '0.00000') AS SUBTOTAL, FORMAT(TAXAMNT, '0.00000') AS TAXAMNT, FORMAT(DOCAMNT, '0.00000') AS DOCAMNT  FROM SOP30200 ORDER BY SOPNUMBE OFFSET $offset ROWS FETCH NEXT $batch_size ROWS ONLY";
				//echo $query; die;
				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$data = [];
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					//echo "<pre>"; print_r($row); die;
					$row = array_map('trim', $row);
					$item_num = $this->db->escape($row['SOPNUMBE']);
					if (isset($existing_orders[$item_num])) {
						continue;
					}
					if (preg_match('/^[A-Za-z0-9]+$/', $item_num)) {
						$data[] = $row;
					}
					
				}

				if (empty($data)) {
					break; // No more data to fetch
				}

				// Process and insert the batch into OpenCart
				$this->insertOrdersIntoWebstoreDB($data);

				$offset += $batch_size; // Move to the next batch
			}

			sqlsrv_free_stmt($stmt);
			$this->model_extension_sqlsrv_connect_ms_sql->close();

			$result = ['code' => '200', 'msg' => 'Successful'];
		} else {
			$result = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $result;
		}


		return $result;
	}

	private function insertOrdersIntoWebstoreDB(array $data) {
		//echo 1; die;
		try {
			$arr_chunk = array_chunk($data, 1000);
			foreach ($arr_chunk as $key => $value) {
				$ins_str = '';
				foreach ($value as $k => $v) {
					if ($v['ORDRDATE'] instanceof DateTime) {
						$formatted_date = $v['ORDRDATE']->format('Y-m-d H:i:s');
					} else {
						$formatted_date = '';
					}
					//echo $v['SOPNUMBE'].''.$v['SUBTOTAL']; die;
					$ins_str .= "('".$this->db->escape($v['SOPTYPE'])."', '".$this->db->escape($v['SOPNUMBE'])."', '".$this->db->escape($v['CUSTNMBR'])."', '".$this->db->escape($v['CUSTNAME'])."', '".$this->db->escape($v['CNTCPRSN'])."', '".$this->db->escape($v['ADDRESS1'])."', '".$this->db->escape($v['ADDRESS2'])."', '".$this->db->escape($v['CITY'])."', '".$this->db->escape($v['STATE'])."', '".$this->db->escape($v['ZIPCODE'])."', '".$this->db->escape($v['COUNTRY'])."', '".$this->db->escape($v['PHNUMBR1'])."', '".$this->db->escape($v['PHNUMBR2'])."', '".$this->db->escape($v['FAXNUMBR'])."', '".$this->db->escape($v['SUBTOTAL'])."', '".$this->db->escape($v['TAXAMNT'])."', '".$this->db->escape($v['DOCAMNT'])."', '".$formatted_date."'),";
				}
				$ins_str = rtrim($ins_str, ',');
				//echo "INSERT INTO oc_order (order_type, order_number, customer_number, customer_name, contact_person, shipping_address_1, shipping_address_2, shipping_city, shipping_zone, shipping_postcode, shipping_country, phone_number_1, phone_number_2, fax, sub_total, tax_amount, total, date_added) VALUES $ins_str"; die;
				//echo $ins_str; die;
				$this->db->query("INSERT INTO oc_order (order_type, order_number, customer_number, customer_name, contact_person, shipping_address_1, shipping_address_2, shipping_city, shipping_zone, shipping_postcode, shipping_country, phone_number_1, phone_number_2, fax, sub_total, tax_amount, total, date_added) VALUES $ins_str");
				// echo 'done';
				//die;
			} //die;

		} catch (Exception $e) {
			return ["code" => "500", "msg" => "Something went wrong : ".$e->getMessage()];
		}
	}
}