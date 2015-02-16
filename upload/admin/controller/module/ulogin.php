<?php
class ControllerModuleUlogin extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('module/ulogin');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_extension_module->addModule('ulogin', $this->request->post);
			} else {
				$this->model_extension_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		if (!isset($this->request->get['module_id'])) {
			$data['text_edit'] = $this->language->get('text_create');
		} else {
			$data['text_edit'] = $this->language->get('text_edit');
		}
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_module_description'] = sprintf($this->language->get('text_module_description'), $this->url->link('module/ulogin_sets', 'token=' . $this->session->data['token'], 'SSL'));
		$data['text_type_offline'] = $this->language->get('text_type_offline');
		$data['text_type_online'] = $this->language->get('text_type_online');
		$data['text_type_online_edit_page'] = $this->language->get('text_type_online_edit_page');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_uloginid'] = $this->language->get('entry_uloginid');
		$data['entry_uloginid_pl'] = $this->language->get('entry_uloginid_pl');
		$data['entry_type'] = $this->language->get('entry_type');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
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

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('module/ulogin', 'token=' . $this->session->data['token'], 'SSL')
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('module/ulogin', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], 'SSL')
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('module/ulogin', 'token=' . $this->session->data['token'], 'SSL');
		} else {
			$data['action'] = $this->url->link('module/ulogin', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], 'SSL');
		}

		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_extension_module->getModule($this->request->get['module_id']);
		}

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}

		if (isset($this->request->post['uloginid'])) {
			$data['uloginid'] = $this->request->post['uloginid'];
		} elseif (!empty($module_info)) {
			$data['uloginid'] = $module_info['uloginid'];
		} else {
			$data['uloginid'] = $this->config->get('uloginid');
		}

		if (isset($this->request->post['type'])) {
			$data['type'] = $this->request->post['type'];
		} elseif (!empty($module_info)) {
			$data['type'] = $module_info['type'];
		} else {
			$data['type'] = 'offline';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('module/ulogin.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/ulogin')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		return !$this->error;
	}


	public function install() {
		$this->load->model('extension/module');
		$this->load->model('design/layout');
		$this->load->model('module/ulogin');

		$code = 'ulogin';
		$layout_id = 0;

		// созание модулей с настройками по умолчанию
		$this->model_extension_module->addModule('ulogin', array(
			'name' => "account_offline",
			'uloginid' => $this->config->get('ulogin_sets_uloginid'),
			'type' => 'offline',
			'status' => '1',
		));
		$this->model_extension_module->addModule('ulogin', array(
			'name' => "account_lk_online",
			'uloginid' => $this->config->get('ulogin_sets_uloginid'),
			'type' => 'online_edit',
			'status' => '1',
		));

		$layouts = $this->model_design_layout->getLayouts();

		foreach ($layouts as $layout) {
			if ($layout["name"] == "Account") {
				$layout_id = $layout['layout_id'];
				break;
			}
		}

		if ($layout_id <= 0) {
			return;
		}

		$modules = $this->model_extension_module->getModulesByCode($code);

		foreach ($modules as $module) {
			if ($module['name'] == 'account_offline') {
				$data = array(
					'position' => 'content_top',
				);
			}
			if ($module['name'] == 'account_lk_online') {
				$data = array(
					'position' => 'content_bottom',
				);
			}
			$data['code'] = $code . '.' .  $module['module_id'];
			$data['layout_id'] = $layout_id;

			// установка модуля в макет
			$this->model_module_ulogin->addModuleInLayout($data);
		}
	}

	public function uninstall() {
	}
}