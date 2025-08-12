<?php
class ControllerCustomerUserList extends Controller
{
	private $error = array();

	public function index()
	{
		$this->load->language('customer/customer');
		$this->load->language('customer/user_list');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('customer/customer');

		$this->getList();
	}

	public function add() {
		$this->load->language('customer/user_list');

		$this->document->setTitle($this->language->get('heading_title_add'));

		$this->load->model('customer/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm('add')) {
			$this->model_customer_customer->addCustomerUser($this->request->post);

			$this->session->data['success'] = $this->language->get('text_add_success');

			$url = '';

			if (isset($this->request->get['filter_customer_code'])) {
				$url .= '&filter_customer_code=' . urlencode(html_entity_decode($this->request->get['filter_customer_code'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_customer_name'])) {
				$url .= '&filter_customer_name=' . urlencode(html_entity_decode($this->request->get['filter_customer_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_email'])) {
				$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_username'])) {
				$url .= '&filter_username=' . urlencode(html_entity_decode($this->request->get['filter_username'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_access_type'])) {
				$url .= '&filter_access_type=' . $this->request->get['filter_access_type'];
			}

			if (isset($this->request->get['filter_customer_group_id'])) {
				$url .= '&filter_customer_group_id=' . $this->request->get['filter_customer_group_id'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
			
			$this->response->redirect($this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm('add');
	}

	public function edit()
	{
		$this->load->language('customer/user_list');
		$this->document->setTitle($this->language->get('heading_title_edit'));
		$this->load->model('customer/customer');
		$this->load->model('tool/phone_helper');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm('edit')) {
			$this->model_customer_customer->editCustomerUser($this->request->get['customer_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_edit_success');

			$url = '';

			if (isset($this->request->get['filter_customer_code'])) {
				$url .= '&filter_customer_code=' . urlencode(html_entity_decode($this->request->get['filter_customer_code'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_customer_name'])) {
				$url .= '&filter_customer_name=' . urlencode(html_entity_decode($this->request->get['filter_customer_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_email'])) {
				$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_username'])) {
				$url .= '&filter_username=' . urlencode(html_entity_decode($this->request->get['filter_username'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_access_type'])) {
				$url .= '&filter_access_type=' . $this->request->get['filter_access_type'];
			}

			if (isset($this->request->get['filter_customer_group_id'])) {
				$url .= '&filter_customer_group_id=' . $this->request->get['filter_customer_group_id'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm('edit');
	}

	protected function getForm(string $type)
	{
		$data['text_form'] = !isset($this->request->get['customer_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['customer_id'])) {
			$data['customer_id'] = (int)$this->request->get['customer_id'];
		} else {
			$data['customer_id'] = 0;
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['customer_name'])) {
			$data['error_customer_name'] = $this->error['customer_name'];
		} else {
			$data['error_customer_name'] = '';
		}

		if (isset($this->error['first_name'])) {
			$data['error_first_name'] = $this->error['first_name'];
		} else {
			$data['error_first_name'] = '';
		}

		if (isset($this->error['last_name'])) {
			$data['error_last_name'] = $this->error['last_name'];
		} else {
			$data['error_last_name'] = '';
		}

		if (isset($this->error['user_email'])) {
			$data['error_user_email'] = $this->error['user_email'];
		} else {
			$data['error_user_email'] = '';
		}

		if (isset($this->error['access_type'])) {
			$data['error_access_type'] = $this->error['access_type'];
		} else {
			$data['error_access_type'] = '';
		}

		if (isset($this->error['status'])) {
			$data['error_status'] = $this->error['status'];
		} else {
			$data['error_status'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
		}

		if (isset($this->error['ext'])) {
			$data['error_ext'] = $this->error['ext'];
		} else {
			$data['error_ext'] = '';
		}

		$url = '';

		if (isset($this->request->get['filter_customer_code'])) {
			$url .= '&filter_customer_code=' . urlencode(html_entity_decode($this->request->get['filter_customer_code'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_customer_name'])) {
			$url .= '&filter_customer_name=' . urlencode(html_entity_decode($this->request->get['filter_customer_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_email'])) {
			$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_username'])) {
			$url .= '&filter_username=' . urlencode(html_entity_decode($this->request->get['filter_username'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_access_type'])) {
			$url .= '&filter_access_type=' . $this->request->get['filter_access_type'];
		}

		if (isset($this->request->get['filter_customer_group_id'])) {
			$url .= '&filter_customer_group_id=' . $this->request->get['filter_customer_group_id'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['customer_id'])) {
			$data['action'] = $this->url->link('customer/user_list/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
			//$data['customer_name'] = $this->model_customer_customer->getCustomers();
		} else {
			$data['action'] = $this->url->link('customer/user_list/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $this->request->get['customer_id'] . $url, true);
			$customer_info = $this->model_customer_customer->getCustomer($this->request->get['customer_id']);
			$data['customer_name'] = $customer_info['customer_name'];
		}

		$data['cancel'] = $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['cust_prefill_id'] = isset($this->request->post['customer_val']) ? $this->request->post['customer_val'] : '';

		$data['cust_prefill_text'] = isset($this->request->post['customer_text']) ? $this->request->post['customer_text'] : '';
		//Debugger::printdata($data);

		$data['title'] = isset($this->request->post['title']) ? $this->request->post['title'] : (isset($customer_info['title']) ? $customer_info['title'] : '');

		$data['first_name'] = isset($this->request->post['first_name']) ? $this->request->post['first_name'] : (isset($customer_info['firstname']) ? $customer_info['firstname'] : '');

		$data['lastname'] = isset($this->request->post['last_name']) ? $this->request->post['last_name'] : (isset($customer_info['lastname']) ? $customer_info['lastname'] : '');

		$data['email'] = isset($this->request->post['email']) ? $this->request->post['email'] : (isset($customer_info['email']) ? $customer_info['email'] : '');

		$data['username'] = isset($this->request->post['username']) ? $this->request->post['username'] : (isset($customer_info['user_name']) ? $customer_info['user_name'] : '');

		$data['access_type'] = isset($this->request->post['access_type']) ? $this->request->post['access_type'] : (isset($customer_info['access_type']) ? $customer_info['access_type'] : '');

		$data['status'] = isset($this->request->post['status']) ? $this->request->post['status'] : (isset($customer_info['status']) ? $customer_info['status'] : '');

		$data['telephone'] = isset($this->request->post['telephone']) ? $this->request->post['telephone'] : (isset($customer_info['telephone']) ? $this->model_tool_phone_helper->formatPhoneNumber($customer_info['telephone']) : '');

		$data['ext'] = isset($this->request->post['ext']) ? $this->request->post['ext'] : (isset($customer_info['ext']) ? $customer_info['ext'] : '');

		$data['telephone_1'] = isset($this->request->post['telephone1']) ? $this->request->post['telephone1'] : ((isset($customer_info['telephone_1']) && !empty($customer_info['telephone_1'])) ? $this->model_tool_phone_helper->formatPhoneNumber($customer_info['telephone_1']) : '');

		$data['telephone_2'] = isset($this->request->post['telephone2']) ? $this->request->post['telephone2'] : ((isset($customer_info['telephone_2']) && !empty($customer_info['telephone_2'])) ? $this->model_tool_phone_helper->formatPhoneNumber($customer_info['telephone_2']) : '');


		$data['user_group'] = $this->model_customer_customer->getCustomerUserGroup();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		if ($type == 'edit') {
			$this->response->setOutput($this->load->view('customer/user_edit_form', $data));
		} elseif ($type == 'add') {
			$this->response->setOutput($this->load->view('customer/user_add_form', $data));
		}
		
	}

	protected function getList()
	{
		$url = '';

		if (isset($this->request->get['filter_customer_code'])) {
			$filter_customer_code = $this->request->get['filter_customer_code'];
			$url .= '&filter_customer_code=' . urlencode(html_entity_decode($filter_customer_code, ENT_QUOTES, 'UTF-8'));
		} else {
			$filter_customer_code = '';
		}

		if (isset($this->request->get['filter_customer_name'])) {
			$filter_customer_name = $this->request->get['filter_customer_name'];
			$url .= '&filter_customer_name=' . urlencode(html_entity_decode($filter_customer_name, ENT_QUOTES, 'UTF-8'));
		} else {
			$filter_customer_name = '';
		}

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
			$url .= '&filter_name=' . urlencode(html_entity_decode($filter_name, ENT_QUOTES, 'UTF-8'));
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_email'])) {
			$filter_email = $this->request->get['filter_email'];
			$url .= '&filter_email=' . urlencode(html_entity_decode($filter_email, ENT_QUOTES, 'UTF-8'));
		} else {
			$filter_email = '';
		}

		if (isset($this->request->get['filter_username'])) {
			$filter_username = $this->request->get['filter_username'];
			$url .= '&filter_username=' . urlencode(html_entity_decode($filter_username, ENT_QUOTES, 'UTF-8'));
		} else {
			$filter_username = '';
		}

		if (isset($this->request->get['filter_access_type'])) {
			$filter_access_type = $this->request->get['filter_access_type'];
			$url .= '&filter_access_type=' . $filter_access_type;
		} else {
			$filter_access_type = '';
		}

		if (isset($this->request->get['filter_customer_group_id'])) {
			$filter_customer_group_id = $this->request->get['filter_customer_group_id'];
			$url .= '&filter_customer_group_id=' . $filter_customer_group_id;
		} else {
			$filter_customer_group_id = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
			$url .= '&filter_status=' . $filter_status;
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'c.customer_code';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('customer/user_list/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('customer/user_list/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['customers'] = array();

		$filter_data = array(
			'filter_customer_code'     => $filter_customer_code,
			'filter_customer_name'     => $filter_customer_name,
			'filter_name'              => $filter_name,
			'filter_email'             => $filter_email,
			'filter_username'          => $filter_username,
			'filter_access_type'       => $filter_access_type,
			'filter_customer_group_id' => $filter_customer_group_id,
			'filter_status'            => $filter_status,
			'sort'                     => $sort,
			'order'                    => $order,
			'start'                    => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                    => $this->config->get('config_limit_admin')
		);

		$customer_total = $this->model_customer_customer->getTotalCustomerUsers($filter_data);
		$results = $this->model_customer_customer->getCustomersUsers($filter_data);
		$data['access_type'] = $this->model_customer_customer->getCustomerUserGroup();

		foreach ($results as $result) {
			$data['customers'][] = array(
				'customer_id'    => $result['customer_id'],
				'customer_code'  => $result['customer_code'],
				'customer_name'  => $result['customer_name'],
				'name'           => $result['name'],
				'status'         => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'edit'           => $this->url->link('customer/user_list/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['customer_id'] . $url, true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}


		if ($order == 'ASC') {
			$forder = 'DESC';
		} else {
			$forder = 'ASC';
		}

		$data['sort_customer_code'] = $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . '&sort=customer_code' . $url . '&page=' . $page . '&order=' . $forder, true);
		$data['sort_customer_name'] = $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . '&sort=customer_name' . $url . '&page=' . $page . '&order=' . $forder, true);
		$data['sort_name'] = $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url . '&page=' . $page . '&order=' . $forder, true);
		$data['sort_status'] = $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url . '&page=' . $page . '&order=' . $forder, true);

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $customer_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($customer_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($customer_total - $this->config->get('config_limit_admin'))) ? $customer_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $customer_total, ceil($customer_total / $this->config->get('config_limit_admin')));

		$data['filter_customer_code'] = $filter_customer_code;
		$data['filter_customer_name'] = $filter_customer_name;
		$data['filter_name'] = $filter_name;
		$data['filter_email'] = $filter_email;
		$data['filter_username'] = $filter_username;
		$data['filter_access_type'] = $filter_access_type;
		$data['filter_customer_group_id'] = $filter_customer_group_id;

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$data['base_url'] = HTTPS_SERVER;

		$this->response->setOutput($this->load->view('customer/user_list', $data));
	}

	public function autocomplete()
	{
		$this->load->model('customer/customer');
		$results = $this->model_customer_customer->getCustomerAuto();

		echo json_encode(["items" => $results]);
	}

	public function delete()
	{
		$this->load->language('customer/customer');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('customer/customer');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $customer_user_id) {
				$this->model_customer_customer->deleteCustomerUsers($customer_user_id);
			}

			$this->session->data['success'] = $this->language->get('text_deleted');

			$url = '';

			if (isset($this->request->get['filter_customer_code'])) {
				$url .= '&filter_customer_code=' . urlencode(html_entity_decode($this->request->get['filter_customer_code'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_customer_name'])) {
				$url .= '&filter_customer_name=' . urlencode(html_entity_decode($this->request->get['filter_customer_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_name'])) {
				$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_email'])) {
				$url .= '&filter_email=' . urlencode(html_entity_decode($this->request->get['filter_email'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_username'])) {
				$url .= '&filter_username=' . urlencode(html_entity_decode($this->request->get['filter_username'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_access_type'])) {
				$url .= '&filter_access_type=' . $this->request->get['filter_access_type'];
			}

			if (isset($this->request->get['filter_customer_group_id'])) {
				$url .= '&filter_customer_group_id=' . $this->request->get['filter_customer_group_id'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('customer/user_list', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function validateDelete()
	{
		if (!$this->user->hasPermission('modify', 'customer/user_list')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateForm(string $type)
	{
		$this->load->model('tool/phone_helper');
		$this->load->model('customer/customer');

		if (!$this->user->hasPermission('modify', 'customer/user_list')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if($type == 'add' && empty($this->request->post['customer'])) {
			$this->error['customer_name'] = 'Please select customer';
		}

		if (empty($this->request->post['first_name'])) {
			$this->error['first_name'] = 'First name is compulsory';
		}

		if (empty($this->request->post['last_name'])) {
			$this->error['last_name'] = 'Last name is compulsory';
		}

		if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['user_email'] = 'Invalid Email';
		}

		if ($type == 'add' && $this->model_customer_customer->checkDuplicateEmail($this->request->post['email'], $type)) 
		{
			$this->error['user_email'] = 'User with the same email already exist!';
		}

		if ($type == 'edit' && $this->model_customer_customer->checkDuplicateEmail($this->request->post['email'], $type, $this->request->post['customer_id'])) 
		{
			$this->error['user_email'] = 'User with the same email already exist!';
		}

		if (empty($this->request->post['access_type'])) {
			$this->error['access_type'] = 'Please select group';
		}

		if (!in_array($this->request->post['status'], array("0", "1"))) {
			$this->error['status'] = 'Please select status';
		}

		if (!$this->model_tool_phone_helper->validatePhoneNumber($this->request->post['telephone'])) {
			$this->error['telephone'] = "Invalid telephone number";
		}

		if (empty($this->request->post['ext'])) {
			$this->error['ext'] = 'Enter ext';
		}

		return !$this->error;
	}
}
