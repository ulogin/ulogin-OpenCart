<?php
class ControllerModuleUloginSets extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('module/ulogin_sets');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('ulogin_sets', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_module_description'] = $this->language->get('text_module_description');

		$data['entry_uloginid'] = $this->language->get('entry_uloginid');
		$data['entry_uloginid_pl'] = $this->language->get('entry_uloginid_pl');
		$data['entry_user_group'] = $this->language->get('entry_user_group');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('module/ulogin_sets', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('module/ulogin_sets', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['ulogin_sets_uloginid'])) {
			$data['ulogin_sets_uloginid'] = $this->request->post['ulogin_sets_uloginid'];
		} else {
			$data['ulogin_sets_uloginid'] = $this->config->get('ulogin_sets_uloginid');
		}

		$this->load->model('sale/customer_group');
		$data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();

		if (isset($this->request->post['ulogin_sets_group'])) {
			$data['ulogin_sets_group'] = $this->request->post['ulogin_sets_group'];
		} else {
			$data['ulogin_sets_group'] = $this->config->get('ulogin_sets_group');
			if (!isset($data['ulogin_sets_group'])) {
				foreach ($data['customer_groups'] as $group) {
					if ($group['name'] == 'uLogin') {
						$data['ulogin_sets_group'] = $group['customer_group_id'];
						break;
					}
				}
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('module/ulogin_sets.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/ulogin_sets')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}


	public function install() {
		//создание таблицы ulogin

		$res = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "ulogin'");
		if ($res->num_rows == 0) {
			$this->db->query("
				CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ulogin` (
				  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				  `user_id` INTEGER UNSIGNED NOT NULL,
				  `identity` VARCHAR(255) NOT NULL,
				  `network` VARCHAR(50) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  INDEX (`user_id`),
				  INDEX (`identity`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
			");
		}


		//создание группы uLogin

		$this->load->model('sale/customer_group');
		$this->load->model('localisation/language');
		$this->load->model('setting/setting');

		$groups = $this->model_sale_customer_group->getCustomerGroups();

		foreach ($groups as $group) {
			if ($group['name'] == 'uLogin') {
				$group_id = $group['customer_group_id'];
				$this->model_setting_setting->editSetting('ulogin_sets', array('ulogin_sets_group' => $group_id, 'ulogin_sets_status' => 1));
				return;
			}
		}

		$descriptions = array();
		$languages = $this->model_localisation_language->getLanguages();

		foreach ($languages as $language) {
			$descriptions[$language['language_id']] =  array(
				'name' => 'uLogin',
				'description' => 'uLogin - группа, для зарегестрированных с помощью uLogin пользователей. Создана модулем uLogin.',
			);
		}

		$default_group_id = $this->config->get('config_customer_group_id');
		$default_group = $this->model_sale_customer_group->getCustomerGroup($default_group_id);

		$data = array(
			'approval' => $default_group['approval'],
			'sort_order' => $default_group['sort_order'],
			'customer_group_description' => $descriptions,
		);

		// установка параметров
		$group_id = $this->config->get('config_customer_group_id');
		$this->model_sale_customer_group->addCustomerGroup($data);

		$groups = $this->model_sale_customer_group->getCustomerGroups();
		foreach ($groups as $group) {
			if ($group['name'] == 'uLogin') {
				$group_id = $group['customer_group_id'];
				break;
			}
		}

		$this->model_setting_setting->editSetting('ulogin_sets', array('ulogin_sets_group' => $group_id, 'ulogin_sets_status' => 1));
	}

	public function uninstall() {
	}
}