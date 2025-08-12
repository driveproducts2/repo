------------------------------ ORIGINAL (LATEST) ---------------------------------------
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
    DECLARE today DATE;
    DECLARE priceGroupID VARCHAR(50);

    SET today = CURDATE();

    -- Step 1: Get base list price
    SELECT opl.item_price INTO listPrice
    FROM oc_price_list opl
    JOIN oc_price_sheet_master opsm ON opl.price_sheet_id = opsm.price_sheet_id
    JOIN oc_customer_price_sheet ocps ON opl.price_sheet_id = ocps.price_sheet_id
    WHERE opl.product_sku = itemNumber
    AND ocps.customer_group_id = 'BASEBOOK'
    AND opsm.end_date >= today
    ORDER BY opsm.start_date DESC
    LIMIT 1;

    -- If no list price found, set finalPrice to 0 and exit
    IF listPrice IS NULL THEN
        SET finalPrice = 0;
    ELSE
        -- Step 2: Get price sheet ID
        SELECT ocps.price_sheet_id INTO priceSheetID
        FROM oc_customer_price_sheet ocps
        JOIN oc_pricebook_to_customer optc ON ocps.customer_group_id = optc.pricebook_id
        WHERE optc.link_code = customerLinkCode
        LIMIT 1;

        -- Step 3: Pre-fetch price group ID
        SELECT price_group_id INTO priceGroupID
        FROM oc_pricegroup_to_item
        WHERE item_number = itemNumber
        LIMIT 1;

        -- Step 4: Get first discount price
        SELECT item_price INTO discountPrice1
        FROM (
            SELECT item_price FROM oc_price_list WHERE product_sku = itemNumber AND price_sheet_id = priceSheetID
            UNION ALL
            SELECT item_price FROM oc_price_list WHERE product_sku = priceGroupID AND price_sheet_id = priceSheetID
        ) AS combined
        LIMIT 1;

        -- Step 5: Apply first discount if available
        IF discountPrice1 IS NOT NULL THEN
            SET pcntList1 = listPrice * discountPrice1 / 100;
            SET discount1 = listPrice - pcntList1;
            SET listPrice = listPrice - discount1;
        END IF;

        -- Step 6: Get second discount price
        SELECT 
            opl.item_price  INTO discountPrice2
        FROM    
            ( SELECT price_sheet_id FROM oc_price_sheet_master opsm WHERE opsm.end_date >= today ORDER BY opsm.start_date DESC LIMIT 1 ) AS filtered_opsm 
        JOIN 
            oc_customer_price_sheet ocps ON ocps.price_sheet_id = filtered_opsm.price_sheet_id 
        JOIN 
            oc_price_list opl ON opl.price_sheet_id = ocps.price_sheet_id 
        WHERE 
            opl.product_sku IN (itemNumber, priceGroupID) AND ocps.customer_group_id = customerLinkCode 
        LIMIT 1;

        -- Step 7: Apply second discount if found
        IF discountPrice2 IS NOT NULL THEN
            SET pcntList2 = listPrice * discountPrice2 / 100;
            SET discount2 = listPrice - pcntList2;
            SET listPrice = listPrice - discount2;
        END IF;

        -- Set final price
        SET finalPrice = listPrice;
    END IF;
END //

DELIMITER ;



----------------------------- DATE 17-03-2025 ---------------------------------
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
    DECLARE today DATE;
    DECLARE priceGroupID VARCHAR(50);

    SET today = CURDATE();

    -- Step 1: Get base list price
    SELECT opl.item_price INTO listPrice
    FROM oc_price_list opl
    JOIN oc_price_sheet_master opsm ON opl.price_sheet_id = opsm.price_sheet_id
    JOIN oc_customer_price_sheet ocps ON opl.price_sheet_id = ocps.price_sheet_id
    WHERE opl.product_sku = itemNumber
    AND ocps.customer_group_id = 'BASEBOOK'
    AND opsm.end_date >= today
    ORDER BY opsm.start_date DESC
    LIMIT 1;

    -- If no list price found, set finalPrice to 0 and exit
    IF listPrice IS NULL THEN
        SET finalPrice = 0;
    ELSE
        -- Step 2: Get price sheet ID
        SELECT ocps.price_sheet_id INTO priceSheetID
        FROM oc_customer_price_sheet ocps
        JOIN oc_pricebook_to_customer optc ON ocps.customer_group_id = optc.pricebook_id
        WHERE optc.link_code = customerLinkCode
        LIMIT 1;

        -- Step 3: Pre-fetch price group ID
        SELECT price_group_id INTO priceGroupID
        FROM oc_pricegroup_to_item
        WHERE item_number = itemNumber
        LIMIT 1;

        -- Step 4: Get first discount price
        SELECT item_price INTO discountPrice1
        FROM (
            SELECT item_price FROM oc_price_list WHERE product_sku = itemNumber AND price_sheet_id = priceSheetID
            UNION ALL
            SELECT item_price FROM oc_price_list WHERE product_sku = priceGroupID AND price_sheet_id = priceSheetID
        ) AS combined
        LIMIT 1;

        -- Step 5: Apply first discount if available
        IF discountPrice1 IS NOT NULL THEN
            SET pcntList1 = listPrice * discountPrice1 / 100;
            SET discount1 = listPrice - pcntList1;
            SET listPrice = listPrice - discount1;
        END IF;

        -- Step 6: Get second discount price
        SELECT item_price INTO discountPrice2
        FROM oc_price_list opl
        JOIN oc_customer_price_sheet ocps ON opl.price_sheet_id = ocps.price_sheet_id
        JOIN oc_price_sheet_master opsm ON opl.price_sheet_id = opsm.price_sheet_id
        WHERE (opl.product_sku = itemNumber OR opl.product_sku = priceGroupID)
        AND ocps.customer_group_id = customerLinkCode
        AND opsm.end_date >= today
        ORDER BY opsm.start_date DESC
        LIMIT 1;

        -- Step 7: Apply second discount if found
        IF discountPrice2 IS NOT NULL THEN
            SET pcntList2 = listPrice * discountPrice2 / 100;
            SET discount2 = listPrice - pcntList2;
            SET listPrice = listPrice - discount2;
        END IF;

        -- Set final price
        SET finalPrice = listPrice;
    END IF;
END //

DELIMITER ;

-- SP to fetch base price of a product
----------------------------- DATE 17-04-2025 ---------------------------------

DROP
PROCEDURE IF EXISTS CalculateItemBasePrice;
DELIMITER
    //
CREATE PROCEDURE CalculateItemBasePrice(
    IN itemNumber VARCHAR(50),
    OUT finalPrice DECIMAL(18, 2)
)
BEGIN
    DECLARE
        listPrice DECIMAL(18, 5); DECLARE today DATE;
    SET
        today = CURDATE() ;
        -- Step 1: Get base list price
    SELECT
        opl.item_price
    INTO listPrice
FROM
    oc_price_list opl
JOIN oc_price_sheet_master opsm ON
    opl.price_sheet_id = opsm.price_sheet_id
JOIN oc_customer_price_sheet ocps ON
    opl.price_sheet_id = ocps.price_sheet_id
WHERE
    opl.product_sku = itemNumber AND ocps.customer_group_id = 'BASEBOOK' AND opsm.end_date >= today
ORDER BY
    opsm.start_date
DESC
LIMIT 1 ;

SET finalPrice = listPrice;
        END //
    DELIMITER
        ;
