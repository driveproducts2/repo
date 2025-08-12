<?php
class ModelAccountAddress extends Model {
	public function addAddress($customer_id, $data) {

		$sql = "INSERT INTO " . DB_PREFIX . "address SET customer_id = '" . (int)$customer_id . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "'";

		if(isset($data['description']) && !empty($data['description'])) {
			$sql .= ",description = '".$this->db->escape($data['description'])."'";
		}

		if(isset($data['phone']) && !empty($data['phone'])) {
			$sql .= ",phone1 = '".$this->db->escape($data['phone'])."'";
		}

		if(isset($data['ext']) && !empty($data['ext'])) {
			$sql .= ",ext = '".$this->db->escape($data['ext'])."'";
		}

		if(isset($data['fax']) && !empty($data['fax'])) {
			$sql .= ",fax = '".$this->db->escape($data['fax'])."'";
		}

		// if(isset($data['make_default_address']) && !empty($data['make_default_address']) && $data['make_default_address'] == 'default') {
		// 	$sql .= ",default_address = '1'";
		// }

		$this->db->query($sql);

		$address_id = $this->db->getLastId();

		if (!empty($data['default'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$customer_id . "'");
		}

		return $address_id;
	}

	public function editAddress($address_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "address SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', company = '" . $this->db->escape($data['company']) . "', address_1 = '" . $this->db->escape($data['address_1']) . "', address_2 = '" . $this->db->escape($data['address_2']) . "', postcode = '" . $this->db->escape($data['postcode']) . "', city = '" . $this->db->escape($data['city']) . "', zone_id = '" . (int)$data['zone_id'] . "', country_id = '" . (int)$data['country_id'] . "', custom_field = '" . $this->db->escape(isset($data['custom_field']['address']) ? json_encode($data['custom_field']['address']) : '') . "' WHERE address_id  = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");

		if (!empty($data['default'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = '" . (int)$address_id . "' WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		}
	}

	public function deleteAddress($address_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
		$default_query = $this->db->query("SELECT address_id FROM " . DB_PREFIX . "customer WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "'");
		if ($default_query->num_rows) {
			$this->db->query("UPDATE " . DB_PREFIX . "customer SET address_id = 0 WHERE customer_id = '" . (int)$this->customer->getId() . "'");
		}
	}

	public function getAddress($address_id) {
		$address_query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "address WHERE address_id = '" . (int)$address_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' OR customer_number IN (SELECT customer_code FROM oc_customer WHERE customer_id = '".(int)$this->customer->getId()."')");

		if ($address_query->num_rows) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$address_query->row['country_id'] . "'");

			if ($country_query->num_rows) {
				$country = $country_query->row['name'];
				$iso_code_2 = $country_query->row['iso_code_2'];
				$iso_code_3 = $country_query->row['iso_code_3'];
				$address_format = $country_query->row['address_format'];
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

			$address_data = array(
				'address_id'     => $address_query->row['address_id'],
				'address_code'   => $address_query->row['address_code'],
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

			return $address_data;
		} else {
			return false;
		}
	}

	public function getAddresses() {
		$address_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		if($query->num_rows == 0) {
			$query = $this->db->query("SELECT a.* FROM oc_address a INNER JOIN oc_customer c1 ON a.customer_id = c1.customer_id INNER JOIN oc_customer c2 ON c1.customer_code = c2.customer_code WHERE c2.customer_id = '".(int)$this->customer->getId()."'");
		}

		foreach ($query->rows as $result) {
			$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$result['country_id'] . "'");

			if ($country_query->num_rows) {
				$country = $country_query->row['name'];
				$iso_code_2 = $country_query->row['iso_code_2'];
				$iso_code_3 = $country_query->row['iso_code_3'];
				$address_format = $country_query->row['address_format'];
			} else {
				$country = '';
				$iso_code_2 = '';
				$iso_code_3 = '';
				$address_format = '';
			}

			$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "regions` WHERE id = '" . (int)$result['zone_id'] . "'");

			if ($zone_query->num_rows) {
				$zone = $zone_query->row['region_en'];
				$zone_code = $zone_query->row['region'];
			} else {
				$zone = '';
				$zone_code = '';
			}

			$address_data[$result['address_id']] = array(
				'address_id'     => $result['address_id'],
				'firstname'      => $result['firstname'],
				'lastname'       => $result['lastname'],
				'company'        => $result['company'],
				'address_1'      => $result['address_1'],
				'address_2'      => $result['address_2'],
				'postcode'       => $result['postcode'],
				'city'           => $result['city'],
				'zone_id'        => $result['zone_id'],
				'zone'           => $zone,
				'zone_code'      => $zone_code,
				'country_id'     => $result['country_id'],
				'country'        => $country,
				'iso_code_2'     => $iso_code_2,
				'iso_code_3'     => $iso_code_3,
				'address_format' => $address_format,
				'custom_field'   => json_decode($result['custom_field'], true)

			);
		}

		return $address_data;
	}

	public function getTotalAddresses() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "address WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}

	public function getBillingAddress(int $user_id):array {
		$res = [];

		$user_id = $this->db->escape($user_id);
		$sql = "SELECT a.firstname, a.lastname, a.company, a.address_1, a.address_2, a.city, a.postcode, a.country_id, a.zone_id, a.address_code FROM oc_address AS a INNER JOIN oc_customer AS c_main ON a.address_code = c_main.address_code WHERE c_main.is_main_customer = 1 AND c_main.customer_code = (SELECT customer_code FROM oc_customer WHERE customer_id = '".$user_id."') AND a.customer_number = (SELECT customer_code FROM oc_customer WHERE customer_id = '".$user_id."')";

		$query = $this->db->query($sql);

		if($query->num_rows > 0){
			$res = $query->row;
		}

		return $res;
	}

	public function getUserCustomerAddress():array {
		$address_data = [];
		$query = $this->db->query("SELECT * FROM oc_address WHERE customer_id = '".(int)$this->customer->getId()."' OR customer_number IN (SELECT customer_code FROM oc_customer WHERE customer_id = '".(int)$this->customer->getId()."'); ");

		if($query->num_rows > 0) {
			foreach ($query->rows as $result) {
				$country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$result['country_id'] . "'");
	
				if ($country_query->num_rows) {
					$country = $country_query->row['name'];
				} else {
					$country = '';
				}
	
				$zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "regions` WHERE id = '" . (int)$result['zone_id'] . "'");
	
				if ($zone_query->num_rows) {
					$zone = $zone_query->row['region_en'];
				} else {
					$zone = '';
				}
	
				$address_data[$result['address_id']] = array(
					'address_id'     => $result['address_id'],
					'firstname'      => $result['firstname'],
					'lastname'       => $result['lastname'],
					'company'        => $result['company'],
					'address_1'      => $result['address_1'],
					'address_2'      => $result['address_2'],
					'postcode'       => $result['postcode'],
					'city'           => $result['city'],
					'zone_id'        => $result['zone_id'],
					'zone'           => $zone,
					'country_id'     => $result['country_id'],
					'country'        => $country	
				);
			}
		}

		return $address_data;
	}
}
