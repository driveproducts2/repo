<?php
class ControllerCheckoutPaymentAddress extends Controller
{
	public function index()
	{
		$this->load->language('checkout/checkout');

		if (isset($this->session->data['payment_address']['address_id'])) {
			$data['address_id'] = $this->session->data['payment_address']['address_id'];
		} else {
			$data['address_id'] = $this->customer->getAddressId();
		}

		$this->load->model('account/address');

		//Get logged in user information and main account address 
		$this->load->model('account/customer');
		$user_details = $this->model_account_customer->getUserDetail($this->session->data['customer_id']);
		//var_dump($user_details); die;
		$data['user_details'] = $user_details['user'];
		$data['customer_details'] = $user_details['customer'];

		$data['addresses'] = $this->model_account_address->getAddresses();

		if (isset($this->session->data['payment_address']['country_id'])) {
			$data['country_id'] = $this->session->data['payment_address']['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->session->data['payment_address']['zone_id'])) {
			$data['zone_id'] = $this->session->data['payment_address']['zone_id'];
		} else {
			$data['zone_id'] = '';
		}

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Custom Fields
		$data['custom_fields'] = array();

		$this->load->model('account/custom_field');

		$custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'address') {
				$data['custom_fields'][] = $custom_field;
			}
		}

		if (isset($this->session->data['payment_address']['custom_field'])) {
			$data['payment_address_custom_field'] = $this->session->data['payment_address']['custom_field'];
		} else {
			$data['payment_address_custom_field'] = array();
		}
		$data['language_code'] = $this->language->get('code');

		$this->response->setOutput($this->load->view('checkout/payment_address', $data));
	}

	public function save()
	{
		$this->load->language('checkout/checkout');

		$json = array();

		// Validate if customer is logged in.
		if (!$this->customer->isLogged()) {
			$json['redirect'] = $this->url->link('checkout/checkout', '', true);
		}

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$json['redirect'] = $this->url->link('checkout/cart');
		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$json['redirect'] = $this->url->link('checkout/cart');

				break;
			}
		}

		if (!$json) {
			$this->load->model('account/address');

			if (isset($this->request->post['payment_address']) && $this->request->post['payment_address'] == 'existing') {
				if (empty($this->request->post['address_id'])) {
					$json['error']['warning'] = $this->language->get('error_address');
				} elseif (!in_array($this->request->post['address_id'], array_keys($this->model_account_address->getAddresses()))) {
					$json['error']['warning'] = $this->language->get('error_address');
				}

				if (!$json) {
					$this->session->data['payment_address'] = $this->model_account_address->getAddress($this->request->post['address_id']);

					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);
				}
			} else {
				if (isset($this->request->post['email'])) { //validate email address
					$email = trim($this->request->post['email']);

					// Validate email
					if (empty($email)) {
						$json['error']['email'] = "Email address is required.";
					} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$json['error']['email'] = "Invalid email address.";
					}
				} else {
					$json['error']['email'] = "Email field is not set.";
				}

				if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) { //validate first name
					$json['error']['firstname'] = $this->language->get('error_firstname');
				}

				if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) { //validate last name
					$json['error']['lastname'] = $this->language->get('error_lastname');
				}

				if (isset($this->request->post['cellphone'])) { //validate cellphone
					$cellphone = trim($this->request->post['cellphone']);
					if (!empty($cellphone)) {
						$this->load->model('localisation/country');
						$country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);
						$country_code = trim(strtolower($country_info['code']));
						$phoneRegexes = [
							'ca' => '/^[(]?\d{3}[)]?[ -.]?\d{3}[- .]?\d{4}?$/', // Canada
							'us' => '/^[(]?\d{3}[)]?[ -.]?\d{3}[- .]?\d{4}?$/'  // United States
						];

						if (!array_key_exists($country_code, $phoneRegexes)) {
							$json['error']['cellphone'] = "Unsupported country code";
						} else {
							$regex = $phoneRegexes[$country_code];
							if (!preg_match($regex, $cellphone)) {
								$json['error']['cellphone'] = "Phone number is invalid";
							}
						}
					}
				}

				if (!$json) {
					$this->load->model('account/customer');
					$this->load->model('account/address');
					$this->model_account_customer->updateUserDetails($this->customer->getId(), $this->request->post);
					$this->session->data['payment_address'] = $this->model_account_address->getBillingAddress($this->customer->getId());

					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
