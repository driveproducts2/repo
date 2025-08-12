<?php
class ControllerCatalogItemClass extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/category');

		$this->document->setTitle($this->language->get('heading_title_item_class'));

		$this->load->model('catalog/category');

    $this->getList();
	}

  public function getList()
  {
    $data['item_classes'] = $this->model_catalog_category->getAllItemClasses();
    $data['save_url'] = $this->url->link('catalog/item_class/updateItemClassStatuses&user_token=' . $this->session->data['user_token']);
    
    $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('catalog/item_class', $data));
  }

  // update item class status
  public function updateItemClassStatuses(): void {
    $this->load->model('catalog/category');
    $json = [];

    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['items']) && is_array($input['items'])) {
      if ($this->model_catalog_category->updateMultipleItemClassStatuses($input['items'])) {
        $json['success'] = true;
      } else {
        $json['success'] = false;
      }      
    } else {
      $json['success'] = false;
      $json['error'] = 'Invalid data';
    }

    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

}