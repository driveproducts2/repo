<?php
class ModelCatalogCategory extends Model
{
	public function getCategory($category_id)
	{
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
		AND c.is_main = 1 AND c.parent_id = 0 AND c.status = '1'");

		return $query->row;
	}

	public function getCategories($parent_id = 0)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1' AND c.top != 1 ORDER BY c.sort_order, LCASE(cd.name)");

		return $query->rows;
	}

	public function getHomeCategories($parent_id = 0)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1' AND is_main = 1 AND c.top != 1 ORDER BY c.sort_order, LCASE(cd.name)");

		return $query->rows;
	}

	public function getTopCategories($parent_id = 0)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c.status = '1' AND c.top = 1 AND is_main = 1 ORDER BY c.sort_order, LCASE(cd.name)");

		return $query->rows;
	}

	public function getCategoryFilters($category_id)
	{
		$implode = array();

		$query = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "category_filter WHERE category_id = '" . (int)$category_id . "'");

		foreach ($query->rows as $result) {
			$implode[] = (int)$result['filter_id'];
		}

		$filter_group_data = array();

		if ($implode) {
			$filter_group_query = $this->db->query("SELECT DISTINCT f.filter_group_id, fgd.name, fg.sort_order FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_group fg ON (f.filter_group_id = fg.filter_group_id) LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY f.filter_group_id ORDER BY fg.sort_order, LCASE(fgd.name)");

			foreach ($filter_group_query->rows as $filter_group) {
				$filter_data = array();

				$filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY f.sort_order, LCASE(fd.name)");

				foreach ($filter_query->rows as $filter) {
					$filter_data[] = array(
						'filter_id' => $filter['filter_id'],
						'name'      => $filter['name']
					);
				}

				if ($filter_data) {
					$filter_group_data[] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter'          => $filter_data
					);
				}
			}
		}

		return $filter_group_data;
	}

	public function getCategoryLayoutId($category_id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0)
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}

	public function getPreNexProducts(int $category_id, int $product_id)
	{	
		$result = array();

		$sql = "SELECT p.product_id, pd.name, p.image, c.parent_id FROM oc_product p
		LEFT JOIN oc_product_description pd ON p.product_id = pd.product_id
		LEFT JOIN oc_product_to_category p2c ON p.product_id = p2c.product_id
		LEFT JOIN oc_category c ON c.category_id = p2c.category_id
		WHERE p.product_id < '".$product_id."'"; 
		if($category_id != 0) {
			$sql .= " AND c.parent_id = '".$category_id."'";
		}
		 
		$sql .= " ORDER BY p.product_id DESC LIMIT 1";

		$query = $this->db->query($sql);
		if($query && ($query->num_rows > 0)) {
			$result['previous'] = $query->row;
		} else {
			$result['previous'] = '';
		}

		$sql = "SELECT p.product_id, pd.name, p.image, c.parent_id FROM oc_product p
		LEFT JOIN oc_product_description pd ON p.product_id = pd.product_id
		LEFT JOIN oc_product_to_category p2c ON p.product_id = p2c.product_id
		LEFT JOIN oc_category c ON c.category_id = p2c.category_id
		WHERE p.product_id > '".$product_id."'"; 
		
		if($category_id != 0) {
			$sql .= " AND c.parent_id = '".$category_id."'";
		}
		 
		$sql .= " ORDER BY p.product_id ASC LIMIT 1";

		$query2 = $this->db->query($sql);
		if($query2->num_rows > 0) {
			$result['next'] = $query2->row;
		} else {
			$result['next'] = '';
		}
		return $result;
	}

	//method to insert new product categories in open cart db
	public function insertCategory() {
		$res_arr = [];
		$this->load->model('extension/sqlsrv/connect_ms_sql');
		$connection = $this->model_extension_sqlsrv_connect_ms_sql->connect(); //connect to GP SQL

		if ($connection) {
			$query = "SELECT ITMCLSCD, ITMCLSDC, ITEMTYPE, PRCLEVEL FROM IV40400";
			$result = sqlsrv_query($connection, $query);

			if ($result === false) {
				$this->log->write('SQLSRV Query Error: ' . print_r(sqlsrv_errors(), true));
				$res_arr = ['code' => '500', 'msg' => 'Enable to fetch records from GP'];
				return $res_arr;
			} else {
				$query_bfr_cnt = $this->db->query("SELECT COUNT(*) AS bfr_cnt FROM oc_category");
				$cnt_before = $query_bfr_cnt->row['bfr_cnt'];

				$exist_catg_arr = [];
				$query = $this->db->query("SELECT name FROM oc_category");

				foreach($query->rows as $catg) {
					$exist_catg_arr[] = $catg['name'];
				} 

				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$get_class_code = trim($this->db->escape($row['ITMCLSCD']));
					$get_class_code_desc = trim($this->db->escape($row['ITMCLSDC']));
					$get_class_code_type = trim($this->db->escape($row['ITEMTYPE']));

					if(!in_array($get_class_code, $exist_catg_arr)) {
						$this->db->query("INSERT INTO `oc_category` (`name`, `item_type`) VALUES ('".$get_class_code."', '".$get_class_code_type."')");

						$last_id = $this->db->getLastId();
						if($last_id > 0) {
							$this->db->query("INSERT INTO `oc_category_description` (`category_id`, `language_id`, `name`, `description`) VALUES ('".$last_id."', '1', '".$get_class_code."', '".$get_class_code_desc."')");
						}
					}
				}

				$query_aft_cnt = $this->db->query("SELECT COUNT(*) AS aft_cnt FROM oc_category");
				$cnt_after = $query_aft_cnt->row['aft_cnt'];

				sqlsrv_free_stmt($result);
				$this->model_extension_sqlsrv_connect_ms_sql->close();
				
				$catg_added = $cnt_after - $cnt_before;
				$res_arr = ["code" => "500", 'msg' => "Number of records before : ".$cnt_before." \n Number of records after : ". $cnt_after." \n Number of categories added : ". $catg_added];

				return $res_arr;
			}
		} else {
			$this->log->write('Database Connection Error: Unable to connect');
			$res_arr = ['code' => '500', 'msg' => 'Failed to connect database'];
			return $res_arr;
		}
	}
}
