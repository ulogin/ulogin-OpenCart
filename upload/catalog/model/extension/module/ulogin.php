<?php
class ModelExtensionModuleUlogin extends Model {
	/**
	 * Проверка, есть ли пользователь с указанным id в базе
	 * @param $u_id
	 * @return bool
	 */
	public function checkUserId ($u_id) {
		$sql = "SELECT customer_id
				FROM  " . DB_PREFIX . "customer
				WHERE customer_id = '" . (int)$u_id . "'";

		$query = $this->db->query($sql);

		if ($query->num_rows) {
			return true;
		}
		return false;
	}


//--------------------
	/**
	 * Получение id пользователя по email
	 * @param string $email
	 * @return int|bool
	 */
	public function getUserIdByEmail ($email = '') {
		$sql = "SELECT customer_id
				FROM  " . DB_PREFIX . "customer
				WHERE email = '" . $this->db->escape($email) . "'";

		$query = $this->db->query($sql);

		if ($query->row) {
			return $query->row['customer_id'];
		}
		return false;
	}


//--------------------
	/**
	 * Получение данных о пользователе из таблицы ulogin_users по identity или user_id
	 * @param $data
	 * @return bool|mixed
	 */
	public function getUloginUserItem ($data = array()) {
		if (!is_array($data) || empty($data)) { return false; }

		$sql = "SELECT *
				FROM " . DB_PREFIX . "ulogin";

		if (!($sql_where = $this->addWhere($data))) { return false; }

		$sql .= " WHERE $sql_where";

		$query = $this->db->query($sql);

		if ($query->row) {
			return $query->row;
		}
		return false;
	}


//--------------------------------
	/** Получение условия where для массива данных $fields
	 * @param array $fields
	 * @return string
	 */
	private function addWhere ($fields = array(), $delimiter = 'AND') {
		if (!is_array($fields) || empty($fields)) { return ''; }
		$i = 0;
		$sql = '';

		foreach ($fields as $field => $value) {
			if ($i > 0) {
				$sql .= " $delimiter ";
			}

			$sql .= "$field = '" . $this->db->escape($value) . "'";
			$i++;
		}

		return $sql;
	}


//--------------------
	/**
	 * Получение массива соцсетей пользователя по значению поля $user_id
	 * @param int $user_id
	 * @return array|bool
	 */
	public function getUloginUserNetworks ($user_id = 0) {
		$sql = "SELECT network
				FROM " . DB_PREFIX . "ulogin
				WHERE user_id = '" . (int)$user_id . "'";

		$query = $this->db->query($sql);

		if (!$query->rows) { return false; }

		foreach ($query->rows as $row)
		{
			$networks[] = $row["network"];
		}

		return $networks;
	}


//--------------------
	/**--
	 * Удаление данных о пользователе из таблицы ulogin_user
	 * @param int $user_id
	 * @return bool
	 */
	public function deleteUloginAccount ($data = array()) {
		if (!is_array($data) || empty($data)) { return false; }

		$sql = "DELETE FROM " . DB_PREFIX . "ulogin";

		if (!($sql_where = $this->addWhere($data))) { return false; }

		$sql .= " WHERE $sql_where";
		$this->db->query($sql);
		return true;
	}


//--------------------
	/**
	 * Добавление данных о пользователе в таблицы ulogin_user
	 * @param array $data
	 * @return bool
	 */
	public function addUloginAccount ($data = array()) {
		if (!is_array($data)
		    || empty($data)
		    || !(array_key_exists('user_id', $data)
				&& array_key_exists('identity', $data)
				&& array_key_exists('network', $data))) {
			return false;
		}

		$sql = "INSERT INTO " . DB_PREFIX . "ulogin";

		if (!($sql_set = $this->addWhere($data, ','))) { return false; }

		$sql .= " SET $sql_set";
		$this->db->query($sql);
		return true;
	}


//--------------------
	/**
	 * Добавление данных о пользователе в таблицы ulogin_user
	 * @param $group_id
	 */
	public function setUserGroup($group_id, $user_id = 0) {
		$this->db->query("UPDATE " . DB_PREFIX . "customer SET customer_group_id = '" . (int)$group_id . "' WHERE customer_id = '" . (int)$user_id . "'");
	}
}