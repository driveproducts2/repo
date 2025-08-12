<?php
class ControllerAccountUsers extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/users', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/users');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('account/users');
    
    $this->getList();
	}

  protected function getList() {

    $this->load->model('extension/encryption/encryption_model');

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

    if ($this->customer->isLogged()) {
      $customer_id = $this->model_extension_encryption_encryption_model->encrypt_data($this->customer->getId());
    } else {
      $customer_id = $this->model_extension_encryption_encryption_model->encrypt_data(0);
    }  
    
    $data['customer_id'] = $customer_id;
		$data['add'] = $this->url->link('account/users/add', '', true);
		$data['back'] = $this->url->link('account/account', '', true);
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/users_list', $data));
  }

  public function get_users() {
		$final_results = [];
    $customer_id = $this->request->post['customer_id'];
    $username = $this->request->post['username'];
    $status = $this->request->post['status'];
    $contact = ($this->request->post['contact'] === 'false') ? false : true;
		$no_of_recds = (int)$this->request->post['no_of_records'];

    $this->load->model('account/users');
    $final_results = $this->model_account_users->fetchCustomerUsers($customer_id, $username, $status, $contact, $no_of_recds);

		echo json_encode($final_results); die;
  }

	public function add_users() {
		
	}
}