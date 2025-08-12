<?php
class ModelCatalogProduct extends Model
{
	public function updateViewed($product_id)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "product SET viewed = (viewed + 1) WHERE product_id = '" . (int)$product_id . "'");
	}

	public function getProduct($product_id)
	{
		$query = $this->db->query("SELECT DISTINCT *,p.product_id as product_id,  pd.name AS name, p.image, m.name AS manufacturer, c.parent_id as main_category, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) 
		LEFT JOIN oc_product_to_category p2c ON p2c.product_id = p.product_id
		LEFT JOIN oc_category c ON c.category_id = p2c.category_id
		WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		
		// if (isset($this->session->data['customer_code']) && !empty($this->session->data['customer_code']) &&
		// 		isset($query->row['model']) && !empty($query->row['model'])) {
		// 	$product_price = $this->getProductNetPrice($this->session->data['customer_code'], $query->row['model']);
		// } elseif(isset($query->row['model']) && !empty($query->row['model'])) {
		// 	$product_price = $this->getProductBasePrice($query->row['model']);
		// }
		//echo $product_price."<br>";
		if ($query->num_rows) {
			return array(
				'product_id'       => $query->row['product_id'],
				'main_category'    => $query->row['main_category'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_title'       => $query->row['meta_title'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'tag'              => $query->row['tag'],
				'model'            => $query->row['model'],
				'sku'              => $query->row['sku'],
				'upc'              => $query->row['upc'],
				'ean'              => $query->row['ean'],
				'jan'              => $query->row['jan'],
				'isbn'             => $query->row['isbn'],
				'mpn'              => $query->row['mpn'],
				'location'         => $query->row['location'],
				'quantity'         => $query->row['quantity'],
				'stock_status'     => $query->row['stock_status'],
				'image'            => $query->row['image'],
				'manufacturer_id'  => $query->row['manufacturer_id'],
				'manufacturer'     => $query->row['manufacturer'],
				//'price'            => $product_price,
				'special'          => $query->row['special'],
				'reward'           => $query->row['reward'],
				'points'           => $query->row['points'],
				'tax_class_id'     => $query->row['tax_class_id'],
				'date_available'   => $query->row['date_available'],
				'weight'           => $query->row['weight'],
				'weight_class_id'  => $query->row['weight_class_id'],
				'length'           => $query->row['length'],
				'width'            => $query->row['width'],
				'height'           => $query->row['height'],
				'length_class_id'  => $query->row['length_class_id'],
				'subtract'         => $query->row['subtract'],
				'rating'           => round(($query->row['rating'] === null) ? 0 : $query->row['rating']),
				'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
				'minimum'          => $query->row['minimum'],
				'sort_order'       => $query->row['sort_order'],
				'status'           => $query->row['status'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}

	public function getProducts($data = array())
	{
		$sql = "SELECT p.product_id";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) "; 
		$sql .= " LEFT JOIN " .DB_PREFIX . "item_classes i ON (p.item_class_code = i.item_class)";
		
		if (isset($data['filter_category_id']) && !empty($data['filter_category_id'])) {
			$sql .= " LEFT JOIN oc_category c ON (p2c.category_id = c.category_id) "; 
		}

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND i.status = 1";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND c.parent_id = '" . (int)$data['filter_category_id'] . "' AND c.status = 1";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$implode[] = (int)$filter_id;
				}

				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
			}
		}

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					$implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				//if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				//}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				$sql .= " OR p.model LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.quantity',
			'rating',
			'p.price',
			'p.sort_order',
			'p.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				$sql .= " ORDER BY p.price";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.product_id";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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
		$product_data = array();
		
		$query = $this->db->query($sql);
		
		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getProductSpecials($data = array())
	{
		$sql = "SELECT DISTINCT ps.product_id, (SELECT AVG(rating) FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = ps.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) GROUP BY ps.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'ps.price',
			'rating',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$product_data = array();

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}

		return $product_data;
	}

	public function getLatestProducts($limit)
	{
		$product_data = $this->cache->get('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getPopularProducts($limit)
	{
		$product_data = $this->cache->get('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getBestSellerProducts($limit)
	{
		$product_data = $this->cache->get('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();

			$query = $this->db->query("SELECT op.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id) LEFT JOIN `" . DB_PREFIX . "product` p ON (op.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE o.order_status_id > '0' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY op.product_id ORDER BY total DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getProductAttributes($product_id)
	{
		$product_attribute_group_data = array();

		$product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

		foreach ($product_attribute_group_query->rows as $product_attribute_group) {
			$product_attribute_data = array();

			$product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");

			foreach ($product_attribute_query->rows as $product_attribute) {
				$product_attribute_data[] = array(
					'attribute_id' => $product_attribute['attribute_id'],
					'name'         => $product_attribute['name'],
					'text'         => $product_attribute['text']
				);
			}

			$product_attribute_group_data[] = array(
				'attribute_group_id' => $product_attribute_group['attribute_group_id'],
				'name'               => $product_attribute_group['name'],
				'attribute'          => $product_attribute_data
			);
		}

		return $product_attribute_group_data;
	}

	public function getProductOptions($product_id)
	{
		$product_option_data = array();

		$product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN " . DB_PREFIX . "option_description od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int)$product_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY o.sort_order");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();

			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int)$product_id . "' AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => $product_option_value['image'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => $product_option_value['price'],
					'price_prefix'            => $product_option_value['price_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix']
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required']
			);
		}

		return $product_option_data;
	}

	public function getProductDiscounts($product_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity > 1 AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity ASC, priority ASC, price ASC");

		return $query->rows;
	}

	public function getProductImages($product_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductRelated($product_id)
	{
		$product_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related pr LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pr.product_id = '" . (int)$product_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $this->getProduct($result['related_id']);
		}

		return $product_data;
	}

	public function getProductLayoutId($product_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getCategories($product_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		return $query->rows;
	}

	public function getTotalProducts($data = array())
	{
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) ";
		$sql .= " LEFT JOIN " .DB_PREFIX . "item_classes i ON (p.item_class_code = i.item_class)";

		if (isset($data['filter_category_id']) && !empty($data['filter_category_id'])) {
			$sql .= " LEFT JOIN oc_category c ON (p2c.category_id = c.category_id) ";
		}
			
		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND i.status = 1";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND c.parent_id = '" . (int)$data['filter_category_id'] . "' AND c.status = 1";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$implode[] = (int)$filter_id;
				}

				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
			}
		}

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					$implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				//if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				//}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				$sql .= " OR p.model LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}
		// echo $sql; die;
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}

	public function getProfile($product_id, $recurring_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r JOIN " . DB_PREFIX . "product_recurring pr ON (pr.recurring_id = r.recurring_id AND pr.product_id = '" . (int)$product_id . "') WHERE pr.recurring_id = '" . (int)$recurring_id . "' AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

		return $query->row;
	}

	public function getProfiles($product_id)
	{
		$query = $this->db->query("SELECT rd.* FROM " . DB_PREFIX . "product_recurring pr JOIN " . DB_PREFIX . "recurring_description rd ON (rd.language_id = " . (int)$this->config->get('config_language_id') . " AND rd.recurring_id = pr.recurring_id) JOIN " . DB_PREFIX . "recurring r ON r.recurring_id = rd.recurring_id WHERE pr.product_id = " . (int)$product_id . " AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getTotalProductSpecials()
	{
		$query = $this->db->query("SELECT COUNT(DISTINCT ps.product_id) AS total FROM " . DB_PREFIX . "product_special ps LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))");

		if (isset($query->row['total'])) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	public function checkProductCategory($product_id, $category_ids)
	{

		$implode = array();

		foreach ($category_ids as $category_id) {
			$implode[] = (int)$category_id;
		}

		$query = $this->db->query("SELECT pc.* FROM oc_product_to_category pc LEFT JOIN oc_category c ON pc.category_id = c.category_id WHERE product_id = '".$product_id."' AND c.parent_id IN (" . implode(',', $implode) . ")");
		//$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' AND category_id IN(" . implode(',', $implode) . ")");
		return $query->row;
	}

	public function getRecentProducts(array $product_ids, int $excl_pro_id): array
	{
		$result = array();

		if (isset($product_ids) && !empty($product_ids)) {
			$pro_id_str = implode(",", $product_ids);

			$sql = "SELECT p.product_id, p.image, p.model, pd.name, c.parent_id as main_category FROM oc_product as p LEFT JOIN oc_product_description pd ON pd.product_id = p.product_id LEFT JOIN oc_product_to_category pc ON pc.product_id = p.product_id LEFT JOIN oc_category c ON c.category_id = pc.category_id WHERE p.product_id IN ($pro_id_str) AND p.product_id != '" . $excl_pro_id . "' GROUP BY p.product_id";

			$query = $this->db->query($sql);

			if ($query->num_rows > 0) {
				foreach ($query->rows as $res) {
					$result[$res['product_id']] = $res;
				}
			}
		}
		return $result;
	}

	//Get product net price according to customer
	private function getProductNetPrice(string $customer_code, string $product_model_no): float {
		$result = 0;
		//echo "CALL CalculateItemPrice('$product_model_no', '$customer_code', @finalPrice)"; die;
		$result = $this->db->query("CALL CalculateItemPrice('$product_model_no', '$customer_code', @finalPrice)");
		$query = $this->db->query("SELECT @finalPrice AS finalPrice");
		//DebugHelper::print($query);
		if($query) {
			$res_arr = (array)$query;
			if(isset($res_arr) && !empty($res_arr)) {
				if($res_arr['num_rows'] > 0) {
					$result = $res_arr['row']['finalPrice'];
				}
			}
		}

		return $result;
	} 

	//Get product base price
	private function getProductBasePrice(string $product_model_no): float {
		$result = 0;
		//echo "CALL CalculateItemPrice('$product_model_no', '$customer_code', @finalPrice)"; die;
		$result = $this->db->query("CALL CalculateItemBasePrice('$product_model_no', @finalPrice)");
		$query = $this->db->query("SELECT @finalPrice AS finalPrice");
		//DebugHelper::print($query);
		if($query) {
			$res_arr = (array)$query;
			if(isset($res_arr) && !empty($res_arr)) {
				if($res_arr['num_rows'] > 0) {
					$result = isset($res_arr['row']['finalPrice']) ? $res_arr['row']['finalPrice'] : 0;
				}
			}
		}

		return $result;
	} 

	//method to insert new categories in webstore db
	public function insertProduct()
	{
		$res_arr = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect(); //connect to GP SQL

		if ($connection) {
			$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_product");
			$cnt_before = $query_bfr_cnt->row['bfr_cnt'];

			$exist_prodt_arr = [];
			$chk_prodt_query = $this->db->query("SELECT model FROM oc_product");

			foreach ($chk_prodt_query->rows as $prodt) {
				$exist_prodt_arr[] = $prodt['model'];
			}

			$batch_size = 1000; // Define batch size for each iteration
			$offset = 0; // Start from the first record

			while (true) {
				$query = "SELECT ITEMNMBR, ITEMDESC, ITEMTYPE, STNDCOST, CURRCOST, ITMCLSCD, PRCLEVEL, PriceGroup, INACTIVE, SELNGUOM FROM IV00101 ORDER BY ITEMNMBR OFFSET $offset ROWS FETCH NEXT $batch_size ROWS ONLY";

				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$data = [];
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					$item_num = trim($this->db->escape($row['ITEMNMBR']));
					if (!in_array($item_num, $exist_prodt_arr)) {
						$data[] = $row;
					}
				}

				if (empty($data)) {
					break; // No more data to fetch
				}

				// Process and insert the batch into webstore
				$this->insertProductsIntoWebstoreDB($data);

				$offset += $batch_size; // Move to the next batch
			}
			$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_product");
			$cnt_after = $query_aft_cnt->row['aft_cnt'];

			sqlsrv_free_stmt($stmt);
			$this->model_extension_sqlsrv_connect_ms_sql->close();

			$total_prod_added = $cnt_after - $cnt_before;
			$res_arr = ['code' => '200', 'msg' => "Number of records before : " . $cnt_before . " \n Number of records after : " . $cnt_after . " \n Number of Products added : " . $total_prod_added];
		} else {
			$this->log->write('Database Connection Error: Unable to connect');
			$res_arr = ['code' => '500', 'msg' => 'Failed to connect database'];
		}

		return $res_arr;
	}

	private function insertProductsIntoWebstoreDB($data)
	{
		try {
			$status = ["0" => "1", "1" => "0"];
			foreach ($data as $value) {
				//ITEMNMBR, ITEMDESC, ITEMTYPE, STNDCOST, CURRCOST, ITMCLSCD, PRCLEVEL, PriceGroup, SELNGUOM
				$this->db->query("INSERT INTO oc_product (model, item_type, standard_cost, current_cost, item_class_code, price_level, price_group, selling_uom, status) VALUES ('" . trim($this->db->escape($value['ITEMNMBR'])) . "', '" . trim($this->db->escape($value['ITEMTYPE'])) . "', '" . trim($this->db->escape($value['STNDCOST'])) . "', '" . trim($this->db->escape($value['CURRCOST'])) . "', '" . trim($this->db->escape($value['ITMCLSCD'])) . "', '" . trim($this->db->escape($value['PRCLEVEL'])) . "', '" . trim($this->db->escape($value['PriceGroup'])) . "', '" . trim($this->db->escape($value['SELNGUOM'])) . "', '" . trim($this->db->escape($status[$value['INACTIVE']])) . "')");

				$last_ins_id = $this->db->getLastId();
				if ($last_ins_id > 0) {
					$this->db->query("INSERT INTO oc_product_description (product_id, model, language_id, name) VALUES ('" . trim($this->db->escape($last_ins_id)) . "','" . trim($this->db->escape($value['ITEMNMBR'])) . "','1','" . trim($this->db->escape($value['ITEMDESC'])) . "')");
				}
			}
		} catch (Exception $e) {
			return ["code" => "500", "msg" => "Something went wrong : " . $e->getMessage()];
		}
	}

	public function insertWarehouse()
	{
		try {
			$status = ["0" => "1", "1" => "0"];
			$res_arr = [];
			$this->load->model('extension/sqlsrv/connect_ms_sql');
			$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect(); //connect to GP SQL

			if ($connection) {
				$query = "SELECT LOCNCODE, LOCNDSCR, ADDRESS1, ADDRESS2, ADDRESS3, CITY, STATE, ZIPCODE, COUNTRY, PHONE1, PHONE2, PHONE3, FAXNUMBR, STAXSCHD, PCTAXSCH, CCode, INACTIVE FROM IV40700";

				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_warehouse");
				$cnt_before = $query_bfr_cnt->row['bfr_cnt'];

				$exist_wrhse_arr = [];
				$chk_wrhse_query = $this->db->query("SELECT location_code FROM oc_warehouse");

				foreach ($chk_wrhse_query->rows as $wrhse) {
					$exist_wrhse_arr[] = $wrhse['location_code'];
				}

				$ins_qry = '';
				$ins_values = '';

				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					$loc_code = trim($this->db->escape($row['LOCNCODE']));
					if (!in_array($loc_code, $exist_wrhse_arr)) {
						$ins_values .= "('" . trim($this->db->escape($row['LOCNCODE'])) . "', '" . trim($this->db->escape($row['LOCNDSCR'])) . "', '" . trim($this->db->escape($row['ADDRESS1'])) . "', '" . trim($this->db->escape($row['ADDRESS2'])) . "', '" . trim($this->db->escape($row['ADDRESS3'])) . "', '" . trim($this->db->escape($row['CITY'])) . "', '" . trim($this->db->escape($row['STATE'])) . "', '" . trim($this->db->escape($row['ZIPCODE'])) . "', '" . trim($this->db->escape($row['COUNTRY'])) . "', '" . trim($this->db->escape($row['PHONE1'])) . "', '" . trim($this->db->escape($row['PHONE2'])) . "', '" . trim($this->db->escape($row['PHONE3'])) . "', '" . trim($this->db->escape($row['FAXNUMBR'])) . "', '" . trim($this->db->escape($row['STAXSCHD'])) . "', '" . trim($this->db->escape($row['PCTAXSCH'])) . "', '" . trim($this->db->escape($row['CCode'])) . "', '" . $status[trim($this->db->escape($row['INACTIVE']))] . "'),";
					}
				}

				if (!empty($ins_values)) {
					$ins_qry = "INSERT INTO oc_warehouse (location_code, description, address1, address2, address3, city, state, zipcode, country, phone1, phone2, phone3, fax_number, staxschid, pctaxsch, country_code, status) VALUES ";
					$ins_values = rtrim($ins_values, ",");
					$ins_qry .= $ins_values;
					$this->db->query($ins_qry);
				}

				$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_warehouse");
				$cnt_after = $query_aft_cnt->row['aft_cnt'];


				sqlsrv_free_stmt($stmt);
				$this->model_extension_sqlsrv_connect_ms_sql->close();

				$total_warhse_added = $cnt_after - $cnt_before;
				$res_arr = ['code' => '200', 'msg' => "Number of records before : " . $cnt_before . " \n Number of records after : " . $cnt_after . " \n Number of Warehouse added : " . $total_warhse_added];
			} else {
				$this->log->write('Database Connection Error: Unable to connect');
				$res_arr = ['code' => '500', 'msg' => 'Failed to connect database'];
			}

			return $res_arr;
		} catch (Exception $e) {
			return ["code" => "500", "msg" => "Something went wrong : " . $e->getMessage()];
		}
	}

	public function insertProductToLocation()
	{
		try {
			ini_set('memory_limit', '1024M');
			set_time_limit(0);
			$res_arr = [];
			$this->load->model('extension/sqlsrv/connect_ms_sql');
			$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect(); //connect to GP SQL

			if ($connection) {
				$query = "SELECT ITEMNMBR, LOCNCODE, QTYONHND, ATYALLOC, INACTIVE FROM IV00102 WHERE LOCNCODE != '' AND ITEMNMBR != ''";
				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_product_to_location");
				$cnt_before = $query_bfr_cnt->row['bfr_cnt'];

				$existing_records = [];
				$chk_query = $this->db->query("SELECT item_number, location_code FROM oc_product_to_location");

				foreach ($chk_query->rows as $value) {
					$key = trim($this->db->escape($value['item_number'])) . "|" . trim($this->db->escape($value['location_code']));
					$existing_records[$key] = true;
				}

				$data = [];
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					$gen_uniq_val = preg_replace('/[^\x20-\x7E]/', '', trim($this->db->escape($row['ITEMNMBR']))) . "|" . preg_replace('/[^\x20-\x7E]/', '', trim($this->db->escape($row['LOCNCODE'])));
					if (!isset($existing_records[$gen_uniq_val])) {
						$data[] = [
							'ITEMNMBR' => preg_replace('/[^\x20-\x7E]/', '', trim($this->db->escape($row['ITEMNMBR']))),
							'LOCNCODE' => preg_replace('/[^\x20-\x7E]/', '', trim($this->db->escape($row['LOCNCODE']))),
							'QTYONHND' => trim($this->db->escape($row['QTYONHND'])),
							'ATYALLOC' => trim($this->db->escape($row['ATYALLOC'])),
							'INACTIVE' => trim($this->db->escape($row['INACTIVE']))
						];
					}
				}

				if (!empty($data)) {
					$data = array_chunk($data, 200);
					$status = ["0" => "1", "1" => "0"];

					foreach ($data as $val) {
						$ins_str = '';
						foreach ($val as $k) {
							//ITEMNMBR, LOCNCODE, QTYONHND, ATYALLOC, INACTIVE
							$ins_str .= "('" . $k['ITEMNMBR'] . "', '" . $k['LOCNCODE'] . "', '" . $k['QTYONHND'] . "', '" . $k['ATYALLOC'] . "', '" . $status[$k['INACTIVE']] . "'),";
						}
						$ins_str = rtrim($ins_str, ",");
						$fnl_qry = "INSERT INTO oc_product_to_location (item_number, location_code, qty_on_hand, qty_allocated, status) VALUES " . $ins_str;

						$this->db->query($fnl_qry);
					}
				}

				$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_product_to_location");
				$cnt_after = $query_aft_cnt->row['aft_cnt'];

				sqlsrv_free_stmt($stmt);
				$this->model_extension_sqlsrv_connect_ms_sql->close();

				$total_records_added = $cnt_after - $cnt_before;
				$res_arr = ['code' => '200', 'msg' => "Number of records before : " . $cnt_before . " \n Number of records after : " . $cnt_after . " \n Number of records added : " . $total_records_added];
			} else {
				$this->log->write('Database Connection Error: Unable to connect');
				$res_arr = ['code' => '500', 'msg' => 'Failed to connect database'];
			}

			return $res_arr;
		} catch (Exception $e) {
			return ["code" => "500", "msg" => "Something went wrong : " . $e->getMessage()];
		}
	}

	public function insertPriceSheets() {
		ini_set('max_execution_time', 1200);
		$result = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			// Get count of records before insertion
			$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_price_list");
			$cnt_before = $query_bfr_cnt->row['bfr_cnt'];

			$batch_size = 10000; // Define batch size for each iteration
			$offset = 0; // Start from the first record

			while (true) {
				$query = "SELECT * FROM IV10402 WHERE PRCSHID IN (SELECT PRCSHID FROM SOP10110 WHERE ENDDATE >= GETDATE()) ORDER BY DEX_ROW_ID OFFSET $offset ROWS FETCH NEXT $batch_size ROWS ONLY";
				//echo $query; die;
				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$data = [];
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					$prcsht_id = preg_replace('/[^\x20-\x7E]/', '', trim($row['PRCSHID']));
					$item_type = preg_replace('/[^\x20-\x7E]/', '', trim($row['EPITMTYP']));
					$item_number = preg_replace('/[^\x20-\x7E]/', '', trim($row['ITEMNMBR']));
					$uofm = preg_replace('/[^\x20-\x7E]/', '', trim($row['UOFM']));
					$quantity_from = preg_replace('/[^\x20-\x7E]/', '', trim($row['QTYFROM']));
					$quantity_to = preg_replace('/[^\x20-\x7E]/', '', trim($row['QTYTO']));

					$data[] = [
							'price_sheet_id' => $prcsht_id,
							'item_type' => $item_type,
							'product_sku' => $item_number,
							'unit_of_measure' => $uofm,
							'quantity_from' => $quantity_from,
							'quantity_to' => $quantity_to,
							'item_price' => trim($row['PSITMVAL']),
							'equivalent_qty' => trim($row['EQUOMQTY']),
							'qty_base_uom' => trim($row['QTYBSUOM']),
							'sequence_number' => trim($row['SEQNUMBR']),
							'row_id' => trim($row['DEX_ROW_ID'])
					];			
				}

				if (empty($data)) {
					break; // No more data to fetch
				}

				// Process and insert the batch into webstore
				$this->insertPriceSheetsData($data);
				$offset += $batch_size; // Move to the next batch
			}

			// Get count of records after insertion
			$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_price_list");
			$cnt_after = $query_aft_cnt->row['aft_cnt'];

			// Free resources
			sqlsrv_free_stmt($stmt);
			$this->model_extension_sqlsrv_connect_ms_sql->close();

			// Calculate records added
			$total_records_added = $cnt_after - $cnt_before;
			return [
				'code' => '200',
				'msg' => "Number of records before: $cnt_before \n Number of records after: $cnt_after \n Number of records added: $total_records_added"
			];

		} else {
			$result = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $result;
		}

		return $result;
	}

	private function insertPriceSheetsData(array $data) {
		if (!empty($data)) {
			$data_chunks = array_chunk($data, 500); // Chunk data into groups of 500

			foreach ($data_chunks as $chunk) {
				$insert_values = [];
				foreach ($chunk as $record) {
						$insert_values[] = "(
								'" . $this->db->escape($record['price_sheet_id']) . "',
								'" . $this->db->escape($record['item_type']) . "',
								'" . $this->db->escape($record['product_sku']) . "',
								'" . $this->db->escape($record['unit_of_measure']) . "',
								'" . $this->db->escape($record['quantity_from']) . "',
								'" . $this->db->escape($record['quantity_to']) . "',
								'" . $this->db->escape($record['item_price']) . "',
								'" . $this->db->escape($record['equivalent_qty']) . "',
								'" . $this->db->escape($record['qty_base_uom']) . "',
								'" . $this->db->escape($record['sequence_number']) . "',
								'" . $this->db->escape($record['row_id']) . "'
						)";
				}

				$insert_query = "INSERT INTO oc_price_list  (
						price_sheet_id, item_type, product_sku, unit_of_measure, quantity_from, quantity_to, item_price, equivalent_qty, qty_base_uom, sequence_number, row_id
				) VALUES " . implode(",", $insert_values);

				$this->db->query($insert_query);
			}
		}
	}

	public function insertPriceSheetsMaster() {
		ini_set('max_execution_time', 1200);
		$result = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			// Get count of records before insertion
			$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_price_sheet_master");
			$cnt_before = $query_bfr_cnt->row['bfr_cnt'];

			$batch_size = 10000; // Define batch size for each iteration
			$offset = 0; // Start from the first record

			while (true) {
				$query = "SELECT  * FROM SOP10110 ORDER BY DEX_ROW_ID OFFSET $offset ROWS FETCH NEXT $batch_size ROWS ONLY";

				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$data = [];
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					//PRCSHID	DESCEXPR	NTPRONLY	ACTIVE	STRTDATE	ENDDATE	NOTEINDX	PROMO	CURNCYID	DEX_ROW_ID
					//price_sheet_id	description	is_ntpr_only	is_active	start_date	end_date	note_index	is_promo	currency_id	row_id
					$prcsht_id = preg_replace('/[^\x20-\x7E]/', '', trim($row['PRCSHID']));
					$description = preg_replace('/[^\x20-\x7E]/', '', trim($row['DESCEXPR']));
					$note_index = preg_replace('/[^\x20-\x7E]/', '', trim($row['NOTEINDX']));
					$currency_id = preg_replace('/[^\x20-\x7E]/', '', trim($row['CURNCYID']));

					$data[] = [
							'price_sheet_id' => $prcsht_id,
							'description' => $description,
							'is_ntpr_only' => trim($row['NTPRONLY']),
							'is_active' => trim($row['ACTIVE']),
							'start_date' => $row['STRTDATE'],
							'end_date' => $row['ENDDATE'],
							'note_index' => $note_index,
							'is_promo' => trim($row['PROMO']),
							'currency_id' => $currency_id,
							'row_id' => trim($row['DEX_ROW_ID'])
					];			
				}

				if (empty($data)) {
					break; // No more data to fetch
				}

				// Process and insert the batch into webstore
				$this->insertPriceSheetsMasterData($data);
				$offset += $batch_size; // Move to the next batch
			}

			// Get count of records after insertion
			$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_price_sheet_master");
			$cnt_after = $query_aft_cnt->row['aft_cnt'];

			// Free resources
			sqlsrv_free_stmt($stmt);
			$this->model_extension_sqlsrv_connect_ms_sql->close();

			// Calculate records added
			$total_records_added = $cnt_after - $cnt_before;
			return [
				'code' => '200',
				'msg' => "Number of records before: $cnt_before \n Number of records after: $cnt_after \n Number of records added: $total_records_added"
			];

		} else {
			$result = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $result;
		}

		return $result;
	}

	private function insertPriceSheetsMasterData(array $data) {
		if (!empty($data)) {
			$data_chunks = array_chunk($data, 500); // Chunk data into groups of 500

			foreach ($data_chunks as $chunk) {
				$insert_values = [];
				foreach ($chunk as $record) {
					//price_sheet_id	description	is_ntpr_only	is_active	start_date	end_date	note_index	is_promo	currency_id	row_id	
						$insert_values[] = "(
								'" . $this->db->escape($record['price_sheet_id']) . "',
								'" . $this->db->escape($record['description']) . "',
								'" . $this->db->escape($record['is_ntpr_only']) . "',
								'" . $this->db->escape($record['is_active']) . "',
								'" . $record['start_date']->format('Y-m-d H:i:s') . "',
								'" . $record['end_date']->format('Y-m-d H:i:s') . "',
								'" . $this->db->escape($record['note_index']) . "',
								'" . $this->db->escape($record['is_promo']) . "',
								'" . $this->db->escape($record['currency_id']) . "',
								'" . $this->db->escape($record['row_id']) . "'
						)";
				}

				$insert_query = "INSERT INTO oc_price_sheet_master (price_sheet_id, description, is_ntpr_only, is_active, start_date, end_date, note_index, is_promo, currency_id, row_id) VALUES " . implode(",", $insert_values);

				$this->db->query($insert_query);
			}
		}
	}

	public function insertPriceSheetsLink(): array {
		ini_set('max_execution_time', 1200);
		$result = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			// Get count of records before insertion
			$cnt_before = '';
			$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_customer_price_sheet");
			if($query_bfr_cnt) {
				$cnt_before = $query_bfr_cnt->row['bfr_cnt'];
			}

			$batch_size = 10000; // Define batch size for each iteration
			$offset = 0; // Start from the first record

			while (true) {
				$query = "SELECT * FROM RM00500 ORDER BY DEX_ROW_ID OFFSET $offset ROWS FETCH NEXT $batch_size ROWS ONLY";

				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$data = [];
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					//PRCSHID	PRODTCOD	LINKCODE	SEQNUMBR	PSSEQNUM	DEX_ROW_ID
					//CT-LOADLIFTER  	C	LOADLIFTER     	49152	16384	96430
					$price_sheet_id = preg_replace('/[^\x20-\x7E]/', '', trim($row['PRCSHID']));
					$promo_detail_code = preg_replace('/[^\x20-\x7E]/', '', trim($row['PRODTCOD']));
					$customer_group_id = preg_replace('/[^\x20-\x7E]/', '', trim($row['LINKCODE']));
					//price_sheet_id	promo_detail_code	customer_group_id	sequence_number	price_sheet_sequence	dex_row_id	

					$data[] = [
							'price_sheet_id' => $price_sheet_id,
							'promo_detail_code' => $promo_detail_code,
							'customer_group_id' => $customer_group_id,
							'sequence_number' => trim($row['SEQNUMBR']),
							'price_sheet_sequence' => trim($row['PSSEQNUM']),
							'dex_row_id' => $row['DEX_ROW_ID']
					];			
				}

				if (empty($data)) {
					break; // No more data to fetch
				}

				// Process and insert the batch into webstore
				$this->insertPriceSheetsLinkData($data);
				$offset += $batch_size; // Move to the next batch
			}

			// Get count of records after insertion
			$cnt_after = '';
			$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_customer_price_sheet");
			if($query_aft_cnt) {
				$cnt_after = $query_aft_cnt->row['aft_cnt'];
			}

			// Free resources
			sqlsrv_free_stmt($stmt);
			$this->model_extension_sqlsrv_connect_ms_sql->close();

			// Calculate records added
			$total_records_added = $cnt_after - $cnt_before;
			return [
				'code' => '200',
				'msg' => "Number of records before: $cnt_before \n Number of records after: $cnt_after \n Number of records added: $total_records_added"
			];

		} else {
			$result = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $result;
		}

		return $result;
	}

	private function insertPriceSheetsLinkData(array $data): void {
		if (!empty($data)) {
			$data_chunks = array_chunk($data, 500); // Chunk data into groups of 500

			foreach ($data_chunks as $chunk) {
				$insert_values = [];
				foreach ($chunk as $record) {
					//	promo_detail_code	customer_group_id	sequence_number	price_sheet_sequence	dex_row_id
						$insert_values[] = "(
								'" . $this->db->escape($record['price_sheet_id']) . "',
								'" . $this->db->escape($record['promo_detail_code']) . "',
								'" . $this->db->escape($record['customer_group_id']) . "',
								'" . $this->db->escape($record['sequence_number']) . "',
								'" . $this->db->escape($record['price_sheet_sequence']) . "',
								'" . $this->db->escape($record['dex_row_id']) . "'
						)";
				}

				$insert_query = "INSERT INTO oc_customer_price_sheet (price_sheet_id, promo_detail_code,	customer_group_id, sequence_number, price_sheet_sequence, dex_row_id) VALUES " . implode(",", $insert_values);

				try {
					// Execute the query
					$this->db->query($insert_query);
				} catch (Exception $e) {
					// Log the error
					error_log("Database error: " . $e->getMessage());

					// Optionally, rethrow the exception or handle it as needed
					throw new Exception("Failed to insert price sheet data: " . $e->getMessage());
				}
			}
		}
	}

	public function insertItemPriceGroup(): array {
		ini_set('max_execution_time', 1200);
		$result = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			// Get count of records before insertion
			$cnt_before = '';
			$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_pricegroup_to_item");
			if($query_bfr_cnt) {
				$cnt_before = $query_bfr_cnt->row['bfr_cnt'];
			}

			$batch_size = 10000; // Define batch size for each iteration
			$offset = 0; // Start from the first record

			while (true) {
				$query = "SELECT * FROM IV10400 ORDER BY DEX_ROW_ID OFFSET $offset ROWS FETCH NEXT $batch_size ROWS ONLY";

				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$data = [];
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					$price_group_id = preg_replace('/[^\x20-\x7E]/', '', trim($row['PRCGRPID']));
					$item_number = preg_replace('/[^\x20-\x7E]/', '', trim($row['ITEMNMBR']));

					$data[] = [
							'price_group_id' => $price_group_id,
							'item_number' => $item_number,
							'sequence_number' => trim($row['SEQNUMBR']),
							'dex_row_id' => trim($row['DEX_ROW_ID'])
					];			
				}

				if (empty($data)) {
					break; // No more data to fetch
				}

				// Process and insert the batch into webstore
				$this->insertPriceGroupItemData($data);
				$offset += $batch_size; // Move to the next batch
			}

			// Get count of records after insertion
			$cnt_after = '';
			$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_pricegroup_to_item");
			if($query_aft_cnt) {
				$cnt_after = $query_aft_cnt->row['aft_cnt'];
			}

			// Free resources
			sqlsrv_free_stmt($stmt);
			$this->model_extension_sqlsrv_connect_ms_sql->close();

			// Calculate records added
			$total_records_added = $cnt_after - $cnt_before;
			return [
				'code' => '200',
				'msg' => "Number of records before: $cnt_before \n Number of records after: $cnt_after \n Number of records added: $total_records_added"
			];

		} else {
			$result = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $result;
		}

		return $result;
	}

	private function insertPriceGroupItemData(array $data): void {
		if (!empty($data)) {
			$data_chunks = array_chunk($data, 500); // Chunk data into groups of 500

			foreach ($data_chunks as $chunk) {
				$insert_values = [];
				foreach ($chunk as $record) {
					//price_group_id	item_number	sequence_number	dex_row_id	
						$insert_values[] = "(
								'" . $this->db->escape($record['price_group_id']) . "',
								'" . $this->db->escape($record['item_number']) . "',
								'" . $this->db->escape($record['sequence_number']) . "',
								'" . $this->db->escape($record['dex_row_id']) . "'
						)";
				}

				$insert_query = "INSERT INTO oc_pricegroup_to_item (price_group_id, item_number, sequence_number, dex_row_id) VALUES " . implode(",", $insert_values);

				try {
					// Execute the query
					$this->db->query($insert_query);
				} catch (Exception $e) {
					// Log the error
					error_log("Database error: " . $e->getMessage());

					// Optionally, rethrow the exception or handle it as needed
					throw new Exception("Failed to insert price sheet data: " . $e->getMessage());
				}
			}
		}
	}

	public function insertPriceBookLink(): array {
		ini_set('max_execution_time', 1200);
		$result = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect();

		if ($connection) {
			// Get count of records before insertion
			$cnt_before = '';
			$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_pricebook_to_customer");
			if($query_bfr_cnt) {
				$cnt_before = $query_bfr_cnt->row['bfr_cnt'];
			}

			$batch_size = 10000; // Define batch size for each iteration
			$offset = 0; // Start from the first record

			while (true) {
				$query = "SELECT * FROM SOP10205 ORDER BY DEX_ROW_ID OFFSET $offset ROWS FETCH NEXT $batch_size ROWS ONLY";

				$stmt = sqlsrv_query($connection, $query);

				if ($stmt === false) {
					$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
					$res_arr = ['code' => '500', 'msg' => 'Unable to fetch records from GP'];
					return $res_arr;
				}

				$data = [];
				//PRCBKID	PRODTCOD	LINKCODE	SEQNUMBR	DEX_ROW_ID
				while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
					$pricebook_id = preg_replace('/[^\x20-\x7E]/', '', trim($row['PRCBKID']));
					$promo_detail_code = preg_replace('/[^\x20-\x7E]/', '', trim($row['PRODTCOD']));
					$link_code = preg_replace('/[^\x20-\x7E]/', '', trim($row['LINKCODE']));

					$data[] = [
							'pricebook_id' => $pricebook_id,
							'promo_detail_code' => $promo_detail_code,
							'link_code' => $link_code,
							'sequence_number' => trim($row['SEQNUMBR']),
							'dex_row_id' => trim($row['DEX_ROW_ID'])
					];			
				}

				if (empty($data)) {
					break; // No more data to fetch
				}

				// Process and insert the batch into webstore
				$this->insertPriceBookLinkData($data);
				$offset += $batch_size; // Move to the next batch
			}

			// Get count of records after insertion
			$cnt_after = '';
			$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_pricebook_to_customer");
			if($query_aft_cnt) {
				$cnt_after = $query_aft_cnt->row['aft_cnt'];
			}

			// Free resources
			sqlsrv_free_stmt($stmt);
			$this->model_extension_sqlsrv_connect_ms_sql->close();

			// Calculate records added
			$total_records_added = $cnt_after - $cnt_before;
			return [
				'code' => '200',
				'msg' => "Number of records before: $cnt_before \n Number of records after: $cnt_after \n Number of records added: $total_records_added"
			];

		} else {
			$result = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $result;
		}

		return $result;
	}

	private function insertPriceBookLinkData(array $data): void {
		if (!empty($data)) {
			$data_chunks = array_chunk($data, 500); // Chunk data into groups of 500

			foreach ($data_chunks as $chunk) {
				$insert_values = [];
				foreach ($chunk as $record) {
					//pricebook_id	promo_detail_code	link_code	sequence_number	dex_row_id	
						$insert_values[] = "(
								'" . $this->db->escape($record['pricebook_id']) . "',
								'" . $this->db->escape($record['promo_detail_code']) . "',
								'" . $this->db->escape($record['link_code']) . "',
								'" . $this->db->escape($record['sequence_number']) . "',
								'" . $this->db->escape($record['dex_row_id']) . "'
						)";
				}

				$insert_query = "INSERT INTO oc_pricebook_to_customer (pricebook_id, promo_detail_code,	link_code, sequence_number, dex_row_id) VALUES " . implode(",", $insert_values);

				try {
					// Execute the query
					$this->db->query($insert_query);
				} catch (Exception $e) {
					// Log the error
					error_log("Database error: " . $e->getMessage());
					// Optionally, rethrow the exception or handle it as needed
					throw new Exception("Failed to insert price sheet data: " . $e->getMessage());
				}
			}
		}
	}

	//
	public function getInventoryLevelProduct(string $model_no): array {
		$result = [];
		$query = $this->db->query("SELECT w.description as warehouse, COALESCE(pl.qty_on_hand, '') AS quantity FROM oc_warehouse w LEFT JOIN oc_product_to_location pl ON w.location_code = pl.location_code AND pl.item_number = '".$model_no."' WHERE w.show_in_webstore = 1");

		if($query && $query->num_rows > 0) {
			foreach($query->rows as $values) {
				$result[] = $values;
			}
		}

		return $result;
	}

	//Calculate product netprice for the customer after applying discount if applicable
	public function calculateProductsNetPrice(array $skus, string $customerGroup): array 
	{
		if (empty($skus)) return [];

		// Initialize results with default values for all SKUs
		$results = array_fill_keys($skus, [
			'base_price' => 0.0,
			'net_price' => 0.0,
			'discounts' => []
		]);
		$basebookGroup = 'BASEBOOK'; //base price book code

		//sanitize array of models and customer code with mysql escape string method
		$model_no_arr = array_map(function($m) {
			return "'" . $this->db->escape($m) . "'";
		}, $skus);
		$model_no = implode(",", $model_no_arr); //convert array of models into comma separated string
		$customerGroup = $this->db->escape($customerGroup);
		
		// Step 1: Get Latest Base Price Per SKU for BASEBOOK customer group
		$baseSql = "SELECT opl.product_sku, opl.item_price,opsm.start_date FROM oc_price_list opl
								INNER JOIN oc_price_sheet_master opsm ON opl.price_sheet_id = opsm.price_sheet_id
								INNER JOIN oc_customer_price_sheet ocps ON opl.price_sheet_id = ocps.price_sheet_id
								WHERE opl.product_sku IN ($model_no) AND ocps.customer_group_id = '".$basebookGroup."' AND opsm.end_date >= CURDATE()";

		$query1 = $this->db->query($baseSql);

		if ($query1 && $query1->num_rows > 0) {
			$rawData = $query1->rows;
			$latestPrices = [];

			foreach ($rawData as $row) {
				$sku = $row['product_sku'];
				$startDate = $row['start_date'];

				// If SKU not seen yet OR this row has a later start_date
				if (!isset($latestPrices[$sku]) || strtotime($startDate) > strtotime($latestPrices[$sku]['start_date'])) {
					$results[$row['product_sku']]['base_price'] = (float)$row['item_price'];
					$results[$row['product_sku']]['net_price'] = (float)$row['item_price'];
				}
			}
		}

		// Step 2: Fetch Price Group Mapping for SKUs
		$groupStmt = "SELECT item_number, price_group_id
		FROM oc_pricegroup_to_item
		WHERE item_number IN ($model_no)";

		$query2 = $this->db->query($groupStmt);
		$skuToGroups = [];
		if ($query2 && $query2->num_rows > 0) {
			foreach ($query2->rows as $row) { 
				$skuToGroups[$row['item_number']][] = $row['price_group_id'];
				$skuToGroups_qt[$row['item_number']][] = "'".$row['price_group_id']."'";
			}
		}

		// Step 3: Prepare list of SKUs + Price Groups for Discount Lookup
		$discountSkus = $model_no_arr;
		foreach ($skuToGroups_qt as $groups) {
			$discountSkus = array_merge($discountSkus, $groups);
		}
		$discountSkus = array_unique($discountSkus);
		$discountSkus = implode(",", $discountSkus);

		// Step 4: Fetch all applicable discounts from price books which are linked to customer code
		$priceBookDisc = "SELECT opl.product_sku, opl.item_price FROM oc_price_list opl 
		JOIN oc_customer_price_sheet psc ON psc.price_sheet_id = opl.price_sheet_id 
		JOIN oc_pricebook_to_customer pbc ON pbc.pricebook_id = psc.customer_group_id 
		WHERE opl.product_sku IN($discountSkus) AND pbc.link_code = '".$customerGroup."'";

		$pbd_query = $this->db->query($priceBookDisc);

		// Map all discounts by SKU
		$priceBookDiscountMap = [];
		if ($pbd_query && $pbd_query->num_rows > 0) {
			foreach ($pbd_query->rows as $row) { 
				$priceBookDiscountMap[$row['product_sku']][] = (float)$row['item_price'];
			}
		}
		
		
		// Step 5: Fetch All Applicable Discounts (Product + Group Level)
		$discountStmt = "SELECT opl.product_sku, opl.item_price FROM oc_price_list opl
            JOIN oc_price_sheet_master opsm ON opl.price_sheet_id = opsm.price_sheet_id
            JOIN oc_customer_price_sheet ocps ON opl.price_sheet_id = ocps.price_sheet_id
            WHERE opl.product_sku IN ($discountSkus) AND ocps.customer_group_id = '".$customerGroup."'
            AND opsm.end_date >= CURDATE() ORDER BY opsm.start_date DESC";

		$query3 = $this->db->query($discountStmt);

		// Map all discounts by SKU
		$skuDiscountMap = [];
		if ($query3 && $query3->num_rows > 0) {
			foreach ($query3->rows as $row) { 
				$skuDiscountMap[$row['product_sku']][] = (float)$row['item_price'];
			}
		}

		// Step 6: Apply All Discounts (Product + Group Based)
		foreach ($skus as $sku) {
			$basePrice = $results[$sku]['base_price'];
			if ($basePrice <= 0) continue;

			$price = round($basePrice, 2);
			$discounted_price = [];
			$applied = [];


			// Apply direct discounts on SKU
			if (!empty($skuDiscountMap[$sku])) {
				foreach ($skuDiscountMap[$sku] as $percent) {
					if ($percent >= 100) continue;
					$applied[] = $percent;
					$discounted_price[] = $price * ($percent / 100);  // Custom logic
				}
			}

			// Apply direct price book discounts on SKU
			if (!empty($priceBookDiscountMap[$sku])) {
				foreach ($priceBookDiscountMap[$sku] as $percent) {
					if ($percent >= 100) continue;
					$applied[] = $percent;
					$discounted_price[] = $price * ($percent / 100);  // Custom logic
				}
			}

			// Apply discounts from price group IDs
			if (!empty($skuToGroups[$sku])) {
				foreach ($skuToGroups[$sku] as $grp) {
					//apply price group id discount linked to price sheet
					if (!empty($skuDiscountMap[$grp])) {
						foreach ($skuDiscountMap[$grp] as $percent) {
							if ($percent >= 100) continue;
							$applied[] = $percent;
							$discounted_price[] = $price * ($percent / 100);  // Custom logic
						}
					}

					//apply price group id discount linked to price book
					if (!empty($priceBookDiscountMap[$grp])) {
						foreach ($priceBookDiscountMap[$grp] as $percent) {
							if ($percent >= 100) continue;
							$applied[] = $percent;
							$discounted_price[] = $price * ($percent / 100);  // Custom logic
						}
					}
				}
			}

			$price = (isset($discounted_price) && !empty($discounted_price)) ? min($discounted_price) : $price;

			// Final pricing after applying all layered discounts
			$results[$sku]['net_price'] = round($price, 2);
			$results[$sku]['discounts'] = $applied;
		}
		
		return $results;
	}

	//Get product base price user not logged in
	public function calculateProductsBasePrice(array $skus): array
	{
		if (empty($skus)) return [];

		// Initialize results with default values for all SKUs
		$results = array_fill_keys($skus, [
			'base_price' => 0.0,
			'net_price' => 0.0,
			'discounts' => []
		]);
		$basebookGroup = 'BASEBOOK'; //base price book code

		//sanitize array of models with mysql escape string method
		$model_no_arr = array_map(function($m) {
			return "'" . $this->db->escape($m) . "'";
		}, $skus);
		$model_no = implode(",", $model_no_arr); //convert array of models into comma separated string
		
		// Step 1: Get Latest Base Price Per SKU for BASEBOOK customer group
		$baseSql = "SELECT opl.product_sku, opl.item_price,opsm.start_date FROM oc_price_list opl
								INNER JOIN oc_price_sheet_master opsm ON opl.price_sheet_id = opsm.price_sheet_id
								INNER JOIN oc_customer_price_sheet ocps ON opl.price_sheet_id = ocps.price_sheet_id
								WHERE opl.product_sku IN ($model_no) AND ocps.customer_group_id = '".$basebookGroup."' AND opsm.end_date >= CURDATE()";

		$query1 = $this->db->query($baseSql);

		if ($query1 && $query1->num_rows > 0) {
			$rawData = $query1->rows;
			$latestPrices = [];

			foreach ($rawData as $row) {
				$sku = $row['product_sku'];
				$startDate = $row['start_date'];

				// If SKU not seen yet OR this row has a later start_date
				if (!isset($latestPrices[$sku]) || strtotime($startDate) > strtotime($latestPrices[$sku]['start_date'])) {
					$results[$row['product_sku']]['base_price'] = round((float)$row['item_price'], 2);
					$results[$row['product_sku']]['net_price'] = round((float)$row['item_price'], 2);
				}
			}
		}

		return $results;
	}
	
}
