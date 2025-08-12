<?php
class ControllerExtensionModuleSlideshow extends Controller {
	public function index($setting) {
		static $module = 0;		

		//load category model to fetch all category details
		$this->load->model('catalog/category');
		$this->load->model('design/banner');
		$this->load->model('tool/image');

		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/swiper.min.css');
		$this->document->addStyle('catalog/view/javascript/jquery/swiper/css/opencart.css');
		$this->document->addScript('catalog/view/javascript/jquery/swiper/js/swiper.jquery.min.js');
		
		$data['banners'] = array();

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_logo_big'))) {
			$data['logo'] = $server . 'image/' . $this->config->get('config_logo_big');
		} else {
			$data['logo'] = '';
		}

		$categories = $this->model_catalog_category->getHomeCategories(0);
		
		foreach($categories as $category) {
			$data['categories'][] = array(
				'name'  => $category['name'],
				'image' => $category['image'],
				'href'  => $this->url->link('product/category', 'path=' . $category['category_id']),
			);
		}

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$data['banners'][] = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height'])
				);
			}
		}

		$data['module'] = $module++;

		return $this->load->view('extension/module/slideshow', $data);
	}
}