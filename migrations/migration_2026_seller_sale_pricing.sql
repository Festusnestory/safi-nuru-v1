-- Adds the Plot & Plan / Existing House pricing breakdown fields to both
-- places a seller property's price is recorded: seller_properties (the
-- generated property row, for both Individual Seller and Property Developer
-- submissions) and seller_development_house_types (the per-development House
-- Type template a developer fills in, which seller_properties rows are
-- derived from). `selling_price` on both tables continues to serve as the
-- computed "Total Selling Price" - no new total column, per spec.

ALTER TABLE seller_properties
    ADD COLUMN sale_pricing_type ENUM('plot_and_plan','existing_house') NULL
        COMMENT 'Drives which pricing breakdown fields apply; NULL for rows submitted before this feature existed'
        AFTER land_type,
    ADD COLUMN plot_selling_price DECIMAL(15,2) NULL COMMENT 'Plot & Plan only' AFTER sale_pricing_type,
    ADD COLUMN construction_amount DECIMAL(15,2) NULL COMMENT 'Plot & Plan only' AFTER plot_selling_price,
    ADD COLUMN property_selling_price DECIMAL(15,2) NULL COMMENT 'Existing House only' AFTER construction_amount,
    ADD COLUMN agent_commission_fees DECIMAL(15,2) NULL AFTER property_selling_price,
    ADD COLUMN other_fees DECIMAL(15,2) NULL COMMENT 'Property Developer path only' AFTER agent_commission_fees;

ALTER TABLE seller_development_house_types
    ADD COLUMN sale_pricing_type ENUM('plot_and_plan','existing_house') NULL AFTER land_type,
    ADD COLUMN plot_selling_price DECIMAL(15,2) NULL COMMENT 'Plot & Plan only' AFTER sale_pricing_type,
    ADD COLUMN construction_amount DECIMAL(15,2) NULL COMMENT 'Plot & Plan only' AFTER plot_selling_price,
    ADD COLUMN property_selling_price DECIMAL(15,2) NULL COMMENT 'Existing House only' AFTER construction_amount,
    ADD COLUMN agent_commission_fees DECIMAL(15,2) NULL AFTER property_selling_price,
    ADD COLUMN other_fees DECIMAL(15,2) NULL AFTER agent_commission_fees;
