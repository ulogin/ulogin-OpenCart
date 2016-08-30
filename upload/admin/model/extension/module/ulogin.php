<?php
class ModelExtensionModuleUlogin extends Model {
	public function addModuleInLayout($data) {
		$layout_id = $data['layout_id'];
		$code = $data['code'];
		$position = $data['position'];
		$sort_order = isset($data['sort_order']) ? $data['sort_order'] : 0;

		$sql = "INSERT INTO " . DB_PREFIX . "layout_module
			SET layout_id = '" . (int)$layout_id . "',
			code = '" . $this->db->escape($code) . "',
			position = '" . $this->db->escape($position) . "',
			sort_order = '" . (int)$sort_order . "'";

		$this->db->query($sql);
	}
}