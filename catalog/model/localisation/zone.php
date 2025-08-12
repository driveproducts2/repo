<?php
class ModelLocalisationZone extends Model {
	public function getZone($zone_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$zone_id . "' AND status = '1'");

		return $query->row;
	}

	public function getZonesByCountryId($country_id) {
		$zone_data = $this->cache->get('zone.' . (int)$country_id);

		if (!$zone_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "regions WHERE country_id = '" . (int)$country_id . "' ORDER BY region_en");

			$zone_data = $query->rows;

			$this->cache->set('zone.' . (int)$country_id, $zone_data);
		}

		return $zone_data;
	}

	public function getRegionId($country_id, $state) {
		if(!empty($state) && (!empty($country_id))) {
			$query = $this->db->query("SELECT id FROM oc_regions WHERE country_id = ".$country_id." AND (region LIKE '%".$state."%' COLLATE utf8_general_ci OR region_en = '".$state."' COLLATE utf8_general_ci) LIMIT 1");
			$state_id = isset($query->row['id']) ? $query->row['id'] : null;
		} else {
			$state_id = null;
		}
		return $state_id;
	}
}