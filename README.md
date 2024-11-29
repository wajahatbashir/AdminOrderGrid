# Magento 2 Admin Order Grid Enhancements

This module enhances the Admin Order Create product grid by adding a **Stock** column with custom logic:
- Displays **In Stock**, **Out of Stock**, or **Pre Order** based on product stock and `pre_order_status`.

## Features
- Adds a **Stock** column with custom statuses.
- Uses a `pre_order_status` attribute for additional logic.
- Fully configurable: Enable or disable the module from the Admin panel.

## Installation
1. Place the module in `app/code/WB/AdminOrderGrid`.
2. Run the following commands:
   ```bash
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush

## Configuration
Enable or disable the module from the Admin panel:

1. Navigate to Stores > Configuration > WB Admin Order Grid.
2. Set "Enable Module" to "Yes" or "No".

## Usage
The module automatically integrates into the Admin Order Create product grid. The Stock column appears with the following statuses:

-  In Stock: Quantity > 0.
-  Pre Order: Quantity <= 0 and pre_order_status = "Yes".
-  Out of Stock: Quantity <= 0 and pre_order_status = "No".

## Compatibility
Magento 2.4.x

## License
This module is proprietary. Unauthorized distribution or use is prohibited.