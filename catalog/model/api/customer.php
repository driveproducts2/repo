<?php
class ModelApiCustomer extends Model
{

    public function insertCustomerClassToWebstore($customers_class)
    {
        $this->load->helper('logger'); // Load Logger Helper

        try {
            $ins_count = 0;

            foreach ($customers_class as $row) {
                $class_id = trim($this->db->escape($row['CLASSID']));
                $class_desc = trim($this->db->escape($row['CLASDSCR']));
                $sales_person = trim($this->db->escape($row['SLPRSNID']));
                $inactive = trim($this->db->escape($row['INACTIVE']));

                // Insert or Update oc_customer_group_test
                $this->db->query("INSERT INTO oc_customer_group_test (class_id, class_description, sales_person, inactive) VALUES ('$class_id', '$class_desc', '$sales_person', '$inactive')
                ON DUPLICATE KEY UPDATE class_description = VALUES(class_description),
                sales_person = VALUES(sales_person), inactive = VALUES(inactive)");

                // Get last inserted or updated ID

                if ($this->db->getLastId() > 0) {
                    $ins_count++;
                }
            }
            
            return $ins_count;
        } catch (Exception $e) {
            Logger::log("Error inserting customers: " . $e->getMessage(), "ERROR");
            return false;
        }
    }

    public function insertCustomersToWebstore($customers)
    {   
        $this->load->model('account/customer');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $this->load->helper('logger'); // Load Logger Helper

        $count = 0;
        $batchSize = 500;
        $chunks = array_chunk($customers, $batchSize); // Split into chunks of 1000
        $status = ['0' => '1', '1' => '0'];

        $before_cnt = $this->model_account_customer->getCustomerCount();

        try {
            foreach ($chunks as $batch) {
                $values = [];
                $placeholders = [];

                foreach ($batch as $customer) {
                    $values[] = "'" .$this->model_account_customer->getCustomerGroupByClassId(trim($this->db->escape($customer['CUSTCLAS'])))."'";
                    $values[] = "'" . trim($this->db->escape($customer['CUSTNMBR'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['CUSTNAME'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['CUSTCLAS'])) . "'";
                    $values[] = 1;
                    $values[] = "'" . trim($this->db->escape($customer['CNTCPRSN'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['ADRSCODE'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['ADDRESS1'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['ADDRESS2'])) . "'";

                    // Get country ID
                    $country_id = $this->model_localisation_country->getCountryId(trim($this->db->escape($customer['COUNTRY'])));
                    $values[] = "'" . $country_id . "'";

                    $values[] = "'" . trim($this->db->escape($customer['CITY'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['ZIP'])) . "'";

                    // Get state/region ID
                    $region_id = $this->model_localisation_zone->getRegionId($country_id, trim($this->db->escape($customer['STATE'])));
                    $values[] = "'" . $region_id . "'";
                    $values[] = "'" . trim($this->db->escape($customer['PHONE1'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['PHONE2'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['FAX'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['PRBTADCD'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['PRSTADCD'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['STADDRCD'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['SLPRSNID'])) . "'";
                    $values[] = "'" . trim($this->db->escape($customer['INACTIVE'])) . "'";
                    $values[] = "'" . trim($this->db->escape($status[$customer['INACTIVE']])) . "'";

                    $placeholders[] = "(" . implode(", ", $values) . ")";
                    $values = []; // Reset values for the next row
                }

                $sql = "INSERT INTO " . DB_PREFIX . "customer_test 
                        (customer_group_id, customer_code, customer_name, customer_class_id, is_main_customer, contact_person, address_code, 
                        address1, address2, country, city, zip, state, telephone, telephone_1, fax, primary_billing_code, 
                        primary_shipping_code, primary_standard_code, sales_person_id, inactive, status) 
                        VALUES " . implode(",", $placeholders);
                
                // Execute query
                $this->db->query($sql);
                
            }
            $after_cnt = $this->model_account_customer->getCustomerCount();
            $count = $after_cnt - $before_cnt;

            Logger::log("Successfully inserted $count customer records into Webstore DB", "INFO");
            return $count;
        } catch (Exception $e) {
            Logger::log("Error inserting customer records: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
}
