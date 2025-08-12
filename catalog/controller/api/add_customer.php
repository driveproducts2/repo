<?php

require_once DIR_ROOT . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class ControllerApiAddCustomer extends Controller
{

  //Check API key for authentication
  private function authenticate()
  {
    $this->load->model('account/api');
    $api_info = [];

    // Login with API Key
    if (isset($this->request->post['username']) && isset($this->request->post['key'])) {
      $api_info = $this->model_account_api->login($this->request->post['username'], $this->request->post['key']);
    } elseif (isset($this->request->post['key'])) {
      $api_info = $this->model_account_api->login('Default', $this->request->post['key']);
    }
    return $api_info;
  }

  // Add customers and customer groups to webstore database 
  public function insert_customer_class()
  {
    if (!$this->authenticate()) {
      // Send 401 Unauthorized status code
      header('HTTP/1.1 401 Unauthorized');
      echo json_encode(["error" => "Not able to access API, please check credentials"]);
      die;
    }
    // Load customer model
    $this->load->model('account/customer');
    $result = ($this->model_account_customer->insertCustomerClass());

    http_response_code($result['code']);
    header('Content-Type: application/json');
    echo json_encode($result['msg']);
    die;
  }

  // Add customer users details to webstore database(create login for users)
  public function insert_customer_user()
  {
    if (!$this->authenticate()) {
      // Send 401 Unauthorized status code
      header('HTTP/1.1 401 Unauthorized');
      echo json_encode(["error" => "Not able to access API, please check credentials"]);
      die;
    }

    $this->load->model('account/customer');
    $json = array();

    // Check if a file was uploaded
    if (isset($_FILES['file']['tmp_name'])) {
      $filePath = $_FILES['file']['tmp_name'];
      $fileType = mime_content_type($filePath);
      $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

      if ($fileExtension !== 'xlsx' || $fileType !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
        $json['error'] = 'Invalid file type. Please upload a valid .xlsx file.';
      } else {
        try {
          // Load the uploaded Excel file
          $json['success'] = $this->model_account_customer->addCustomerUsers();
        } catch (\Exception $e) {
          $json['error'] = 'Error processing request: ' . $e->getMessage();
        }
      }
    } else {
      $json['error'] = 'No file uploaded!';
    }

    // Output response as JSON
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }

  //Add sales person to webstore database (currently not in use)
  public function insert_sales_person()
  {
    if (!$this->authenticate()) {
      // Send 401 Unauthorized status code
      header('HTTP/1.1 401 Unauthorized');
      echo json_encode(["error" => "Not able to access API, please check credentials"]);
      die;
    }
    $this->load->model('account/customer');
    print_r($this->model_account_customer->insertSalesPerson());
  }

  //Add single customer to webstore database (currently not in use)
  public function insert_customer()
  {
    if (!$this->authenticate()) {
      // Send 401 Unauthorized status code
      header('HTTP/1.1 401 Unauthorized');
      echo json_encode(["error" => "Not able to access API, please check credentials"]);
      die;
    }

    $this->load->model('account/customer');

    // Validate customer number
    if (!isset($this->request->post['cust_num']) || empty($this->request->post['cust_num'])) {
      // Send 400 Bad Request status code
      header('HTTP/1.1 400 Bad Request');
      echo json_encode(["error" => "Please enter a valid customer number"]);
      die;
    }

    $cust_num = trim($this->request->post['cust_num']);
    $result = $this->model_account_customer->insertApiData($cust_num);

    if (isset($result) && !empty($result)) {
      // Set the HTTP response code based on the result
      http_response_code($result['code']);
      header('Content-Type: application/json');
      echo json_encode($result['msg']);
      die;
    }
  }

  public function insertCustomerFromWebStore()
  {
    // if (!$this->authenticate()) {
    //   // Send 401 Unauthorized status code
    //   header('HTTP/1.1 401 Unauthorized');
    //   echo json_encode(["error" => "Not able to access API, please check credentials"]);
    //   die;
    // }
    $query = $this->db->query("SELECT customer_code, customer_name, customer_class_id, address1, address2 FROM `oc_customer` WHERE customer_id = '25501'");
    if ($query->num_rows > 0) {
      $result = $query->row;
    }
    // $customer_group = '06FLEET LARGE';
    // $customer_name = 'TEST CUSTOMER NAME';
    // $customer_code = 'TEST CUSTOMER CODE';
    // $customer_address = 'BILL TO';
    // $arr = ['customer_group' => $customer_group, 'customer_name' => $customer_name, 'customer_code' => $customer_code, 'customer_address' => $customer_address];

    header('Content-Type: application/json');
    echo json_encode($result);
    die;
  }

  /*API's TO SYNC DATA FROM DYNAMICS GP TO WEB STORE DB*/
  //http://20.151.72.222/driveproduct/index.php?route=api/add_customer/syncCustomerFromGP
  public function syncCustomerFromGP()
  {
    $this->load->model('api/customer');
    $this->load->helper('logger');

    header("Content-Type: application/json");
    
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
      http_response_code(405);
      echo json_encode(["error" => "Method not allowed"]);
      die;
    }

    $input = file_get_contents("php://input");
    $data = gzdecode($input);
    $customers = json_decode($data, true);
    
    if (!$customers) {
      http_response_code(400);
      echo json_encode(["error" => "Invalid JSON input"]);
      die;
    }

    try {
      $inserted = $this->model_api_customer->insertCustomersToWebstore($customers);
      Logger::log("Inserted $inserted customer records into Webstore DB", "INFO");

      http_response_code(200);
      echo json_encode(["res" => $inserted]); die;

    } catch (Exception $e) {
      Logger::log("Error inserting customers: " . $e->getMessage(), "ERROR");
      http_response_code(500);
      echo json_encode(["error" => "Database error"]); die;
    }
  }

  public function syncCustomerClassFromGP()
  {
    $this->load->model('api/customer');
    $this->load->helper('logger');

    header("Content-Type: application/json");
    
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
      http_response_code(405);
      echo json_encode(["error" => "Method not allowed"]);
      die;
    }

    $input = file_get_contents("php://input");
    $json_data = gzdecode($input);
    $customers_class = json_decode($json_data, true);
    
    if (!$customers_class) {
      http_response_code(400);
      echo json_encode(["error" => "Invalid JSON input"]);
      die;
    }

    try {
      $inserted = $this->model_api_customer->insertCustomerClassToWebstore($customers_class);

      Logger::log("Inserted $inserted customer class records into Webstore DB", "INFO");
      http_response_code(200);
      echo json_encode(["res" => $inserted]); die; 

    } catch (Exception $e) {
      Logger::log("Error inserting into MySQL: " . $e->getMessage(), "ERROR");
      http_response_code(500);
      echo json_encode(["error" => "Database error"]); die;
    }
  }
}
