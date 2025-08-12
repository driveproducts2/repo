DROP PROCEDURE IF EXISTS CalculateItemPrice;
DELIMITER //

CREATE PROCEDURE CalculateItemPrice(
    IN itemNumber VARCHAR(50), 
    IN customerLinkCode VARCHAR(50), 
    OUT finalPrice DECIMAL(18,2)
)
BEGIN
    DECLARE listPrice DECIMAL(18,5);
    DECLARE discountPrice1 DECIMAL(18,5);
    DECLARE discountPrice2 DECIMAL(18,5);
    DECLARE priceSheetID VARCHAR(50);
    DECLARE pcntList1 DECIMAL(18,5);
    DECLARE discount1 DECIMAL(18,5);
    DECLARE pcntList2 DECIMAL(18,5);
    DECLARE discount2 DECIMAL(18,5);

    -- Step 1: Get the base list price of the item
    SELECT opl.item_price INTO listPrice
    FROM oc_price_list opl
    JOIN oc_price_sheet_master opsm ON opl.price_sheet_id = opsm.price_sheet_id
    WHERE opl.product_sku = itemNumber
    AND opl.price_sheet_id IN (
        SELECT price_sheet_id FROM oc_customer_price_sheet WHERE customer_group_id = 'BASEBOOK'
    )
    AND opsm.end_date >= CURDATE()
    ORDER BY opsm.start_date DESC
    LIMIT 1;

    -- If no record found, set final price to 0
    IF listPrice IS NULL THEN
        SET finalPrice = 0;
    ELSE
        -- Step 2: Get the price sheet ID for the customer's link code
        SELECT price_sheet_id INTO priceSheetID
        FROM oc_customer_price_sheet
        WHERE customer_group_id = (
            SELECT pricebook_id FROM oc_pricebook_to_customer WHERE link_code = customerLinkCode
            LIMIT 1
        );

        -- Step 3 & 4: Get the first discount price
        SELECT item_price INTO discountPrice1
        FROM oc_price_list
        WHERE (product_sku = itemNumber OR product_sku = (
            SELECT price_group_id FROM oc_pricegroup_to_item WHERE item_number = itemNumber LIMIT 1
        ))
        AND price_sheet_id = priceSheetID
        LIMIT 1;

        -- Step 5: Apply first discount if available
        IF discountPrice1 IS NOT NULL THEN
            SET pcntList1 = listPrice * discountPrice1 / 100;
            SET discount1 = listPrice - pcntList1;
            SET listPrice = listPrice - discount1;
        END IF;

        -- Step 6: Get the second discount price if available
        SELECT item_price INTO discountPrice2
        FROM oc_price_list
        WHERE (product_sku = itemNumber OR product_sku = (
            SELECT price_group_id FROM oc_pricegroup_to_item WHERE item_number = itemNumber LIMIT 1
        ))
        AND price_sheet_id IN (
            SELECT price_sheet_id FROM oc_customer_price_sheet WHERE customer_group_id = customerLinkCode
        )
        AND price_sheet_id IN (
            SELECT price_sheet_id FROM oc_price_sheet_master WHERE end_date >= CURDATE()
        )
        LIMIT 1;

        -- Step 7: Apply second discount if found
        IF discountPrice2 IS NOT NULL THEN
            SET pcntList2 = listPrice * discountPrice2 / 100;
            SET discount2 = listPrice - pcntList2;
            SET listPrice = listPrice - discount2;
        END IF;

        -- Set the final price
        SET finalPrice = listPrice;
    END IF;
END //

DELIMITER ;
