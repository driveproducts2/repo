<?php

date_default_timezone_set('America/Toronto');

class ModelApiProduct extends Model
{   
    private $oc_item_class_table = "oc_item_classes";
    private $oc_product_table = "oc_product";
    private $oc_product_description_table = "oc_product_description";
    private $oc_uom_schedule_table = "oc_uom_schedule";
    private $oc_warehouse_table = "oc_warehouse";
    private $oc_quantity_table = "oc_product_to_location";

    //insert/update item classes into webstore
    public function insertItemClassToWebstore(array $item_class): int
    {
        try {
            $this->load->helper('logger'); // Load Logger Helper

            $batchSize = 500;
            $totalRowsAffected = 0;
            $batch_arr = array_chunk($item_class, $batchSize);
            $existing_data = [];
            $current_date_time = date('Y-m-d H:i:s');

            $query = $this->db->query("SELECT item_class, item_class_desc, item_type, item_class_genrl_desc, item_class_tax_schdl_id, decimal_qty, uom_schedule, price_level, price_group, sales_tax_option FROM ".$this->oc_item_class_table);

            foreach ($query->rows as $data) {
                $existing_data[$data['item_class']] = $data;
            }
            //Logger::log(json_encode($existing_data)); die;

            foreach ($batch_arr as $chunk) {
                $insertValues = [];

                // These will be used for CASE-based bulk update
                $update_itemclscd = [];
                $case_itmclsdsc   = [];
                $case_itemtype   = [];
                $case_itemgnrldesc = [];
                $case_itmtshid   = [];
                $case_decqty   = [];
                $case_uomschdl   = [];
                $case_prclevel   = [];
                $case_pricegroup = [];
                $case_salestaxopt = [];

                foreach ($chunk as $p) {
                    $itmclscd   = $this->db->escape($p['ITMCLSCD']);
                    $itmclsdsc  = $this->db->escape($p['ITMCLSDC']);
                    $itemtype   = (int)$p['ITEMTYPE'];
                    $itmgedsc   = $this->db->escape($p['ITMGEDSC']);
                    $itmtshid   = $this->db->escape($p['ITMTSHID']);
                    $itmdecqty  = $this->db->escape($p['DECPLQTY']);
                    $uomschdl   = $this->db->escape($p['UOMSCHDL']);
                    $prclevel   = $this->db->escape($p['PRCLEVEL']);
                    $pricegroup = $this->db->escape($p['PriceGroup']);
                    $taxopt     = $this->db->escape($p['TAXOPTNS']);

                    if (!isset($existing_data[$itmclscd])) {
                        $insertValues[] = "('$itmclscd', '$itmclsdsc', $itemtype, '$itmgedsc', '$itmtshid', '$itmdecqty', '$uomschdl', '$prclevel', '$pricegroup', '$taxopt', '1', '$current_date_time')";
                    } else {

                        // Compare field-by-field
                        $existing = $existing_data[$itmclscd];
                        $changed = false;

                        if (
                            $existing['item_class_desc'] !== $itmclsdsc ||
                            (int)$existing['item_type'] !== $itemtype ||
                            $existing['item_class_genrl_desc'] !== $itmgedsc ||
                            $existing['item_class_tax_schdl_id'] !== $itmtshid ||
                            $existing['decimal_qty'] !== $itmdecqty ||
                            $existing['uom_schedule'] !== $uomschdl ||
                            $existing['price_level'] !== $prclevel ||
                            $existing['price_group'] !== $pricegroup ||
                            $existing['sales_tax_option'] !== $taxopt
                        ) {
                            $changed = true;
                        }

                        // Existing: prepare for update
                        if ($changed) {
                            $update_itemclscd[]     = "'$itmclscd'";
                            $case_itmclsdsc[]       = "WHEN '$itmclscd' THEN '$itmclsdsc'";
                            $case_itemtype[]        = "WHEN '$itmclscd' THEN '$itemtype'";
                            $case_itemgnrldesc[]    = "WHEN '$itmclscd' THEN '$itmgedsc'";
                            $case_itmtshid[]        = "WHEN '$itmclscd' THEN '$itmtshid'";
                            $case_decqty[]          = "WHEN '$itmclscd' THEN '$itmdecqty'";
                            $case_uomschdl[]        = "WHEN '$itmclscd' THEN '$uomschdl'";
                            $case_prclevel[]        = "WHEN '$itmclscd' THEN '$prclevel'";
                            $case_pricegroup[]      = "WHEN '$itmclscd' THEN '$pricegroup'";
                            $case_salestaxopt[]     = "WHEN '$itmclscd' THEN '$taxopt'";
                        }
                    }
                }

                //Logger::log(json_encode($update_itemclscd)); die;

                // Step 3: Run the bulk INSERT
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO ".$this->oc_item_class_table." (item_class, item_class_desc, item_type, item_class_genrl_desc, item_class_tax_schdl_id, decimal_qty, uom_schedule, price_level, price_group, sales_tax_option, sync_with_gp, created_at) VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows inserted : " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to insert data into mysql database", "ERROR");
                    }
                }

                // Step 4: Run the bulk UPDATE (single query using CASE)
                if (!empty($update_itemclscd)) {
                    $update_sql = "UPDATE ".$this->oc_item_class_table." SET 
                    item_class_desc = CASE item_class " . implode("\n", $case_itmclsdsc) . " END, 
                    item_type = CASE item_class " . implode("\n", $case_itemtype) . " END, 
                    item_class_genrl_desc = CASE item_class " . implode("\n", $case_itemgnrldesc) . " END, 
                    item_class_tax_schdl_id = CASE item_class " . implode("\n", $case_itmtshid) . " END, 
                    decimal_qty = CASE item_class " . implode("\n", $case_decqty) . " END, 
                    uom_schedule = CASE item_class " . implode("\n", $case_uomschdl) . " END, 
                    price_level = CASE item_class " . implode("\n", $case_prclevel) . " END, 
                    price_group = CASE item_class " . implode("\n", $case_pricegroup) . " END, 
                    sales_tax_option = CASE item_class " . implode("\n", $case_salestaxopt) . " END, 
                    updated_at = '$current_date_time',
                    sync_with_gp = '1'
                    WHERE item_class IN (" . implode(',', $update_itemclscd) . ")";


                    //Logger::log($update_sql); die;
                    if ($this->db->query($update_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows updated : " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to update data into mysql database", "ERROR");
                    }
                }
            }
            Logger::log("Total rows affected : " . $totalRowsAffected, "INFO");
        } catch (Exception $e) {
            Logger::log("Error inserting item class records: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

    //insert/update items(products) into webstore
    public function insertItemsToWebstore(array $items): int
    {   
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger');
            $batchSize = 1000;
            $language_id = 1;
            $current_date_time = date('Y-m-d H:i:s');
            $batch_arr = array_chunk($items, $batchSize);

            foreach ($batch_arr as $chunk) {
                $totalRowsInsertedItem = 0;
                $totalRowsInsertedItemDesc = 0;
                $totalRowsUpdatedItem = 0;
                $totalRowsUpdatedItemDesc = 0;

                $models = array_map(fn($item) => $this->db->escape($item['ITEMNMBR']), $chunk);
                $modelList = "'" . implode("','", $models) . "'";

                // Fetch existing items for this batch
                $existingItems = [];
                $query = $this->db->query("SELECT product_id, model FROM {$this->oc_product_table} WHERE model IN ($modelList)");
                foreach ($query->rows as $row) {
                    $existingItems[$row['model']] = $row['product_id'];
                }

                // Fetch existing descriptions
                $existingDescriptions = [];
                if (!empty($existingItems)) {
                    $idList = implode(",", array_values($existingItems));
                    $descQuery = $this->db->query("SELECT product_id FROM {$this->oc_product_description_table} WHERE language_id = $language_id AND product_id IN ($idList)");
                    foreach ($descQuery->rows as $row) {
                        $existingDescriptions[$row['product_id']] = true;
                    }
                }

                $insertProductValues = [];
                $insertDescValues = [];

                $updateModels = [];
                $updateProductCases = [];
                $updateDescCases = [];
                $descUpdateIds = [];

                // Prepare insert/update
                foreach ($chunk as $item) {
                    $model = $this->db->escape($item['ITEMNMBR']);
                    $name = $this->db->escape($item['ITEMDESC']);
                    $item_class_code = $this->db->escape($item['ITMCLSCD']);
                    $item_type = (int)$item['ITEMTYPE'];
                    $sales_tax_option = $this->db->escape($item['TAXOPTNS']);
                    $item_class_tax_schdl_id = $this->db->escape($item['ITMTSHID']);
                    $selling_uom = $this->db->escape($item['SELNGUOM']);
                    $price_level = $this->db->escape($item['PRCLEVEL']);
                    $price_group = $this->db->escape($item['PriceGroup']);
                    $status = (isset($item['INACTIVE']) && $item['INACTIVE'] == 1) ? 0 : 1;
                    $gp_timestamp = $this->db->escape($item['DEX_ROW_TS']);

                    if (!isset($existingItems[$model])) {
                        $insertProductValues[] = "('$model', '$name', '$item_class_code', $item_type, '$sales_tax_option', '$item_class_tax_schdl_id', '$selling_uom', '$price_level', '$price_group', 0, NULL, 0.0000, 0, 1, 1, 0, 0, $status, '$gp_timestamp', '$current_date_time', 1)";
                    } else {
                        $updateModels[] = "'$model'";
                        $updateProductCases['name'][] = "WHEN '$model' THEN '$name'";
                        $updateProductCases['item_class_code'][] = "WHEN '$model' THEN '$item_class_code'";
                        $updateProductCases['item_type'][] = "WHEN '$model' THEN $item_type";
                        $updateProductCases['sales_tax_option'][] = "WHEN '$model' THEN '$sales_tax_option'";
                        $updateProductCases['item_class_tax_schdl_id'][] = "WHEN '$model' THEN '$item_class_tax_schdl_id'";
                        $updateProductCases['selling_uom'][] = "WHEN '$model' THEN '$selling_uom'";
                        $updateProductCases['price_level'][] = "WHEN '$model' THEN '$price_level'";
                        $updateProductCases['price_group'][] = "WHEN '$model' THEN '$price_group'";
                        $updateProductCases['status'][] = "WHEN '$model' THEN $status";
                        $updateProductCases['gp_timestamp'][] = "WHEN '$model' THEN '$gp_timestamp'";
                    }
                }

                // Step 1: Insert new products
                if (!empty($insertProductValues)) {
                    $insertSql = "INSERT INTO {$this->oc_product_table} 
                    (model, name, item_class_code, item_type, sales_tax_option, item_class_tax_schdl_id, selling_uom, price_level, price_group, category_id, image, price, tax_class_id, quantity_minimum, quantity_multiple, quantity_maximum, sort_order, status, gp_timestamp, created_at, sync_with_gp) 
                    VALUES " . implode(',', $insertProductValues);

                    if ($this->db->query($insertSql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsInsertedItem += $affected;
                        Logger::log("Number of records inserted in item master table : $affected");
                    }
                }

                // Step 2: Re-fetch product_id for all models
                $fetchAllQuery = $this->db->query("SELECT product_id, model FROM {$this->oc_product_table} WHERE model IN ($modelList)");
                $modelToId = [];
                foreach ($fetchAllQuery->rows as $row) {
                    $modelToId[$row['model']] = $row['product_id'];
                }

                // Step 3: Insert product descriptions
                foreach ($chunk as $item) {
                    $model = $this->db->escape($item['ITEMNMBR']);
                    $name = $this->db->escape($item['ITEMDESC']);
                    $product_id = $modelToId[$model] ?? null;

                    if ($product_id !== null) {
                        if (!isset($existingDescriptions[$product_id])) {
                            $insertDescValues[] = "($product_id, '$model', $language_id, '$name', '', '', '$name', '', '')";
                        } else {
                            $updateDescCases['name'][] = "WHEN $product_id THEN '$name'";
                            $updateDescCases['meta_title'][] = "WHEN $product_id THEN '$name'";
                            $descUpdateIds[] = $product_id;
                        }
                    }
                }

                if (!empty($insertDescValues)) {
                    $insertDescSql = "INSERT INTO {$this->oc_product_description_table} 
                    (product_id, model, language_id, name, description, tag, meta_title, meta_description, meta_keyword) 
                    VALUES " . implode(',', $insertDescValues);

                    if ($this->db->query($insertDescSql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsInsertedItemDesc += $affected;
                        Logger::log("Number of records inserted in item description table : $affected");
                    }
                }

                // Step 4: Update products
                if (!empty($updateModels)) {
                    $updateSql = "UPDATE {$this->oc_product_table} SET 
                        name = CASE model " . implode("\n", $updateProductCases['name']) . " END,
                        item_class_code = CASE model " . implode("\n", $updateProductCases['item_class_code']) . " END,
                        item_type = CASE model " . implode("\n", $updateProductCases['item_type']) . " END,
                        sales_tax_option = CASE model " . implode("\n", $updateProductCases['sales_tax_option']) . " END,
                        item_class_tax_schdl_id = CASE model " . implode("\n", $updateProductCases['item_class_tax_schdl_id']) . " END,
                        selling_uom = CASE model " . implode("\n", $updateProductCases['selling_uom']) . " END,
                        price_level = CASE model " . implode("\n", $updateProductCases['price_level']) . " END,
                        price_group = CASE model " . implode("\n", $updateProductCases['price_group']) . " END,
                        status = CASE model " . implode("\n", $updateProductCases['status']) . " END,
                        gp_timestamp = CASE model " . implode("\n", $updateProductCases['gp_timestamp']) . " END,
                        updated_at = '$current_date_time',
                        sync_with_gp = 1
                        WHERE model IN (" . implode(',', $updateModels) . ")";

                    if ($this->db->query($updateSql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsUpdatedItem += $affected;
                        Logger::log("Total records updated in item master : $affected");
                    }
                }

                // Step 5: Update product descriptions
                if (!empty($descUpdateIds)) {
                    $updateDescSql = "UPDATE {$this->oc_product_description_table} SET 
                        name = CASE product_id " . implode("\n", $updateDescCases['name']) . " END,
                        meta_title = CASE product_id " . implode("\n", $updateDescCases['meta_title']) . " END
                        WHERE product_id IN (" . implode(',', $descUpdateIds) . ") AND language_id = $language_id";

                    if ($this->db->query($updateDescSql)) {
                        $affected = $this->db->countAffected();
                        $totalRowsUpdatedItemDesc += $affected;
                        Logger::log("Total records updated in item description : $affected");
                    }
                }

                $totalRowsAffected += $totalRowsInsertedItem + $totalRowsUpdatedItem;
            }
        } catch (Exception $e) {
            Logger::log("Error in insertItemsToWebstore: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

    //insert/update unit of measurement schedule into webstore
    public function insertUOMScheduleToWebstore($data): int
    {
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger'); // Load Logger Helper

            $batchSize = 500;
            $batch_arr = array_chunk($data, $batchSize);
            $existing_data = [];
            $current_date_time = date('Y-m-d H:i:s');

            // Load existing data
            $query = $this->db->query("SELECT uomschdl, uofm, equivuom, equomqty, qtybsuom, uofmlongdesc, dex_row_ts FROM " . $this->oc_uom_schedule_table);
            foreach ($query->rows as $data) {
                $existing_data[$data['uomschdl'] . "|" . $data['uofm']] = $data;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];

                // Reset CASE arrays for each chunk
                $case_equivuom = [];
                $case_equomqty = [];
                $case_qtybsuom = [];
                $case_uofmlongdesc = [];
                $case_dex_row_ts = [];
                $where_conditions = [];

                foreach ($chunk as $p) {
                    $uomschdl = $this->db->escape($p['UOMSCHDL']);
                    $uofm = $this->db->escape($p['UOFM']);
                    $equivuom = $this->db->escape($p['EQUIVUOM']);
                    $equomqty = $this->db->escape($p['EQUOMQTY']);
                    $qtybsuom = $this->db->escape($p['QTYBSUOM']);
                    $uofmlongdesc = $this->db->escape($p['UOFMLONGDESC']);
                    $dex_row_ts = $this->db->escape($p['DEX_ROW_TS']);

                    $key = $uomschdl . "|" . $uofm;

                    if (!isset($existing_data[$key])) {
                        // New record
                        $insertValues[] = "('$uomschdl', '$uofm', '$equivuom', '$equomqty', '$qtybsuom', '$uofmlongdesc', '$dex_row_ts', '1', '$current_date_time')";
                    } else {
                        $existing = $existing_data[$key];
                        if (
                            $equivuom != $existing['equivuom'] ||
                            $equomqty != $existing['equomqty'] ||
                            $qtybsuom != $existing['qtybsuom'] ||
                            $uofmlongdesc != $existing['uofmlongdesc'] ||
                            $dex_row_ts != $existing['dex_row_ts']
                        ) {
                            // Only add to update list if there's a change
                            $case_equivuom[] = "WHEN uomschdl = '$uomschdl' AND uofm = '$uofm' THEN '$equivuom'";
                            $case_equomqty[] = "WHEN uomschdl = '$uomschdl' AND uofm = '$uofm' THEN     '$equomqty'";
                            $case_qtybsuom[] = "WHEN uomschdl = '$uomschdl' AND uofm = '$uofm' THEN '$qtybsuom'";
                            $case_uofmlongdesc[] = "WHEN uomschdl = '$uomschdl' AND uofm = '$uofm' THEN '$uofmlongdesc'";
                            $case_dex_row_ts[] = "WHEN uomschdl = '$uomschdl' AND uofm = '$uofm' THEN '$dex_row_ts'";
                            $where_conditions[] = "(uomschdl = '$uomschdl' AND uofm = '$uofm')";
                        }
                    }
                }

                // Insert new records
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO " . $this->oc_uom_schedule_table . " 
                    (uomschdl, uofm, equivuom, equomqty, qtybsuom, uofmlongdesc, dex_row_ts, sync_with_gp, created_date)
                    VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows inserted: " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to insert data into MySQL database", "ERROR");
                    }
                }

                // Update changed records
                if (!empty($case_equivuom) && !empty($where_conditions)) {
                    $update_sql = "
                    UPDATE " . $this->oc_uom_schedule_table . " 
                    SET
                        equivuom = CASE " . implode("\n", $case_equivuom) . " END,
                        equomqty = CASE " . implode("\n", $case_equomqty) . " END,
                        qtybsuom = CASE " . implode("\n", $case_qtybsuom) . " END,
                        uofmlongdesc = CASE " . implode("\n", $case_uofmlongdesc) . " END,
                        dex_row_ts = CASE " . implode("\n", $case_dex_row_ts) . " END,
                        updated_date = '$current_date_time'
                    WHERE " . implode("\nOR ", $where_conditions);

                    if ($this->db->query($update_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows updated: " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to update data into MySQL database", "ERROR");
                    }
                }
            }
        } catch (\Exception $e) {
            Logger::log("Error inserting records: " . $e->getFile() . " at line " . $e->getLine() . ": " . $e->getMessage(), "ERROR");
        }

        Logger::log("Total rows affected: " . $totalRowsAffected, "INFO");
        return $totalRowsAffected;
    }

    //insert/update warehouse into webstore
    public function insertWarehouseToWebstore(array $data): int
    {
        try {
            $this->load->helper('logger'); // Load Logger Helper

            $batchSize = 500;
            $totalRowsAffected = 0;
            $batch_arr = array_chunk($data, $batchSize);
            $existing_data = [];
            $current_date_time = date('Y-m-d H:i:s');

            $query = $this->db->query("SELECT location_code, description, address1, address2, address3, city, state, zipcode, country, phone1, phone2, phone3, fax_number, staxschid, pctaxsch, country_code, status FROM ".$this->oc_warehouse_table);

            foreach ($query->rows as $data) {
                $existing_data[$data['location_code']] = $data;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];

                // These will be used for CASE-based bulk update
                $update_location_code = [];
                $case_description = [];
                $case_address1   = [];
                $case_address2   = [];
                $case_address3   = [];
                $case_city   = [];
                $case_state = [];
                $case_zipcode = [];
                $case_country   = [];
                $case_phone1   = [];
                $case_phone2 = [];
                $case_phone3   = [];
                $case_fax_number   = [];
                $case_staxschid   = [];
                $case_pctaxsch   = [];
                $case_country_code = [];
                $case_status = [];

                foreach ($chunk as $p) {
                    $location_code   = $this->db->escape($p['LOCNCODE']);
                    $description  = $this->db->escape($p['LOCNDSCR']);
                    $address1   = $this->db->escape($p['ADDRESS1']);
                    $address2   = $this->db->escape($p['ADDRESS2']);
                    $address3   = $this->db->escape($p['ADDRESS3']);
                    $city  = $this->db->escape($p['CITY']);
                    $state   = $this->db->escape($p['STATE']);
                    $zipcode   = $this->db->escape($p['ZIPCODE']);
                    $country = $this->db->escape($p['COUNTRY']);
                    $phone1     = $this->db->escape($p['PHONE1']);
                    $phone2   = $this->db->escape($p['PHONE2']);
                    $phone3  = $this->db->escape($p['PHONE3']);
                    $fax_number   = $this->db->escape($p['FAXNUMBR']);
                    $staxschid   = $this->db->escape($p['STAXSCHD']);
                    $pctaxsch   = $this->db->escape($p['PCTAXSCH']);
                    $country_code  = $this->db->escape($p['CCode']);
                    $status   = (isset($p['INACTIVE']) && $p['INACTIVE'] == 0) ? '1' : '0';
                    $sync_with_gp   = '1';
                    $created_date = $updated_date = $current_date_time;

                    if (!isset($existing_data[$location_code])) {
                        $insertValues[] = "('".$location_code."', '".$description."', '".$address1."', '".$address2."', '".$address3."', '".$city."', '".$state."', '".$zipcode."', '".$country."', '".$phone1."', '".$phone2."', '".$phone3."', '".$fax_number."', '".$staxschid."', '".$pctaxsch."', '".$country_code."', '".$status."', '".$sync_with_gp."', '".$created_date."')";
                    } else {

                        // Compare field-by-field
                        $existing = $existing_data[$location_code];
                        $changed = false;

                        if (
                            $existing['description'] !== $description ||
                            $existing['address1'] !== $address1 ||
                            $existing['address2'] !== $address2 ||
                            $existing['address3'] !== $address3 ||
                            $existing['city'] !== $city ||
                            $existing['state'] !== $state ||
                            $existing['zipcode'] !== $zipcode ||
                            $existing['country'] !== $country ||
                            $existing['phone1'] !== $phone1 ||
                            $existing['phone2'] !== $phone2 ||
                            $existing['phone3'] !== $phone3 ||
                            $existing['fax_number'] !== $fax_number ||
                            $existing['staxschid'] !== $staxschid ||
                            $existing['pctaxsch'] !== $pctaxsch ||
                            $existing['country_code'] !== $country_code ||
                            $existing['status'] !== $status
                        ) {
                            $changed = true;
                        }

                        // Existing: prepare for update
                        if ($changed) {
                            $update_location_code[]     = "'".$location_code."'";
                            $case_description[]   = "WHEN '".$location_code."' THEN '".$description."'";
                            $case_address1[]   = "WHEN '".$location_code."' THEN '".$address1."'";
                            $case_address2[]   = "WHEN '".$location_code."' THEN '".$address2."'";
                            $case_address3[]   = "WHEN '".$location_code."' THEN '".$address3."'";
                            $case_city[]   = "WHEN '".$location_code."' THEN '".$city."'";
                            $case_state[] = "WHEN '".$location_code."' THEN '".$state."'";
                            $case_zipcode[] = "WHEN '".$location_code."' THEN '".$zipcode."'";
                            $case_country[]   = "WHEN '".$location_code."' THEN '".$country."'";
                            $case_phone1[]   = "WHEN '".$location_code."' THEN '".$phone1."'";
                            $case_phone2[] = "WHEN '".$location_code."' THEN '".$phone2."'";
                            $case_phone3[]   = "WHEN '".$location_code."' THEN '".$phone3."'";
                            $case_fax_number[]   = "WHEN '".$location_code."' THEN '".$fax_number."'";
                            $case_staxschid[]   = "WHEN '".$location_code."' THEN '".$staxschid."'";
                            $case_pctaxsch[]   = "WHEN '".$location_code."' THEN '".$pctaxsch."'";
                            $case_country_code[] = "WHEN '".$location_code."' THEN '".$country_code."'";
                            $case_status[] = "WHEN '".$location_code."' THEN '".$status."'";
                        }
                    }
                }

                // Step 3: Run the bulk INSERT
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO ".$this->oc_warehouse_table." (location_code, description, address1, address2, address3, city, state, zipcode, country, phone1, phone2, phone3, fax_number, staxschid, pctaxsch, country_code, status, sync_with_gp, created_date) VALUES " . implode(',', $insertValues);

                    if ($this->db->query($insert_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows inserted : " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to insert data into mysql database", "ERROR");
                    }
                }

                // Step 4: Run the bulk UPDATE (single query using CASE)
                if (!empty($update_location_code)) {
                    $update_sql = "UPDATE ".$this->oc_warehouse_table." SET 
                    description = CASE location_code " . implode("\n", $case_description) . " END, 
                    address1 = CASE location_code " . implode("\n", $case_address1) . " END, 
                    address2 = CASE location_code " . implode("\n", $case_address2) . " END, 
                    address3 = CASE location_code " . implode("\n", $case_address3) . " END, 
                    city = CASE location_code " . implode("\n", $case_city) . " END, 
                    state = CASE location_code " . implode("\n", $case_state) . " END, 
                    zipcode = CASE location_code " . implode("\n", $case_zipcode) . " END, 
                    country = CASE location_code " . implode("\n", $case_country) . " END, 
                    phone1 = CASE location_code " . implode("\n", $case_phone1) . " END, 
                    phone2 = CASE location_code " . implode("\n", $case_phone2) . " END, 
                    phone3 = CASE location_code " . implode("\n", $case_phone3) . " END, 
                    fax_number = CASE location_code " . implode("\n", $case_fax_number) . " END, 
                    staxschid = CASE location_code " . implode("\n", $case_staxschid) . " END, 
                    pctaxsch = CASE location_code " . implode("\n", $case_pctaxsch) . " END, 
                    country_code = CASE location_code " . implode("\n", $case_country_code) . " END, 
                    status = CASE location_code " . implode("\n", $case_status) . " END, 
                    updated_date = '".$updated_date."',
                    sync_with_gp = '".$sync_with_gp."'
                    WHERE location_code IN (" . implode(',', $update_location_code) . ")";


                    //Logger::log($update_sql); die;
                    if ($this->db->query($update_sql)) {
                        $affected_rows = $this->db->countAffected();
                        $totalRowsAffected += $affected_rows;
                        Logger::log("Total rows updated : " . $affected_rows, "INFO");
                    } else {
                        Logger::log("Failed to update data into mysql database", "ERROR");
                    }
                }
            }

            Logger::log("Total rows affected : " . $totalRowsAffected, "INFO");
        } catch (Exception $e) {
            Logger::log("Error inserting/updating warehouse records: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

    //insert/update item quantities data into webstore
    public function insertItemQuantityToWebstore(array $data): int
    {
        $totalRowsAffected = 0;

        try {
            $this->load->helper('logger');

            $batchSize = 1000;
            $batch_arr = array_chunk($data, $batchSize);
            $current_date_time = date('Y-m-d H:i:s');

            // Fetch existing data from DB
            $existing_data = [];
            $query = $this->db->query("SELECT item_number, location_code, qty_on_hand, qty_allocated, quantity_back_order, quantity_returned, status FROM ".$this->oc_quantity_table);

            foreach ($query->rows as $row) {
                $key = $row['item_number'] . '|' . $row['location_code'];
                $existing_data[$key] = $row;
            }

            foreach ($batch_arr as $chunk) {
                $insertValues = [];
                $updateCases_qty_on_hand = [];
                $updateCases_qty_allocated = [];
                $updateCases_quantity_back_order = [];
                $updateCases_quantity_returned = [];
                $updateCases_status = [];
                $updateKeys = [];

                foreach ($chunk as $p) {
                    $item_number         = $this->db->escape($p['ITEMNMBR']);
                    $location_code       = $this->db->escape($p['LOCNCODE']);
                    $qty_on_hand         = (int)$p['QTYONHND'];
                    $qty_allocated       = (int)$p['ATYALLOC'];
                    $quantity_back_order = (int)$p['QTYBKORD'];
                    $quantity_returned   = (int)$p['QTYRTRND'];
                    $status              = (isset($p['INACTIVE']) && $p['INACTIVE'] == 0) ? '1' : '0';
                    $sync_with_gp        = '1';
                    $created_date = $updated_date = $current_date_time;

                    $key = $item_number . '|' . $location_code;

                    if (!isset($existing_data[$key])) {
                        // New record
                        $insertValues[] = "('".$item_number."', '".$location_code."', '".$qty_on_hand."', '".$qty_allocated."', '".$quantity_back_order."', '".$quantity_returned."', '".$status."', '".$sync_with_gp."', '".$created_date."')";
                    } else {
                        // Check for changes before updating
                        $existing = $existing_data[$key];

                        if ($existing['qty_on_hand'] !== $qty_on_hand || 
                            $existing['qty_allocated'] !== $qty_allocated || 
                            $existing['quantity_back_order'] !== $quantity_back_order || 
                            $existing['quantity_returned'] !== $quantity_returned || 
                            $existing['status'] !== $status) 
                        {
                            $updateKeys[] = "('".$item_number."', '".$location_code."')";
                            $updateCases_qty_on_hand[] = "WHEN item_number = '".$item_number."' AND location_code = '".$location_code."' THEN '".$qty_on_hand."'";
                            $updateCases_qty_allocated[] = "WHEN item_number = '".$item_number."' AND location_code = '".$location_code."' THEN '".$qty_allocated."'";
                            $updateCases_quantity_back_order[] = "WHEN item_number = '".$item_number."' AND location_code = '".$location_code."' THEN '".$quantity_back_order."'";
                            $updateCases_quantity_returned[] = "WHEN item_number = '".$item_number."' AND location_code = '".$location_code."' THEN '".$quantity_returned."'";
                            $updateCases_status[] = "WHEN item_number = '".$item_number."' AND location_code = '".$location_code."' THEN '".$status."'";
                        }
                    }
                }

                // Bulk INSERT
                if (!empty($insertValues)) {
                    $insert_sql = "INSERT INTO ".$this->oc_quantity_table." (item_number, location_code, qty_on_hand, qty_allocated, quantity_back_order, quantity_returned, status, sync_with_gp, created_date) VALUES " . implode(',', $insertValues);

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
                    $update_sql = "UPDATE ".$this->oc_quantity_table." SET 
                        qty_on_hand = CASE " . implode("\n", $updateCases_qty_on_hand) . " ELSE qty_on_hand END,
                        qty_allocated = CASE " . implode("\n", $updateCases_qty_allocated) . " ELSE qty_allocated END,
                        quantity_back_order = CASE " . implode("\n", $updateCases_quantity_back_order) . " ELSE quantity_back_order END, 
                        quantity_returned = CASE " . implode("\n", $updateCases_quantity_returned) . " ELSE quantity_returned END,
                        status = CASE " . implode("\n", $updateCases_status) . " ELSE status END,sync_with_gp = ".$sync_with_gp.", updated_date = '$updated_date'
                        WHERE (item_number, location_code) IN (" . implode(',', $updateKeys) . ")";

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
            Logger::log("Error in inserting/updating item quantity table in webstore database: " . $e->getMessage(), "ERROR");
        }

        return $totalRowsAffected;
    }

}
