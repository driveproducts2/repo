<?php

date_default_timezone_set('America/Toronto');

class ModelApiPricing extends Model
{
    private $price_sheet_table = 'oc_price_sheet_master';
    private $item_to_price_group_table = "oc_pricegroup_to_item";
    private $pricebook_to_customer_table = "oc_pricebook_to_customer";
    private $pricesheet_to_customer_table = "oc_customer_price_sheet";
    private $item_prices_table = "oc_price_list";

    //Insert/Update price sheet master records in webstore
    public function insertPriceSheetToWebstore(array $data): int 
    {
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger'); // Load Logger Helper

            $batchSize = 1000;
            $batch_arr = array_chunk($data, $batchSize);
            $existing_data = [];
            $current_date_time = date('Y-m-d H:i:s');

            $query = $this->db->query("SELECT `price_sheet_id`, `description`, `is_ntpr_only`, `is_active`, `start_date`, `end_date`, `is_promo`, `currency_id` FROM ".$this->price_sheet_table);
            foreach ($query->rows as $data) {
                $existing_data[$data['price_sheet_id']] = $data;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];
                $update_pricesheet = [];

                foreach ($chunk as $p) {
                    $price_sheet_id   = $this->db->escape($p['PRCSHID']);
                    $description  = $this->db->escape($p['DESCEXPR']);
                    $is_ntpr_only   = $this->db->escape($p['NTPRONLY']);
                    $is_active   = $this->db->escape($p['ACTIVE']);
                    $start_date = date('Y-m-d H:i:s' ,strtotime($this->db->escape($p['STRTDATE'])));
                    $end_date = date('Y-m-d H:i:s' ,strtotime($this->db->escape($p['ENDDATE'])));
                    $is_promo   = $this->db->escape($p['PROMO']);
                    $currency_id = $this->db->escape($p['CURNCYID']);

                    if(!isset($existing_data[$price_sheet_id])) {
                        $insertValues[] = "('$price_sheet_id', '$description', '$is_ntpr_only', '$is_active', '$start_date', '$end_date', '$is_promo', '$currency_id', '1', '$current_date_time')";
                    } else {
                        //price_sheet_id, description, is_ntpr_only, is_active, start_date, end_date, is_promo, currency_id
                        if ($description != $existing_data[$price_sheet_id]['description'] || $is_ntpr_only != $existing_data[$price_sheet_id]['is_ntpr_only'] || $is_active != $existing_data[$price_sheet_id]['is_active'] || $start_date != $existing_data[$price_sheet_id]['start_date'] || $end_date != $existing_data[$price_sheet_id]['end_date'] || $is_promo != $existing_data[$price_sheet_id]['is_promo'] || $currency_id != $existing_data[$price_sheet_id]['currency_id']) {

                            $update_pricesheet[] = "'$price_sheet_id'";
                            $case_description[] = "WHEN '$price_sheet_id' THEN '$description'";
                            $case_is_ntpr_only[] = "WHEN '$price_sheet_id' THEN $is_ntpr_only";
                            $case_is_active[] = "WHEN '$price_sheet_id' THEN '$is_active'";
                            $case_start_date[] = "WHEN '$price_sheet_id' THEN '$start_date'";
                            $case_end_date[] = "WHEN '$price_sheet_id' THEN '$end_date'";
                            $case_is_promo[] = "WHEN '$price_sheet_id' THEN '$is_promo'";
                            $case_currency_id[] = "WHEN '$price_sheet_id' THEN '$currency_id'";
                        }
                    }
                }
                
                // Step 3: Run the bulk INSERT
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO ".$this->price_sheet_table." (price_sheet_id, description, is_ntpr_only, is_active, start_date, end_date, is_promo, currency_id, sync_with_gp, added_date) VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows inserted : " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to insert data into mysql database", "ERROR");
                    }
                }

                if (!empty($update_pricesheet)) {
                    //description, is_ntpr_only, is_active, start_date, end_date, is_promo, currency_id
                    $update_sql = "UPDATE ".$this->price_sheet_table." SET description = CASE price_sheet_id " . implode("\n", $case_description) . " END, is_ntpr_only = CASE price_sheet_id " . implode("\n", $case_is_ntpr_only) . " END, is_active = CASE price_sheet_id " . implode("\n", $case_is_active) . " END, start_date = CASE price_sheet_id " . implode("\n", $case_start_date) . " END, end_date = CASE price_sheet_id " . implode("\n", $case_end_date) . " END, is_promo = CASE price_sheet_id " . implode("\n", $case_is_promo) . " END, currency_id = CASE price_sheet_id " . implode("\n", $case_currency_id) . " END, updated_date = '$current_date_time' WHERE price_sheet_id IN (" . implode(',', $update_pricesheet) . ")";

                    if ($this->db->query($update_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows updated : " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to update data into mysql database", "ERROR");
                    }
                }

                Logger::log("Total rows affected in price sheet master table : " . $totalRowsAffected, "INFO");
            }
            
        } catch (\Exception $e) {
            Logger::log("Error inserting records: " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

    //Insert/Update price group to item mapping records in webstore
    public function insertItemPriceGroupToWebstore(array $data): int
    {
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger');

            $batchSize = 1000;
            $batch_arr = array_chunk($data, $batchSize);
            $current_date_time = date('Y-m-d H:i:s');

            // Fetch existing data from DB
            $existing_data = [];
            $query = $this->db->query("SELECT price_group_id, item_number, sequence_number, dex_row_id FROM {$this->item_to_price_group_table}");
            foreach ($query->rows as $row) {
                $key = $row['price_group_id'] . '|' . $row['item_number'];
                $existing_data[$key] = $row;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];
                $updateCases_seq = [];
                $updateCases_dex = [];
                $updateKeys = [];

                foreach ($chunk as $p) {
                    $price_group_id = $this->db->escape($p['PRCGRPID']);
                    $item_number    = $this->db->escape($p['ITEMNMBR']);
                    $seq_number     = $this->db->escape($p['SEQNUMBR']);
                    $dex_row_id     = $this->db->escape($p['DEX_ROW_ID']);

                    $key = $price_group_id . '|' . $item_number;

                    if (!isset($existing_data[$key])) {
                        // New record
                        $insertValues[] = "('$price_group_id', '$item_number', '$seq_number', '$dex_row_id', '1', '$current_date_time')";
                    } else {
                        // Check for changes before updating
                        $existing = $existing_data[$key];
                        if ($existing['sequence_number'] !== $seq_number || $existing['dex_row_id'] !== $dex_row_id) {
                            $updateKeys[] = "('$price_group_id', '$item_number')";
                            $updateCases_seq[] = "WHEN price_group_id = '$price_group_id' AND item_number = '$item_number' THEN '$seq_number'";
                            $updateCases_dex[] = "WHEN price_group_id = '$price_group_id' AND item_number = '$item_number' THEN '$dex_row_id'";
                        }
                    }
                }

                // Bulk INSERT
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO {$this->item_to_price_group_table} (price_group_id, item_number, sequence_number, dex_row_id, sync_with_gp, created_date) VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsAffected += $affected;
                        Logger::log("Total records inserted : $affected", 'INFO');
                    } else {
                        Logger::log("Insert query failed: $insert_sql", 'ERROR');
                    }
                }

                // Bulk UPDATE (optional and safe)
                if (!empty($updateCases_seq)) {
                    $update_sql = "UPDATE {$this->item_to_price_group_table} SET 
                        sequence_number = CASE 
                            " . implode("\n", $updateCases_seq) . "
                            ELSE sequence_number END,
                        dex_row_id = CASE 
                            " . implode("\n", $updateCases_dex) . "
                            ELSE dex_row_id END, sync_with_gp = 1, updated_date = '$current_date_time'
                        WHERE (price_group_id, item_number) IN (" . implode(',', $updateKeys) . ")";

                    if ($this->db->query($update_sql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsAffected += $affected;
                        Logger::log("Total records updated : $affected", 'INFO');
                    } else {
                        Logger::log("Update query failed: $update_sql", 'ERROR');
                    }
                }
            }

            Logger::log("Total rows affected in current iteration : $totalRowsAffected");
        } catch (Exception $e) {
            Logger::log("Error in inserting Item to PriceGroup table in Webstore database: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

    //Insert/Update price book to customer mapping records in webstore
    public function insertPriceBookCustomerToWebstore(array $data): int
    {
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger');

            $batchSize = 1000;
            $batch_arr = array_chunk($data, $batchSize);
            $current_date_time = date('Y-m-d H:i:s');

            // Fetch existing data from DB
            $existing_data = [];
            $query = $this->db->query("SELECT pricebook_id, promo_detail_code, link_code, sequence_number, dex_row_id FROM {$this->pricebook_to_customer_table}");
            foreach ($query->rows as $row) {
                $key = $row['pricebook_id'] . '|' . $row['link_code'];
                $existing_data[$key] = $row;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];
                $updateCases_promoCode = [];
                $updateCases_seq = [];
                $updateCases_dex = [];
                $updateKeys = [];

                foreach ($chunk as $p) {
                    //PRCBKID	PRODTCOD	LINKCODE	SEQNUMBR	DEX_ROW_ID
                    $pricebook_id = $this->db->escape($p['PRCBKID']);
                    $promo_code    = $this->db->escape($p['PRODTCOD']);
                    $link_code      = $this->db->escape($p['LINKCODE']);
                    $seq_number     = $this->db->escape($p['SEQNUMBR']);
                    $dex_row_id     = $this->db->escape($p['DEX_ROW_ID']);

                    $key = $pricebook_id . '|' . $link_code;

                    if (!isset($existing_data[$key])) {
                        // New record
                        $insertValues[] = "('$pricebook_id', '$promo_code', '$link_code', '$seq_number', '$dex_row_id', '1', '$current_date_time')";
                    } else {
                        // Check for changes before updating
                        $existing = $existing_data[$key];
                        if ($existing['promo_detail_code'] !== $promo_code || $existing['sequence_number'] !== $seq_number || $existing['dex_row_id'] !== $dex_row_id) {
                            //pricebook_id, promo_detail_code, link_code, sequence_number, dex_row_id
                            $updateKeys[] = "('$pricebook_id', '$link_code')";
                            $updateCases_promoCode[] = "WHEN pricebook_id = '$pricebook_id' AND link_code = '$link_code' THEN '$promo_code'";
                            $updateCases_seq[] = "WHEN pricebook_id = '$pricebook_id' AND link_code = '$link_code' THEN '$seq_number'";
                            $updateCases_dex[] = "WHEN pricebook_id = '$pricebook_id' AND link_code = '$link_code' THEN '$dex_row_id'";
                        }
                    }
                }

                // Bulk INSERT
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO {$this->pricebook_to_customer_table} (pricebook_id, promo_detail_code, link_code, sequence_number, dex_row_id, sync_with_gp, created_date) VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsAffected += $affected;
                        Logger::log("Inserted rows: $affected", 'INFO');
                    } else {
                        Logger::log("Insert query failed: $insert_sql", 'ERROR');
                    }
                }

                // Bulk UPDATE (optional and safe)
                if (!empty($updateCases_seq)) {
                    $update_sql = "UPDATE {$this->pricebook_to_customer_table} SET promo_detail_code = CASE " . implode("\n", $updateCases_promoCode) . " ELSE promo_detail_code END,
                        sequence_number = CASE " . implode("\n", $updateCases_seq) . " ELSE sequence_number END,
                        dex_row_id = CASE " . implode("\n", $updateCases_dex) . " ELSE dex_row_id END, 
                        sync_with_gp = 1, 
                        updated_date = '$current_date_time' WHERE (pricebook_id, link_code) IN (" . implode(',', $updateKeys) . ")";

                    if ($this->db->query($update_sql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsAffected += $affected;
                        Logger::log("Updated rows: $affected", 'INFO');
                    } else {
                        Logger::log("Update query failed: $update_sql", 'ERROR');
                    }
                }
            }

            Logger::log("Total rows affected in current iteration : $totalRowsAffected", 'INFO');
        } catch (Exception $e) {
            Logger::log("Error in inserting or updating price book to customer mapping table: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

    //Insert/Update price sheet to price book/customer mapping records in webstore
    public function insertPriceSheetCustomerToWebstore(array $data): int
    {
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger');

            $batchSize = 1000;
            $batch_arr = array_chunk($data, $batchSize);
            $current_date_time = date('Y-m-d H:i:s');

            // Fetch existing data from DB
            $existing_data = [];
            $query = $this->db->query("SELECT price_sheet_id, promo_detail_code, customer_group_id, sequence_number, price_sheet_sequence, dex_row_id FROM {$this->pricesheet_to_customer_table}");
            foreach ($query->rows as $row) {
                $key = $row['price_sheet_id'] . '|' . $row['promo_detail_code']. '|' . $row['customer_group_id'];
                $existing_data[$key] = $row;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];
                $updateCases_seq = [];
                $updateCases_pseq = [];
                $updateCases_dex = [];
                $updateKeys = [];

                foreach ($chunk as $p) {
                    //PRCSHID	PRODTCOD	LINKCODE	SEQNUMBR	PSSEQNUM	DEX_ROW_ID
                    $pricesheet_id = $this->db->escape($p['PRCSHID']);
                    $promo_code    = $this->db->escape($p['PRODTCOD']);
                    $link_code      = $this->db->escape($p['LINKCODE']);
                    $seq_number     = $this->db->escape($p['SEQNUMBR']);
                    $pseq_number     = $this->db->escape($p['PSSEQNUM']);
                    $dex_row_id     = $this->db->escape($p['DEX_ROW_ID']);

                    $key = $pricesheet_id . '|' . $promo_code . '|' . $link_code;

                    if (!isset($existing_data[$key])) {
                        $insertValues[] = "('$pricesheet_id', '$promo_code', '$link_code', '$seq_number', '$pseq_number', '$dex_row_id', '1', '$current_date_time')";
                    } else {
                        // Check for changes before updating
                        $existing = $existing_data[$key];
                        if ($existing['price_sheet_sequence'] !== $pseq_number || $existing['sequence_number'] !== $seq_number || $existing['dex_row_id'] !== $dex_row_id) {
                            $updateKeys[] = "('$pricesheet_id', '$promo_code', '$link_code')";
                            $updateCases_seq[] = "WHEN price_sheet_id = '$pricesheet_id' AND promo_detail_code = '$promo_code' AND customer_group_id = '$link_code' THEN '$seq_number'";
                            $updateCases_pseq[] = "WHEN price_sheet_id = '$pricesheet_id' AND promo_detail_code = '$promo_code' AND customer_group_id = '$link_code' THEN '$pseq_number'";
                            $updateCases_dex[] = "WHEN price_sheet_id = '$pricesheet_id' AND promo_detail_code = '$promo_code' AND customer_group_id = '$link_code' THEN '$dex_row_id'";
                        }
                    }
                }

                // Bulk INSERT
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO {$this->pricesheet_to_customer_table} (price_sheet_id, promo_detail_code, customer_group_id, sequence_number, price_sheet_sequence, dex_row_id, sync_with_gp, created_date) VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsAffected += $affected;
                        Logger::log("Inserted rows: $affected", 'INFO');
                    } else {
                        Logger::log("Insert query failed: $insert_sql", 'ERROR');
                    }
                }

                // Bulk UPDATE (optional and safe)
                if (!empty($updateCases_seq)) {
                    $update_sql = "UPDATE {$this->pricesheet_to_customer_table} SET price_sheet_sequence = CASE " . implode("\n", $updateCases_pseq) . " ELSE price_sheet_sequence END,
                        sequence_number = CASE " . implode("\n", $updateCases_seq) . " ELSE sequence_number END,
                        dex_row_id = CASE " . implode("\n", $updateCases_dex) . " ELSE dex_row_id END, 
                        sync_with_gp = 1, 
                        updated_date = '$current_date_time' WHERE (price_sheet_id, promo_detail_code, customer_group_id) IN (" . implode(',', $updateKeys) . ")";

                    if ($this->db->query($update_sql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsAffected += $affected;
                        Logger::log("Updated rows: $affected", 'INFO');
                    } else {
                        Logger::log("Update query failed: $update_sql", 'ERROR');
                    }
                }
            }

            Logger::log("Total rows affected in current iteration : ".$totalRowsAffected);

        } catch (Exception $e) {
            Logger::log("Error in inserting or updating price sheet to customer/pricebook mapping table: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

    //Insert/Update item prices records in webstore
    public function insertItemPricesToWebstore(array $data): int
    {
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger');

            $batchSize = 5000;
            $batch_arr = array_chunk($data, $batchSize);
            $current_date_time = date('Y-m-d H:i:s');

            // Fetch existing data from DB
            $existing_data = [];

            $query = $this->db->query("SELECT price_sheet_id, item_type, product_sku, unit_of_measure, quantity_from, quantity_to, item_price, equivalent_qty, qty_base_uom FROM " . $this->item_prices_table);

            foreach ($query->rows as $row) {
                $key = $row['price_sheet_id'] . '|' .
                    $row['item_type'] . '|' .
                    $row['product_sku'] . '|' .
                    $row['unit_of_measure'] . '|' .
                    number_format((float)$row['quantity_from'], 5, '.', '') . '|' .
                    number_format((float)$row['quantity_to'], 5, '.', '') . '|' .
                    number_format((float)$row['item_price'], 5, '.', '') . '|' .
                    number_format((float)$row['equivalent_qty'], 5, '.', '') . '|' .
                    number_format((float)$row['qty_base_uom'], 5, '.', '');

                $existing_data[$key] = true;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];

                foreach ($chunk as $p) {
                    // Raw values for comparison
                    $pricesheet_id = $p['PRCSHID'];
                    $item_type = $p['EPITMTYP'];
                    $product_sku = $p['ITEMNMBR'];
                    $uom = $p['UOFM'];
                    $qty_from = number_format((float)$p['QTYFROM'], 5, '.', '');
                    $qty_to = number_format((float)$p['QTYTO'], 5, '.', '');
                    $price = number_format((float)$p['PSITMVAL'], 5, '.', '');
                    $equ_uom_qty = number_format((float)$p['EQUOMQTY'], 5, '.', '');
                    $qty_uom = number_format((float)$p['QTYBSUOM'], 5, '.', '');

                    $key = $pricesheet_id . '|' . $item_type . '|' . $product_sku . '|' . $uom . '|' . $qty_from . '|' . $qty_to . '|' . $price . '|' . $equ_uom_qty . '|' . $qty_uom;

                    if (!isset($existing_data[$key])) {
                        //Logger::log("Inserting: $key");

                        // Escape only during SQL generation
                        $insertValues[] = "(" .
                            "'" . $this->db->escape($pricesheet_id) . "', " .
                            "'" . $this->db->escape($item_type) . "', " .
                            "'" . $this->db->escape($product_sku) . "', " .
                            "'" . $this->db->escape($uom) . "', " .
                            "'" . $this->db->escape($qty_from) . "', " .
                            "'" . $this->db->escape($qty_to) . "', " .
                            "'" . $this->db->escape($price) . "', " .
                            "'" . $this->db->escape($equ_uom_qty) . "', " .
                            "'" . $this->db->escape($qty_uom) . "', " .
                            "'1', " .
                            "'" . $this->db->escape($current_date_time) . "'" .
                        ")";
                    }
                }

                // Bulk insert
                if (!empty($insertValues)) {
                    $this->db->query("SET GLOBAL max_allowed_packet = 67108864"); //increase query string length max size to 64 mb.
                    
                    $insert_sql = "INSERT INTO {$this->item_prices_table} 
                        (price_sheet_id, item_type, product_sku, unit_of_measure, quantity_from, quantity_to, item_price, equivalent_qty, qty_base_uom, sync_with_gp, created_date) 
                        VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsAffected += $affected;
                        //Logger::log("Inserted rows: $affected", 'INFO');
                    } else {
                        Logger::log("Insert query failed: $insert_sql", 'ERROR');
                    }
                }
            }

            // Optional cleanup
            $this->db->query("SET GLOBAL max_allowed_packet = 1048576");

        } catch (Exception $e) {
            Logger::log("Error in inserting or updating item prices table: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }


}
