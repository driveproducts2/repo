<?php
class ControllerAccountRegister extends Controller
{
	private $error = array();

	public function index()
	{
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', false));
		}

		$this->load->language('account/register');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$this->load->model('account/customer');
		$this->load->model('localisation/language'); //load language model for language dropdown
		
		$data['form_err'] = array();
		$data['form_data'] = array();
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && empty($this->validate())) {
			
			$this->session->data['form_data'] = $this->request->post;
			
			$this->model_account_customer->addCustomer($this->request->post);

			// Clear any previous login attempts for unregistered accounts.
			$this->model_account_customer->deleteLoginAttempts($this->request->post['email']);

			//$this->customer->login($this->request->post['email'], $this->request->post['password']);

			unset($this->session->data['guest']);

			$this->response->redirect($this->url->link('account/success'));
		} elseif(($this->request->server['REQUEST_METHOD'] == 'POST') && !empty($this->validate())) {
			$data['form_data'] = $this->request->post;
			// var_dump($this->session->data['form_data']); die;
			$data['form_err'] = $this->validate();
		}
		
		

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', false)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_register'),
			'href' => $this->url->link('account/register', '', false)
		);
		$data['text_account_already'] = sprintf($this->language->get('text_account_already'), $this->url->link('account/login', '', false));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['firstname'])) {
			$data['error_firstname'] = $this->error['firstname'];
		} else {
			$data['error_firstname'] = '';
		}

		if (isset($this->error['lastname'])) {
			$data['error_lastname'] = $this->error['lastname'];
		} else {
			$data['error_lastname'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
		}

		if (isset($this->error['custom_field'])) {
			$data['error_custom_field'] = $this->error['custom_field'];
		} else {
			$data['error_custom_field'] = array();
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['confirm'])) {
			$data['error_confirm'] = $this->error['confirm'];
		} else {
			$data['error_confirm'] = '';
		}

		$data['action'] = $this->url->link('account/register', '', false);

		$data['customer_groups'] = array();

		if (is_array($this->config->get('config_customer_group_display'))) {
			$this->load->model('account/customer_group');

			$customer_groups = $this->model_account_customer_group->getCustomerGroups();

			foreach ($customer_groups as $customer_group) {
				if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}

		if (isset($this->request->post['customer_group_id'])) {
			$data['customer_group_id'] = $this->request->post['customer_group_id'];
		} else {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		if (isset($this->request->post['firstname'])) {
			$data['firstname'] = $this->request->post['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->request->post['lastname'])) {
			$data['lastname'] = $this->request->post['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} else {
			$data['telephone'] = '';
		}

		// Custom Fields
		$data['custom_fields'] = array();

		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields();

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				$data['custom_fields'][] = $custom_field;
			}
		}

		if (isset($this->request->post['custom_field']['account'])) {
			$data['register_custom_field'] = $this->request->post['custom_field']['account'];
		} else {
			$data['register_custom_field'] = array();
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['confirm'])) {
			$data['confirm'] = $this->request->post['confirm'];
		} else {
			$data['confirm'] = '';
		}

		if (isset($this->request->post['newsletter'])) {
			$data['newsletter'] = $this->request->post['newsletter'];
		} else {
			$data['newsletter'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		if ($this->config->get('config_account_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if ($information_info) {
				$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), false), $information_info['title']);
			} else {
				$data['text_agree'] = '';
			}
		} else {
			$data['text_agree'] = '';
		}

		if (isset($this->request->post['agree'])) {
			$data['agree'] = $this->request->post['agree'];
		} else {
			$data['agree'] = false;
		}

		if (isset(($this->session->data['language']))) {
			if ($this->session->data['language'] == 'fr-FR') {
				$this->language->set('text_home_title', 'Registre');
			} elseif ($this->session->data['language'] == 'en-gb') {
				$this->language->set('text_home_title', 'Register');
			}
		}

		$data['countries'] = $this->model_account_customer->getCountry();
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['language_code'] = $this->language->get('code');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/register', $data));
	}

	private function validate()
	{	
		$alpha_pattern = '/^[a-zA-Z]+$/';
		$phone_pattern = '/^\+?[1-9]\d{1,14}$/';

		if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32) || (!preg_match($alpha_pattern, $this->request->post['firstname']))) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32) || (!preg_match($alpha_pattern, $this->request->post['lastname']))) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if(empty($this->request->post['company'])) {
			$this->error['company'] = $this->language->get('error_company');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if (empty($this->request->post['cemail']) || ($this->request->post['cemail'] != $this->request->post['email'])) {
			$this->error['cemail'] = $this->language->get('error_cnf_email');
		}

		if(empty($this->request->post['address'])) {
			$this->error['address'] = $this->language->get('error_address');
		}

		if(empty($this->request->post['city'])) {
			$this->error['city'] = $this->language->get('error_city');
		}

		if(empty($this->request->post['zip'])) {
			$this->error['zip'] = $this->language->get('error_zip');
		}

		if ($this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
			$this->error['email_alrd'] = $this->language->get('error_exists');
		}

		if ((utf8_strlen($this->request->post['phone']) < 3) || (utf8_strlen($this->request->post['phone']) > 32) || (!preg_match($phone_pattern, $this->request->post['phone']))) {
			$this->error['phone'] = $this->language->get('error_telephone');
		}

		if(empty($this->request->post['code'])) {
			$this->error['code_err'] = $this->language->get('error_code');
		} else {
			$txt_code = $this->request->post['code'];
			$enc_code = $this->request->post['hid_cap'];

			if($txt_code !=  $this->decrypt_data_img($enc_code)) {
				$this->error['code_err'] = $this->language->get('error_code');
			}
		}
		
		return $this->error;
	}

	public function customfield()
	{
		$json = array();

		$this->load->model('account/custom_field');

		// Customer Group
		if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			$json[] = array(
				'custom_field_id' => $custom_field['custom_field_id'],
				'required'        => $custom_field['required']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	//newly added to fetch state name of the country selected
	public function get_states()
	{

		$this->load->model('account/customer');
		if (isset($this->request->post['country_id']) && !empty($this->request->post['country_id'])) {
			$country_id = (int)$this->request->post['country_id'];

			$state_name = $this->model_account_customer->getState($country_id);

			if (isset($state_name) && !empty($state_name)) {
				echo json_encode($state_name);
				die;
			}
			echo json_encode(array('error' => 'No state found'));
			die;
		}
	}

	//function to generate image using gd2 library in php
	// public function get_captcha_image()
	// {

	// 	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($this->request->post['action_img']) && ($this->request->post['action_img'] === 'generate')) {
	// 		// Generate a random string
	// 		$length = 6;
	// 		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	// 		$randomString = '';
	// 		for ($i = 0; $i < $length; $i++) {
	// 			$randomString .= $characters[rand(0, strlen($characters) - 1)];
	// 		}

	// 		// Store the CAPTCHA text in session
	// 		//$_SESSION['captcha_text'] = $randomString;

	// 		// Create an image
	// 		$width = 200;
	// 		$height = 70;
	// 		$image = imagecreate($width, $height);

	// 		// Define colors
	// 		$bgColor = imagecolorallocate($image, 255, 255, 255); // white background
	// 		$textColor = imagecolorallocate($image, 0, 0, 0); // black text

	// 		// Fill the background with white
	// 		imagefill($image, 0, 0, $bgColor);

	// 		// Add the text to the image using built-in fonts
	// 		$fontSize = 5; // Built-in font size (1 to 5)
	// 		$fontWidth = imagefontwidth($fontSize);
	// 		$fontHeight = imagefontheight($fontSize);

	// 		// Calculate the total width of the text
	// 		$textWidth = $fontWidth * strlen($randomString);

	// 		// Calculate the X and Y coordinates to center the text
	// 		$textX = ($width - $textWidth) / 2;
	// 		$textY = ($height - $fontHeight) / 2;

	// 		// Draw the text
	// 		imagestring($image, $fontSize, $textX, $textY, $randomString, $textColor);

	// 		// Output the image
	// 		header('Content-Type: image/png');
	// 		imagepng($image);
	// 		imagedestroy($image);
	// 		die;
	// 	} else {
	// 		// Return a default image or error message
	// 		// This could be a static image or an error message in JSON format
	// 		header('Content-Type: image/png'); // Assuming default image is PNG
	// 		readfile('default_captcha.png'); // Path to default CAPTCHA image
	// 		die;
	// 	}
	// }

	public function gen_rand_str()
	{
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($this->request->post['action_img']) && ($this->request->post['action_img'] === 'generate')) {
			$length = 6;
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, strlen($characters) - 1)];
			}
			echo $this->encrypt_data_img($randomString);
			die;
		}
	}

	public function get_captcha_image()
	{
		if (isset($this->request->get['rand_str'])) {
			$randomString = $this->decrypt_data_img($this->request->get['rand_str']);
			$width = 200;
			$height = 70;
			$image = imagecreate($width, $height);

			// Define colors
			$bgColor = imagecolorallocate($image, 255, 255, 255); // white background
			$textColor = imagecolorallocate($image, 0, 0, 0); // black text

			// Fill the background with white
			imagefill($image, 0, 0, $bgColor);

			// Add the text to the image using built-in fonts
			$fontSize = 5; // Built-in font size (1 to 5)
			$fontWidth = imagefontwidth($fontSize);
			$fontHeight = imagefontheight($fontSize);

			// Calculate the total width of the text
			$textWidth = $fontWidth * strlen($randomString);

			// Calculate the X and Y coordinates to center the text
			$textX = ($width - $textWidth) / 2;
			$textY = ($height - $fontHeight) / 2;

			// Draw the text
			imagestring($image, $fontSize, $textX, $textY, $randomString, $textColor);

			// Output the image
			header('Content-Type: image/png');
			imagepng($image);
			imagedestroy($image);
		}
	}

	public function encrypt_data_img(string $data): string
	{
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$encrypted = openssl_encrypt($data, 'aes-256-cbc', ENC_KEY, 0, $iv);
		$result = base64_encode($encrypted . '::' . $iv);

		// Use URL-safe base64 encoding
		$urlSafeResult = str_replace(['+', '/', '='], ['-', '_', ''], $result);

		return $urlSafeResult;
	}

	public function decrypt_data_img(string $data): string
	{
		// Convert URL-safe base64 encoding back to standard base64 encoding
		$base64 = str_replace(['-', '_'], ['+', '/'], $data);
		// Add padding if necessary
		$padding = 4 - (strlen($base64) % 4);
		if ($padding !== 4) {
			$base64 .= str_repeat('=', $padding);
		}

		$decoded = base64_decode($base64);
		list($encrypted_data, $iv) = explode('::', $decoded, 2);

		$decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', ENC_KEY, 0, $iv);

		return $decrypted;
	}
}
