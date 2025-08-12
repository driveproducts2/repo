<?php

date_default_timezone_set('America/Toronto');

class ModelApiOrder extends Model
{
    private $orders_table = "oc_order";
    // private $orders_product_table = "oc_order_product";
    // private $orders_total_table = "oc_order_total";

    public function getOrdersFromWebstore(): array
    {   
        $this->load->model('api/order');
        $this->load->model('account/customer');
        $this->load->model('account/order');
        $result = [];

        $query = "SELECT order_id, customer_id, firstname, lastname, shipping_address_code, shipping_address_1, shipping_address_2, shipping_city, shipping_postcode, shipping_country_id, shipping_zone_id, customer_po_number, comment, total, currency_code, date_added FROM ".$this->orders_table." WHERE sync_with_gp = 0";
        $order_result = $this->db->query($query);

        // SOPTYPE, SOPNUMBE, LNITMSEQ, ITEMNMBR, ITEMDESC, UOFM, LOCNCODE, 
        // ORUNTCST, OREXTCST, ORUNTPRC, OXTNDPRC, QUANTITY, 
        // QTYTOINV, QTYFULFI, PRCLEVEL, PRSTADCD, ShipToName, Address1

        foreach ($order_result->rows as $res) {
            $customer_detail = $this->model_account_customer->getCustomer($res['customer_id']);
            $order_detail['order_id'] = $res['order_id'];
            $order_detail['customer_code'] = $customer_detail['customer_code'];
            $order_detail['customer_name'] = $customer_detail['customer_name']; 
            $order_detail['shipping_address_code'] = $res['shipping_address_code']; 
            $order_detail['ship_to_name'] = $res['firstname'].' '.$res['lastname']; 
            $order_detail['shipping_address_1'] = $res['shipping_address_1'];
            $order_detail['shipping_address_2'] = $res['shipping_address_2'];
            $order_detail['shipping_city'] = $res['shipping_city'];
            $order_detail['shipping_postcode'] = $res['shipping_postcode'];
            $order_detail['currency_code'] = $res['currency_code']; 
            $order_detail['customer_po_number'] = $res['customer_po_number']; 
            $order_detail['comment'] = $res['comment']; 
            $order_detail['date_added'] = date('Y-m-d 00:00:00', strtotime($res['date_added']));            

            $result[$res['order_id']]['order_details'] = $order_detail;
            $result[$res['order_id']]['order_total'] = $this->model_account_order->getOrderTotals($res['order_id']);
            $result[$res['order_id']]['order_products'] = $this->model_account_order->getOrderProducts($res['order_id']);

        }
        //Logger::log(json_encode($result));
        return $result;
    }

    public function updateSyncedOrdersToWebstore(array $data): int
    {
        $rows_affected = 0;
        $date_modified = date('Y-m-d H:i:s');

        if (isset($data) && !empty($data)) {
            foreach ($data as $value) {
                $order_id = isset($value['id']) ? $this->db->escape($value['id']) : '';
                $gp_order_num = isset($value['weborder_number']) ? $this->db->escape($value['weborder_number']) : '';
                
                $this->db->query("UPDATE oc_order SET gp_order_no = '".$gp_order_num."', sync_with_gp = 1, order_status_id = 2, date_modified = '".$date_modified."' WHERE order_id = '".$order_id."'");

                if ($this->db->countAffected() > 0) {
                    $rows_affected += 1;
                }
            }
        }

        return $rows_affected;
    }

}
