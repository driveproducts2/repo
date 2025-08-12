<?php

class ControllerApiAddProduct extends Controller
{ 
  //Sync item class records from GP
  public function syncItemClassFromGP(): void
  {
    try {
      $this->load->model('api/product');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        Logger::log("Unathorized access {MODULE_NAME : ITEM_CLASS}");
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        Logger::log("Method not allowed {MODULE_NAME : ITEM_CLASS}");
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $item_class = json_decode($json_data, true);

      if (!$item_class) {
        Logger::log("Invalid JSON input : " . json_encode($item_class), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync of item class", "INFO");
      $inserted = $this->model_api_product->insertItemClassToWebstore($item_class);

      Logger::log("Total records affected in item class table in webstore database : $inserted \n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);
    } catch (Exception $e) {
      Logger::log("An error occurred : " . $e->getMessage(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }

  //Sync items records from GP (products in webstore)
  public function syncItemsFromGP(): void
  {
    try {
      $this->load->model('api/product');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        Logger::log("Unathorized access {MODULE_NAME : ITEMS}");
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        Logger::log("Method not allowed {MODULE_NAME : ITEMS}");
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $items = json_decode($json_data, true);

      if (!$items) {
        Logger::log("Invalid JSON input : " . json_encode($items), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync of item class", "INFO");
      $inserted = $this->model_api_product->insertItemsToWebstore($items);

      Logger::log("Total records affected in item master and description table in webstore database : $inserted\n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);
    } catch (Exception $e) {
      Logger::log("An error occurred : " . $e->getMessage(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }

  //Sync uom schedule records from GP
  public function syncUOMScheduleFromGP(): void
  {
    try {
      $this->load->model('api/product');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        Logger::log("Unathorized access, authentication fail", "ERROR");
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        Logger::log("Invalid method : ".$_SERVER["REQUEST_METHOD"], "ERROR");
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data  = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync for : UOM schedule", "INFO");
      $inserted = $this->model_api_product->insertUOMScheduleToWebstore($data);

      Logger::log("Total records affected in UOM schedule table in webstore database : ".$inserted."\n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);


    } catch (\Exception $e) {
      Logger::log("Error in " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }

  //Sync warehouse records from GP
  public function syncWarehouseFromGP(): void
  {
    try {
      $this->load->model('api/product');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        Logger::log("Unathorized access {MODULE_NAME : WAREHOUSE}");
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        Logger::log("Method not allowed {MODULE_NAME : WAREHOUSE}");
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync of warehouse", "INFO");
      $inserted = $this->model_api_product->insertWarehouseToWebstore($data);

      Logger::log("Total records affected in warehouse table in webstore database : $inserted \n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);
    } catch (Exception $e) {
      Logger::log("An error occurred : " . $e->getMessage(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }  

  //Sync item quantities from GP (inventory)
  public function syncQuantityFromGP(): void
  {
    try {
      $this->load->model('api/product');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        Logger::log("Unathorized access {MODULE_NAME : ITEM QUANTITY}");
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        Logger::log("Method not allowed {MODULE_NAME : ITEM QUANTITY}");
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync of item quantity", "INFO");
      $inserted = $this->model_api_product->insertItemQuantityToWebstore($data);

      Logger::log("Total records affected in item quantity table in webstore database : $inserted \n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);
    } catch (Exception $e) {
      Logger::log("An error occurred : " . $e->getMessage(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }  

}
