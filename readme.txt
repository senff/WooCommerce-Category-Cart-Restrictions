=== Category Cart Restrictions for WooCommerce ===
Contributors: senff
Donate link: http://donate.senff.com
Tags: categories, cart, restrictions, restrict
Plugin URI: https://wordpress.org/plugins/stepselect-for-woocommerce
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html


Prevent customers from mixing products from conflicting categories in their cart.


== Description ==

Category Cart Restrictions for WooCommerce lets you define pairs of product categories whose items can never appear in the cart at the same time. The restriction is bidirectional: if a product from Category A is in the cart, products from Category B cannot be added, and vice versa.


== Features: ==

* Define unlimited category restriction pairs
* Fully bidirectional — one rule covers both directions
* Supports category hierarchies (subcategories inherit parent restrictions)
* Disabled add-to-cart buttons on shop and product pages with a clear explanation
* Option to show the explanation (for why a product can't be added) with a clear message, or a tooltip


== Installation ==

= Installation from within WordPress =

1. Visit **Plugins > Add New**.
2. Search for **Category Cart Restrictions**.
3. Install and activate the Category Cart Restrictions plugin.
4. Go to **WooCommerce → Settings → Category Cart Restrictions** to start setting up restrictions.

= Manual installation =

1. Upload the entire `category-cart-restrictions-for-woocommerce` folder to the `/wp-content/plugins/` directory.
2. Visit **Plugins**.
3. Activate the Category Cart Restrictions plugin.
4. Go to **WooCommerce → Settings → Category Cart Restrictions** to start setting up restrictions.


== Frequently Asked Questions ==

= What does it do exactly? =
Sometimes you do not want customers to purchase products from specific different categories at the same time. For example, they can buy something from the TOYS category, and they can buy something from the CLOTHING category, but not at the same time. So once they have a product from the TOYS category in the cart, they can't add something from the CLOTHING category, or vice versa. This plugin lets you do that and restricts products from certain categories to be in the cart together.

= Why? =
Maybe products from Category A are shipped from one warehouse, and products from Category B are shipped from another warehouse, and you want every order to contain products from the same warehouse.  Or maybe products from Category C have free shipping, and products from Category D require shipping costs and having those products in the same order complicates your shipping. There's a few situations where this will be useful!

= When I select a category, all subcategories are included as well? =
Yes, since this is the most common scenario. Imagine you have a category APPAREL that has 3 subcategories (MEN, WOMEN, KIDS) and you don't want customers to order any products from the TOYS category.

= I want people to be able to order TOYS and APPAREL → WOMEN together, but not any -other- subcategories from APPAREL (MEN and KIDS). =
Don't create a restriction between TOYS and APPAREL. Instead, you would need to set up 2 restrictions:
- TOYS and APPAREL → MEN
- TOYS and APPAREL → KIDS
With this setup, TOYS can be purchased together with products from APPAREL → WOMEN, but TOYS can not be purchased with products from any other subcategory in APPAREL.
 
= I need more help please! =
If you're not sure how to use this, or you're running into any issues with it, post a message on the plugin's [WordPress.org support forum](https://wordpress.org/support/plugin/category-cart-restrictions-for-woocommerce).

= I've noticed that something doesn't work right, or I have an idea for improvement. How can I report this? =
Category Cart Restrictions's community support forum at https://wordpress.org/support/plugin/category-cart-restrictions-for-woocommerce would a good place, though if you want to add all sorts of -technical- details, it's best to report it on the plugin's Github page at https://github.com/senff/WooCommerce-Category-Cart-Restrictions/issues . This is also where I consider code contributions.

= My question isn't listed here? =
Please go to the plugin's community support forum at https://wordpress.org/support/plugin/category-cart-restrictions-for-woocommerce and post a message. Note that support is provided on a voluntary basis and that it can be difficult to troubleshoot, and may require access to your admin area. Needless to say, NEVER include any passwords of your site on a public forum!


== Screenshots ==

1. Settings screen
2. Product can not be added (shop page)
3. Product can not be added (product page)


== Changelog ==

= 1.0 =
* Initial release.


== Upgrade Notice ==

= 1.0 =
Initial release of the plugin.