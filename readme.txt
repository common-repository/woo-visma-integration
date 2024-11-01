=== WooCommerce Visma Integration ===

Contributors: Wetail
Plugin Name: WooCommerce Visma Plugin
Plugin URI: wp-plugs.com
Tags: WooCommerce, Order, E-Commerce, Accounting, sync, Visma, Customers, Integration,
Author URI: wetail.com
Author: Wetail
Requires at least: 5.0
Tested up to: 6.6.1
Stable tag: 2.3.4
Version: 2.3.4
License: GPLv2 or later


== Spara tid och pengar med minskad administration ==

Synkronisera kunder, produkter och ordrar från WooCommerce till Visma eEkonomi.


== Description ==

Komplett integration för WooCommerce och Visma eEkonomi.

= Features =
* Bokför ordrar
* Synkroniserar Kunder
* Synkroniserar Ordrar
* Synkroniserar Artiklar
* Mappa olika betalsätt till olika konton
* Bokför ordrar från EU och övriga världen
* Automatisk valutakonvertering till SEK

Kräver WooCommerce


= Installationsguide =

Vänligen följ den länk för [installationsguide](https://docs.wetail.io/woocommerce-visma-integration/visma-installationsguide/)


= Data export till Visma eEkonomi =

* Ordrar
* Artiklar
* Kunder
* Bokföringsdata


== Screenshots ==

1. General

2. Sync settings

3. Shipping settings

4. Product settings

5. Order view

== Changelog ==

= 2.3.4 =
* Bugfix: AccessToken expiry time is lowered to avoid getting invalid grant errors that can lead to customer duplicates due to errorhandling
* Bugfix: Corrected refund process

= 2.3.2 =
* Bugfix: Shipping Address fix
* Bugfix: Default settings are not working

= 2.3.1 =
* Feature: Supports nonexisting products i WooCommerce
* Bugfix: Organisation number fix on Visma Customer object
* Bugfix: Billing addresss is now default on Visma Customer object

= 2.3.0 =
* Feature: Visma invoice PDF is downloadable from order view
* Feature: Setting for not updating customer on order sync
* Feature: Order your reference is set to order billing customer
* Bugfix: Order deliveryname  is set to order billing customer when customer is a company
* Bugfix: Floating point issue is fixed
= 2.2.7 =
* Feature: support for WooCommerce HPOS
= 2.2.6 =
* Bugfix: Corrections on fetching Visma units and Article Account Codings
* Bugfix: Decimals on order row discount percentage is updated to 4 decimals
* Bugfix: UI cache is saved in correct directory
= 2.2.5 =
* Bugfix: Corrections on fetching Visma units and Article Account Codings
* Bugfix: Extended support for Norway
= 2.2.4 =
* Changed endpoints for fetching Visma units and Article Account Codings
= 2.2.3 =
* Bugfix: Currency conversion service changed
= 2.2.2 =
* Bugfixes
= 2.2.1 =
* Feature: support for refunds
= 2.1.8 =
* License check fix
= 2.1.7 =
* Feature: Added support for currencies AUD, CAD
= 2.1.6 =
* Fix: New currency API
= 2.1.5 =
* Feature: Prevents duplicate voucher to be created
* Bugfix: Prevent multiple shipping products from being created
= 2.1.4 =
* Feature: Support for Visma stock module
* Feature: Support for multiple units
* Fix: Removal of guzzle
= 2.1.1 =
* Bugfix: Settings validator showed an error message when all settings were valid.
= 2.1.0 =
* Vat rounding correction 
* Settings validation correction
* Country selection fallback for vouchers
* Meta field exclusion when duplicating a product
= 2.0.5 =
* Fix: License SSL issue fix
= 2.0.4 =
* Fix: for virtual orders
= 2.0.3 =
* Fix: PHP 8.0 compatability
= 2.0.2 =
License check update
= 2.0.1 =
* BUGFIX: free shipping on ordersync

= 2.0 =
* Fix: All ENABLED payment methods appear in the plugin settings now, regardless if they are considered active or not.
* Change: Added migration script for license key setting
* New feature: It is now possible to synchronize orders in bulk.
* New feature: It is now possible to convert synchronized orders to invoices.
* New feature: It is now possible to set invoices as paid.
* Enhancement: The UI for the settings has been reworked.
    - Better descriptions in admin interface.
    - Visualized and described the 2 synchronization modes better.
    - Improved error handling and descriptions.
    - Hide accounting settings depending on sync option.
    - Improved plugin copy.
* Enhancement: Removed Guzzle dependency.
* BUGFIX: The organization number field was marked as mandatory in the backend but not in the frontend.
* BUGFIX: Corrected link in support button.


= 1.33 =
* BUGFIX: Return types void caused a crash

= 1.32 =
* BUGFIX: VAT

= 1.30 =
* Visma Product IDs are now visible and editable in product view
* BUGFIX: Removal of unused action

= 1.21 =
* BUGFIX: Fix VAT for EU fees

= 1.20 =
* FIX: Fix for administrators with multiple companies in e-Ekonomi

= 1.19 =
* BUGFIX: Bugfix for fetching single articles that affected product sync and thus order sync

= 1.15 =
* BUGFIX: Bugfix for custom ordernumbers

= 1.13 =
* BUGFIX: Too many decimals in products

= 1.12 =
* Truncate product name if over 50
* BUGFIX: Too many decimals in vouchers totals
* BUGFIX: Discount calculation

= 1.10 =
* BUGFIX: Too many decimals in vouchers
* BUGFIX: Fee handling


= 1.07 =
* BUGFIX: Too many decimals on NetPrice

= 1.06 =

*	BUGFIX: Discount percentage fix
= 1.04 =

*	BUGFIX: Too many decimals on UnitPrice
= 1.03 =

*	Better support for custom shipping methods

= 1.01 =

*	BUG: Product syncronization fixed when Visma Article ID is missing
*   Fixer API handling updated
