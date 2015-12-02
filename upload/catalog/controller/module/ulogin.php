<?php
class ControllerModuleUlogin extends Controller {
	protected $u_data;
	protected $currentUserId;
	protected $userIsLogged;
	protected $doRedirect;
	protected $token;
	protected $redirect;
	private $userRegistration;

	public function index($setting) {
		$this->load->language('module/ulogin');

		$this->document->addScript('https://ulogin.ru/js/ulogin.js');
		$this->document->addScript('catalog/view/javascript/ulogin.js');
		$this->document->addStyle('catalog/view/theme/default/stylesheet/ulogin.css');
		$this->document->addStyle('https://ulogin.ru/css/providers.css');

		$url = $this->url->link('module/ulogin/login', '', 'SSL');
		$data['heading_title'] = $this->language->get('heading_title');
		$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';
		$data['redirect_uri'] = urlencode($url.  '&backurl=' . $route);
		$data['callback'] = 'uloginCallback';

		$html = '';
		$tpl = '';
		$ulogin_form = '';

		$currentUserId = $this->customer->isLogged();
		$this->currentUserId = $currentUserId ? $currentUserId : 0;
		$this->userIsLogged = $currentUserId ? true : false;

		$data['uloginid'] = !empty($setting['uloginid']) ? $setting['uloginid'] : $this->config->get('ulogin_sets_uloginid');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/ulogin/ulogin_form.tpl')) {
			$ulogin_form .= $this->load->view($this->config->get('config_template') . '/template/module/ulogin/ulogin_form.tpl', $data);
		} else {
			$ulogin_form .= $this->load->view('default/template/module/ulogin/ulogin_form.tpl', $data);
		}

		$data['ulogin_form'] = $ulogin_form;

		$ulogin_type = isset($setting['type']) ? $setting['type'] : '';
		$ulogin_status = $this->config->get('ulogin_sets_status');

		if (empty($ulogin_type) && !$this->userIsLogged && !empty($ulogin_status)) {
			$tpl .= '/template/module/ulogin/ulogin_form.tpl';

		} elseif (!$this->userIsLogged && $ulogin_type == 'offline') {
			$tpl .= '/template/module/ulogin/ulogin_panel.tpl';

		} elseif ($ulogin_type == 'online' || ($route == 'account/edit' && $ulogin_type == 'online_edit')) {

			$data['ulogin_profile_title'] = $this->language->get('ulogin_profile_title');
			$data['add_account'] = $this->language->get('add_account');
			$data['add_account_explain'] = $this->language->get('add_account_explain');
			$data['delete_account'] = $this->language->get('delete_account');
			$data['delete_account_explain'] = $this->language->get('delete_account_explain');

			$this->load->model('module/ulogin');
			$data['networks'] = $this->model_module_ulogin->getUloginUserNetworks($currentUserId);

			$tpl .= '/template/module/ulogin/ulogin_profile.tpl';

		} else return $html;

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . $tpl)) {
			$html .= $this->load->view($this->config->get('config_template') . $tpl, $data);
		} else {
			$html .= $this->load->view('default' . $tpl, $data);
		}

		return $html;
	}


	public function messager() {
		$this->load->language('module/ulogin');
		$html = '';

		$data_message['ulogin_success'] = isset($this->session->data['ulogin_success']) ? $this->session->data['ulogin_success'] : '';
		$data_message['ulogin_error_warning'] = isset($this->session->data['ulogin_error_warning']) ? $this->session->data['ulogin_error_warning'] : '';

		unset($this->session->data['ulogin_success']);
		unset($this->session->data['ulogin_error_warning']);

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/ulogin/ulogin_message.tpl')) {
			$html .= $this->load->view($this->config->get('config_template') . '/template/module/ulogin/ulogin_message.tpl', $data_message);
		} else {
			$html .= $this->load->view('default/template/module/ulogin/ulogin_message.tpl', $data_message);
		}

		return $html;
	}


	public function login() {
		$this->load->language('module/ulogin');

		$this->load->model('module/ulogin');
		$this->load->model('account/customer');

		$currentUserId = $this->customer->isLogged();
		$this->currentUserId = $currentUserId ? $currentUserId : 0;
		$this->userIsLogged = $currentUserId ? true : false;

		$title = '';
		$msg = '';

		$this->doRedirect = !(isset($this->request->post['isAjax']) ? true : false);

		if ($this->userIsLogged){
			$msg = 'ulogin_add_account_success';//'Аккаунт успешно добавлен';
		}

		$this->userRegistration = false;

		$this->uloginLogin($title, $msg);

		if (!$this->doRedirect) {
			exit;
		}
	}


	public function delete() {
		$this->load->language('module/ulogin');
		$this->load->model('module/ulogin');

		$currentUserId = $this->customer->isLogged();
		$this->currentUserId = $currentUserId ? $currentUserId : 0;
		$this->userIsLogged = $currentUserId ? true : false;

		$this->deleteAccount();
	}


//=================================================================================

	protected function uloginLogin ($title = '', $msg = '') {

		$this->u_data = $this->uloginParseRequest();

		if ( !$this->u_data ) {
			return;
		}

		try {
			$u_user_db = $this->model_module_ulogin->getUloginUserItem(array('identity' => $this->u_data['identity']));
			$user_id = 0;

			if ( $u_user_db ) {

				if ($this->model_module_ulogin->checkUserId($u_user_db['user_id'])) {
					$user_id = $u_user_db['user_id'];
				}

				if ( intval( $user_id ) > 0 ) {
					if ( !$this->checkCurrentUserId( $user_id ) ) {
						// если $user_id != ID текущего пользователя
						return;
					}
				} else {
					// данные о пользователе есть в ulogin_table, но отсутствуют в users. Необходимо переписать запись в ulogin_table и в базе users.
					$user_id = $this->newUloginAccount( $u_user_db );
				}

			} else {
				// пользователь НЕ обнаружен в ulogin_table. Необходимо добавить запись в ulogin_table и в базе users.
				$user_id = $this->newUloginAccount();
			}

			// обновление данных и Вход
			if ( $user_id > 0 ) {
				$this->loginUser( $user_id );

				$networks = $this->model_module_ulogin->getUloginUserNetworks( $user_id );
				$this->sendMessage(array(
					'title' => $title,
					'msg' => $msg,
					'networks' => $networks,
					'type' => 'success',
				));
				return;
			}

			$this->sendMessage (array(
				'title' => '',
				'msg' => 'ulogin_login_error',
				'type' => 'error'
			));
			return;
		}

		catch (Exception $e){
			$this->sendMessage (array(
				'title' => 'ulogin_db_error',//"Ошибка при работе с БД.",
				'msg' => "Exception: " . $e->getMessage(),
				'type' => 'error'
			));
			return;
		}
	}


	/**
	 * Отправляет данные как ответ на ajax запрос, если код выполняется в результате вызова callback функции,
	 * либо добавляет сообщение в сессию для вывода в режиме redirect
	 * @param array $params
	 */
	protected function sendMessage ($params = array()) {
		$params = array(
			'type' => isset($params['type']) ? $params['type'] : '',
			'script' => isset($params['script']) ? $params['script'] : '',
			'networks' => isset($params['networks']) ? $params['networks'] : '',
			'title' => $this->language->get($params['title']),
			'msg' => !is_array($params['msg'])
				? $this->language->get($params['msg'])
				: sprintf($this->language->get($params['msg'][0]), $params['msg'][1]),
		);

		if ($this->doRedirect) {
			$message = !empty($params['title']) ? '<strong>' . $params['title'] . '</strong>' : '';
			$message .= ((!empty($params['msg']) && !empty($message)) ? '<br/>' : '') . $params['msg'];

			if (!empty($params['script'])) {
				$token = !empty($params['script']['token']) ? $params['script']['token'] : '';
				$identity = !empty($params['script']['identity']) ? $params['script']['identity'] : '';
				$s = '';

				if  ($token && $identity) {
					$s = "uLogin.mergeAccounts('$token', '$identity');";
				} else if ($token) {
					$s = "uLogin.mergeAccounts('$token');";
				}

				if ($s) {
					$message .= "<script type=\"text/javascript\">$s</script>";
				}
			}

			if (!empty($message)) {
				if ($params['type'] == 'success') {
					unset($this->session->data['ulogin_success']);
					$this->session->data['ulogin_success'] = $message;
				} else {
					unset($this->session->data['ulogin_error_warning']);
					$this->session->data['ulogin_error_warning'] = $message;
				}
			}

			$redirect = isset($this->request->get['backurl']) ? $this->request->get['backurl'] : '';

			if (empty($redirect) || $redirect == 'account/logout') {
				$redirect = 'account/login';
			}

			$this->response->redirect($this->url->link($redirect, '', 'SSL'));

		} else {
			echo json_encode($params);
			exit;
		}
	}


	/**
	 * Добавление в таблицу uLogin
	 * @param $u_user_db - при непустом значении необходимо переписать данные в таблице uLogin
	 */
	protected function newUloginAccount($u_user_db = ''){
		$u_data = $this->u_data;

		if ($u_user_db) {
			// данные о пользователе есть в ulogin_user, но отсутствуют в users => удалить их
			$this->model_module_ulogin->deleteUloginAccount(array('id' => $u_user_db['id']));
		}

		$CMSuserId = $this->model_module_ulogin->getUserIdByEmail($u_data['email']);

		// $emailExists == true -> есть пользователь с таким email
		$user_id = 0;
		$emailExists = false;
		if ($CMSuserId) {
			$user_id = $CMSuserId; // id юзера с тем же email
			$emailExists = true;
		}

		// $userIsLogged == true -> пользователь онлайн
		$currentUserId = $this->currentUserId;
		$userIsLogged = $this->userIsLogged;

		if (!$emailExists && !$userIsLogged) {
			// отсутствует пользователь с таким email в базе -> регистрация в БД
			$user_id = $this->regUser();
			$this->addUloginAccount($user_id);
		} else {
			// существует пользователь с таким email или это текущий пользователь
			if (intval($u_data["verified_email"]) != 1){
				// Верификация аккаунта

				$this->sendMessage(
					array(
						'title' => 'ulogin_verify',//'Подтверждение аккаунта.',
						'msg' => 'ulogin_verify_text',
						'script' => array('token' => $this->token),
					)
				);
				return false;
			}

			$user_id = $userIsLogged ? $currentUserId : $user_id;

			$other_u = $this->model_module_ulogin->getUloginUserItem(array(
				'user_id' => $user_id,
			));

			if ($other_u) {
				// Синхронизация аккаунтов
				if(!$userIsLogged && !isset($u_data['merge_account'])){
					$this->sendMessage(
						array(
							'title' => 'ulogin_synch',//'Синхронизация аккаунтов.',
							'msg' => 'ulogin_synch_text',
							'script' => array('token' => $this->token, 'identity' => $other_u['identity']),
						)
					);
					return false;
				}
			}

			$this->addUloginAccount($user_id);
		}

		return $user_id;
	}



	/**
	 * Регистрация пользователя в БД users
	 * @return mixed
	 */
	protected function regUser(){
		$u_data = $this->u_data;

		$password = md5($u_data['identity'].time().rand());
		$password = substr($password, 0, 12);

		$customer_group_id = $this->config->get('ulogin_sets_group');
		if (empty($customer_group_id)) {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$data = array(
			'customer_group_id' => (int)$customer_group_id,
			'firstname'         => isset($u_data['first_name']) ? $u_data['first_name'] : '',
			'lastname'          => isset($u_data['last_name']) ? $u_data['last_name'] : '',
			'email'             => isset($u_data['email']) ? $u_data['email'] : '',
			'telephone'         => isset($u_data['phone']) ? $u_data['phone'] : '',
			'fax'               => '',
			'password'          => $password,
			'company'           => '',
			'address_1'         => '',
			'address_2'         => '',
			'city'              => isset($u_data['city']) ? $u_data['city'] : '',
			'postcode'          => '',
			'country_id'        => '',
			'zone_id'           => '',
		);

		$customer_id = $this->model_account_customer->addCustomer($data);

		$customer_info = $this->model_account_customer->getCustomer($customer_id);
		if (!$customer_info || !is_array($customer_info)) {
			$this->sendMessage (array(
				'title' => 'ulogin_reg_error',
				'msg' => 'ulogin_reg_error_text',
				'type' => 'error'
			));
			return false;
		}

		// присвоение группы
		if ($customer_info['customer_group_id'] != $customer_group_id && $customer_group_id > 0)	{
			$this->model_module_ulogin->setUserGroup($customer_group_id, $customer_id);
		}


		$this->userRegistration = true;

		return $customer_id;
	}



	/**
	 * Добавление записи в таблицу ulogin_user
	 * @param $user_id
	 * @return bool
	 */
	protected function addUloginAccount($user_id){
		$res = $this->model_module_ulogin->addUloginAccount(array(
			'user_id' => $user_id,
			'identity' => strval($this->u_data['identity']),
			'network' => $this->u_data['network'],
		));

		if (!$res) {
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error',//"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_add_account_error',//"Не удалось записать данные об аккаунте.",
				'type' => 'error'
			));
			return false;
		}

		return true;
	}



	/**
	 * Выполнение входа пользователя в систему по $user_id
	 * @param $u_user
	 * @param int $user_id
	 */
	protected function loginUser($user_id = 0) {
		$u_data = $this->u_data;

		$customer_info = $this->model_account_customer->getCustomer($user_id);

		// обновление данных
		if (empty($customer_info['telephone']) && !empty($u_data['phone']))	{
			$customer_info['telephone'] = $u_data['phone'];
		}

		$this->model_account_customer->editCustomer($customer_info);

		// если пользователь залогинен - выход из функции
		if ($this->userIsLogged) {
			return true;
		}

		// вход в систему
		$result = $this->customer->login($customer_info['email'], '', true);
		if (!$result) {
			$this->sendMessage (
				array(
					'title' => '',
					'msg' => 'ulogin_auth_error', // "Произошла ошибка при авторизации."
					'type' => 'error',
				)
			);
		}

		if ($this->userRegistration) {
			$activity_key = 'register';

			// удаление адреса только что зарегистрированного пользователя для создания нового адреса и нормальной валидации при оформлении заказа
			$this->load->model('account/address');

			$this->model_account_address->deleteAddress($customer_info['address_id']);

			// Default Shipping Address
			if (isset($this->session->data['shipping_address']['address_id']) && ($customer_info['address_id'] == $this->session->data['shipping_address']['address_id'])) {
				unset($this->session->data['shipping_address']);
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
			}

			// Default Payment Address
			if (isset($this->session->data['payment_address']['address_id']) && ($customer_info['address_id'] == $this->session->data['payment_address']['address_id'])) {
				unset($this->session->data['payment_address']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			}
		} else {
			$activity_key = 'login';
		}

		// Add to activity log
		$this->load->model('account/activity');

		$activity_data = array(
			'customer_id' => $this->customer->getId(),
			'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName()
		);

		$this->model_account_activity->addActivity($activity_key, $activity_data);

		return true;
	}



	/**
	 * Проверка текущего пользователя
	 * @param $user_id
	 */
	protected function checkCurrentUserId($user_id){
		$currentUserId = $this->currentUserId;
		if($this->userIsLogged) {
			if ($currentUserId == $user_id) {
				return true;
			}
			$this->sendMessage (
				array(
					'title' => '',
					'msg' => 'ulogin_account_not_available',
					'type' => 'error',
				)
			);
			return false;
		}
		return true;
	}



	/**
	 * Обработка ответа сервера авторизации
	 */
	protected function uloginParseRequest(){
		$this->token = isset($this->request->post['token']) ? $this->request->post['token'] : '';

		if (!$this->token) {
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_no_token_error', //"Не был получен токен uLogin.",
				'type' => 'error'
			));
			return false;
		}

		$s = $this->getUserFromToken();

		if (!$s){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_no_user_data_error', //"Не удалось получить данные о пользователе с помощью токена.",
				'type' => 'error'
			));
			return false;
		}

		$this->u_data = json_decode($s, true);

		if (!$this->checkTokenError()){
			return false;
		}

		return $this->u_data;
	}


	/**
	 * "Обменивает" токен на пользовательские данные
	 */
	protected function getUserFromToken() {
		$response = false;
		if ($this->token){
			$host = $_SERVER['SERVER_NAME'];
			$data = array(
				'cms' => 'opencart',
				'version' => VERSION,
			);
			$request = 'http://ulogin.ru/token.php?token=' . $this->token . '&host=' . $host . '&data='.base64_encode(json_encode($data));
			$response = $this->getResponse($request);
		}
		return $response;
	}

	/**
	 * Получение данных с помощью curl или file_get_contents
	 * @param string $url
	 * @return bool|mixed|string
	 */
	private function getResponse($url="", $do_abbort=true) {
		$result = false;

		if (in_array('curl', get_loaded_extensions())) {
			$request = curl_init($url);
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($request, CURLOPT_BINARYTRANSFER, 1);
			$result = curl_exec($request);
		}elseif (function_exists('file_get_contents') && ini_get('allow_url_fopen')){
			$result = file_get_contents($url);
		}

		if (!$result) {
			if ($do_abbort) {
				$this->sendMessage(array(
					'title' => 'ulogin_read_response_error',
					'msg' => 'ulogin_read_response_error_text',
					'type' => 'error'
				));
			}
			return false;
		}

		return $result;
	}


	/**
	 * Проверка пользовательских данных, полученных по токену
	 */
	protected function checkTokenError(){
		if (!is_array($this->u_data)){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_wrong_user_data_error', //"Данные о пользователе содержат неверный формат.",
				'type' => 'error'
			));
			return false;
		}

		if (isset($this->u_data['error'])){
			$strpos = strpos($this->u_data['error'],'host is not');
			if ($strpos){
				$this->sendMessage (array(
					'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
					'msg' => array('ulogin_host_address_error', sub($this->u_data['error'],intval($strpos)+12)),//"<i>ERROR</i>: адрес хоста не совпадает с оригиналом " . sub($this->u_data['error'],intval($strpos)+12),
					'type' => 'error'
				));
				return false;
			}
			switch ($this->u_data['error']){
				case 'token expired':
					$this->sendMessage (array(
						'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
						'msg' => 'ulogin_token_expired_error', //"<i>ERROR</i>: время жизни токена истекло",
						'type' => 'error'
					));
					break;
				case 'invalid token':
					$this->sendMessage (array(
						'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
						'msg' => 'ulogin_invalid_token_error', //"<i>ERROR</i>: неверный токен",
						'type' => 'error'
					));
					break;
				default:
					$this->sendMessage (array(
						'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
						'msg' => "<i>ERROR</i>: " . $this->u_data['error'],
						'type' => 'error'
					));
			}
			return false;
		}
		if (!isset($this->u_data['identity'])){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => array('ulogin_no_variable_error', 'identity'), //"В возвращаемых данных отсутствует переменная <b>identity</b>.",
				'type' => 'error'
			));
			return false;
		}
		if (!isset($this->u_data['email'])){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => array('ulogin_no_variable_error', 'email'), //"В возвращаемых данных отсутствует переменная <b>email</b>",
				'type' => 'error'
			));
			return false;
		}
		if (!isset($this->u_data['first_name'])){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => array('ulogin_no_variable_error', 'first_name'), //"В возвращаемых данных отсутствует переменная <b>first_name</b>",
				'type' => 'error'
			));
			return false;
		}
		if (!isset($this->u_data['last_name'])){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => array('ulogin_no_variable_error', 'last_name'), //"В возвращаемых данных отсутствует переменная <b>last_name</b>",
				'type' => 'error'
			));
			return false;
		}
		return true;
	}


//	/**
//	 * Гнерация логина пользователя
//	 * в случае успешного выполнения возвращает уникальный логин пользователя
//	 * @param $first_name
//	 * @param string $last_name
//	 * @param string $nickname
//	 * @param string $bdate
//	 * @param array $delimiters
//	 * @return string
//	 */
//	protected function generateNickname($first_name, $last_name="", $nickname="", $bdate="", $delimiters=array('.', '_')) {
//		$delim = array_shift($delimiters);
//
//		$first_name = $this->translitIt($first_name);
//		$first_name_s = substr($first_name, 0, 1);
//
//		$variants = array();
//		if (!empty($nickname))
//			$variants[] = $nickname;
//		$variants[] = $first_name;
//		if (!empty($last_name)) {
//			$last_name = $this->translitIt($last_name);
//			$variants[] = $first_name.$delim.$last_name;
//			$variants[] = $last_name.$delim.$first_name;
//			$variants[] = $first_name_s.$delim.$last_name;
//			$variants[] = $first_name_s.$last_name;
//			$variants[] = $last_name.$delim.$first_name_s;
//			$variants[] = $last_name.$first_name_s;
//		}
//		if (!empty($bdate)) {
//			$date = explode('.', $bdate);
//			$variants[] = $first_name.$date[2];
//			$variants[] = $first_name.$delim.$date[2];
//			$variants[] = $first_name.$date[0].$date[1];
//			$variants[] = $first_name.$delim.$date[0].$date[1];
//			$variants[] = $first_name.$delim.$last_name.$date[2];
//			$variants[] = $first_name.$delim.$last_name.$delim.$date[2];
//			$variants[] = $first_name.$delim.$last_name.$date[0].$date[1];
//			$variants[] = $first_name.$delim.$last_name.$delim.$date[0].$date[1];
//			$variants[] = $last_name.$delim.$first_name.$date[2];
//			$variants[] = $last_name.$delim.$first_name.$delim.$date[2];
//			$variants[] = $last_name.$delim.$first_name.$date[0].$date[1];
//			$variants[] = $last_name.$delim.$first_name.$delim.$date[0].$date[1];
//			$variants[] = $first_name_s.$delim.$last_name.$date[2];
//			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[2];
//			$variants[] = $first_name_s.$delim.$last_name.$date[0].$date[1];
//			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[0].$date[1];
//			$variants[] = $last_name.$delim.$first_name_s.$date[2];
//			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[2];
//			$variants[] = $last_name.$delim.$first_name_s.$date[0].$date[1];
//			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[0].$date[1];
//			$variants[] = $first_name_s.$last_name.$date[2];
//			$variants[] = $first_name_s.$last_name.$delim.$date[2];
//			$variants[] = $first_name_s.$last_name.$date[0].$date[1];
//			$variants[] = $first_name_s.$last_name.$delim.$date[0].$date[1];
//			$variants[] = $last_name.$first_name_s.$date[2];
//			$variants[] = $last_name.$first_name_s.$delim.$date[2];
//			$variants[] = $last_name.$first_name_s.$date[0].$date[1];
//			$variants[] = $last_name.$first_name_s.$delim.$date[0].$date[1];
//		}
//		$i=0;
//
//		$exist = true;
//		while (true) {
//			if ($exist = $this->userExist($variants[$i])) {
//				foreach ($delimiters as $del) {
//					$replaced = str_replace($delim, $del, $variants[$i]);
//					if($replaced !== $variants[$i]){
//						$variants[$i] = $replaced;
//						if (!$exist = $this->userExist($variants[$i]))
//							break;
//					}
//				}
//			}
//			if ($i >= count($variants)-1 || !$exist)
//				break;
//			$i++;
//		}
//
//		if ($exist) {
//			while ($exist) {
//				$nickname = $first_name.mt_rand(1, 100000);
//				$exist = $this->userExist($nickname);
//			}
//			return $nickname;
//		} else
//			return $variants[$i];
//	}
//
//
//	/**
//	 * Проверка существует ли пользователь с заданным логином
//	 */
//	protected function userExist($login){
//		if (!$this->model_module_ulogin->checkUserName(strtolower($login))){
//			return false;
//		}
//		return true;
//	}
//
//
//	/**
//	 * Транслит
//	 */
//	protected function translitIt($str) {
//		$tr = array(
//			"А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
//			"Д"=>"d","Е"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
//			"Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
//			"О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
//			"У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
//			"Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
//			"Э"=>"e","Ю"=>"yu","Я"=>"ya","а"=>"a","б"=>"b",
//			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
//			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
//			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
//			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
//			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
//			"ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
//		);
//		if (preg_match('/[^A-Za-z0-9\_\-]/', $str)) {
//			$str = strtr($str,$tr);
//			$str = preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $str);
//		}
//		return $str;
//	}



	/**
	 * Удаление привязки к аккаунту соцсети в таблице ulogin_user для текущего пользователя
	 */
	protected function deleteAccount() {
		$isAjaxRequest = isset($this->request->post['isAjax']) ? true : false;

		if (!$isAjaxRequest) {
			$this->response->redirect($this->url->link('error/not_found', '', 'SSL'));
		}

		if(!$this->userIsLogged) {exit;}

		$user_id = $this->currentUserId;

		$network = isset($this->request->post['network']) ? $this->request->post['network'] : '';

		if ($user_id > 0 && $network != '') {
			try {
				$this->model_module_ulogin->deleteUloginAccount(array('user_id' => $user_id, 'network' => $network));
				echo json_encode(array(
					'title' => '',
					'msg' => sprintf($this->language->get('ulogin_delete_account_success'), $network), //"Удаление аккаунта $network успешно выполнено",
					'type' => 'success'
				));
				exit;

			} catch (Exception $e) {
				echo json_encode(array(
					'title' => $this->language->get('ulogin_delete_account_error'), //"Ошибка при удалении аккаунта",
					'msg' => "Exception: " . $e->getMessage(),
					'type' => 'error'
				));
				exit;
			}
		}
		exit;
	}

}