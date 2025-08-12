<?php

class ControllerApiPricing extends Controller
{
  //Sync price sheet master table
  public function syncPriceSheet(): void
  {
    try {
      $this->load->model('api/pricing');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data  = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync for : Price Sheet Master", "INFO");
      $inserted = $this->model_api_pricing->insertPriceSheetToWebstore($data);

      Logger::log("Total records affected in price sheet table in webstore database : ".$inserted."\n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);


    } catch (\Exception $e) {
      Logger::log("Error in " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }

  //Sync item to price group table
  public function syncItemPriceGroup(): void
  {
    try {
      $this->load->model('api/pricing');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data  = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync for : Items and Price Group Mapping", "INFO");
      $inserted = $this->model_api_pricing->insertItemPriceGroupToWebstore($data);

      Logger::log("Total records affected in item price group mapping table in webstore database : ".$inserted."\n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);


    } catch (\Exception $e) {
      Logger::log("Error in " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }

  //Sync price book to customer mapping table
  public function syncPriceBookToCustomer(): void
  {
    try {
      $this->load->model('api/pricing');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data  = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync for : Price Book To Customer Mapping", "INFO");
      $inserted = $this->model_api_pricing->insertPriceBookCustomerToWebstore($data);

      Logger::log("Total records affected in price book to customer mapping table in webstore database : ".$inserted."\n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);


    } catch (\Exception $e) {
      Logger::log("Error in " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }

  //Sync price sheet to customer/pricebook mapping table
  public function syncPriceSheetToCustomer(): void
  {
    try {
      $this->load->model('api/pricing');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data  = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync for : Price Sheet To Customer/Pricebook Mapping", "INFO");
      $inserted = $this->model_api_pricing->insertPriceSheetCustomerToWebstore($data);

      Logger::log("Total records affected in price sheet to customer/pricebook mapping table in webstore database : ".$inserted."\n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);


    } catch (\Exception $e) {
      Logger::log("Error in " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }

  //Sync item prices table
  public function syncItemPrices(): void
  {
    set_time_limit(0);
    try {
      $this->load->model('api/pricing');
      $this->load->helper('logger');
      $this->load->helper('auth_helper');
      $this->load->helper('response');
      header("Content-Type: application/json");

      $auth_obj = new AuthHelper();
      if (!$auth_obj->authenticate($this->registry)) {
        http_response(403, ["error" => "Unathorized access"]);
      }

      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response(405, ["error" => "Method not allowed"]);
      }

      $input = file_get_contents("php://input");
      $json_data = gzdecode($input);
      $data  = json_decode($json_data, true);

      if (!$data) {
        Logger::log("Invalid JSON input : " . json_encode($data), "ERROR");
        http_response(400, ["error" => "Invalid JSON input"]);
      }

      Logger::log("Started sync for : Item Prices", "INFO");
      $inserted = $this->model_api_pricing->insertItemPricesToWebstore($data);

      Logger::log("Total records affected in item prices table in webstore database : ".$inserted."\n---------------------------------------------------------------------------", "INFO");
      http_response(200, ["res" => $inserted]);


    } catch (\Exception $e) {
      Logger::log("Error in " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString(), "ERROR");
      http_response(500, ["error" => "Something went wrong. Check error logs for more details"]);
    }
  }
}
