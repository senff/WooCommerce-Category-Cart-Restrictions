# Category Cart Restrictions for WooCommerce

* Contributors: senff
* Donate link: http://donate.senff.com
* Tags: categories, cart, restrictions, restrict
* Plugin URI: https://wordpress.org/plugins/stepselect-for-woocommerce
* Requires at least: 6.0
* Tested up to: 6.9
* Requires PHP: 7.4
* Stable tag: 1.0
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Requires PHP: 7.4

A WooCommerce plugin that prevents customers from adding products from certain categories when products from another specific category are already in the cart.

---

## Description

Category Cart Restrictions for WooCommerce lets you define pairs of product categories whose items can never appear in the cart at the same time. The restriction is bidirectional: if a product from Category A is in the cart, products from Category B cannot be added, and vice versa.

**Features:**

- Define unlimited category restriction pairs
- Fully bidirectional — one rule covers both directions
- Supports category hierarchies (subcategories inherit parent restrictions)
- Disabled add-to-cart buttons on shop and product pages with a clear explanation
- Option to show the explanation (for why a product can't be added) with a clear message, or a tooltip

---

## Installation

1. Upload the `category-cart-restrictions-for-woocommerce` directory to your `wp-content/plugins` directory.
2. In your WordPress admin, go to **Plugins** and activate **Category Cart Restrictions for WooCommerce**.
3. Go to **WooCommerce → Settings → Category Cart Restrictions** to configure restriction rules.

---

## Frequently Asked Questions

**What does it do exactly?**

Sometimes you do not want customers to purchase products from specific different categories at the same time. For example, they can buy something from the TOYS category, and they can buy something from the CLOTHING category, but not at the same time. So once they have a product from the TOYS category in the cart, they can't add something from the CLOTHING category, or vice versa. This plugin lets you do that and restricts products from certain categories to be in the cart together.

**Why?**

Maybe products from Category A are shipped from one warehouse, and products from Category B are shipped from another warehouse, and you want every order to contain products from the same warehouse. Or maybe products from Category C have free shipping, and products from Category D require shipping costs and having those products in the same order complicates your shipping. There are a few situations where this will be useful!

**When I select a category, all subcategories are included as well?**

Yes, since this is the most common scenario. Imagine you have a category APPAREL that has 3 subcategories (MEN, WOMEN, KIDS) and you don't want customers to order any products from the TOYS category.

**But I want people to be able to order TOYS and APPAREL → WOMEN together, but not any other subcategories from APPAREL (MEN and KIDS).**

You would need to set up 2 restrictions:
- TOYS and APPAREL → MEN
- TOYS and APPAREL → KIDS

With this setup, TOYS can be purchased together with products from APPAREL → WOMEN, but TOYS cannot be purchased with products from any other subcategory in APPAREL.

**I need more help please!**

If you're not sure how to use this, or you're running into any issues, post a message on the plugin's [WordPress.org support forum](https://wordpress.org/support/plugin/category-cart-restrictions-for-woocommerce).

**I've noticed something doesn't work right, or I have an idea for improvement. How can I report this?**

The [WordPress.org support forum](https://wordpress.org/support/plugin/category-cart-restrictions-for-woocommerce) is a good place, though for technical details it's best to report on the plugin's [GitHub issues page](https://github.com/senff/WooCommerce-Category-Cart-Restrictions/issues). This is also where code contributions are considered.

**My question isn't listed here?**

Please post a message on the plugin's [community support forum](https://wordpress.org/support/plugin/category-cart-restrictions-for-woocommerce). Note that support is provided on a voluntary basis. Never include any passwords of your site on a public forum!

---

## Screenshots

1. Settings screen
2. Product can not be added (shop page)
3. Product can not be added (product page)

---

## Changelog

### 1.0
- Initial release.

---

## Upgrade Notice

### 1.0
- Initial release of the plugin.
