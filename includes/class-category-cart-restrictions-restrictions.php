<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Category_Cart_Restrictions_Restrictions {

	public function __construct() {
		// Server-side: block adding a restricted product to the cart.
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_cart_addition' ), 10, 2 );

		// Loop / archive pages: replace the add-to-cart button HTML.
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'maybe_disable_loop_button' ), 10, 2 );

		// Single product page: show the restriction message or tooltip notice.
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'maybe_show_single_product_notice' ) );

		// Single product page: add a body class so CSS disables the button on
		// initial page load (before any variation is selected).
		add_filter( 'body_class', array( $this, 'add_restriction_body_class' ) );

		// Frontend stylesheet.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles() {
		if ( is_woocommerce() || is_cart() ) {
			wp_enqueue_style(
				'category-cart-restrictions-frontend',
				CATEGORY_CART_RESTRICTIONS_PLUGIN_URL . 'assets/css/category-cart-restrictions-frontend.css',
				array(),
				CATEGORY_CART_RESTRICTIONS_VERSION
			);
		}
	}

	/** Track whether the tooltip JS has already been enqueued this request. */
	private static $tooltip_js_added = false;

	/**
	 * Register a minimal script handle so wp_add_inline_script() has an anchor.
	 * The handle outputs no src — it is just a container for inline code.
	 */
	private function get_inline_script_handle() {
		$handle = 'category-cart-restrictions-inline';
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script( $handle, '', array(), CATEGORY_CART_RESTRICTIONS_VERSION, true );
		}
		wp_enqueue_script( $handle );
		return $handle;
	}

	/**
	 * Collect all product category IDs present in the current cart,
	 * including ancestor categories.
	 *
	 * @return int[]
	 */
	private function get_cart_category_ids() {
		$category_ids = array();

		if ( ! WC()->cart ) {
			return $category_ids;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			$terms = get_the_terms( (int) $item['product_id'], 'product_cat' );
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}
			foreach ( $terms as $term ) {
				$category_ids[] = (int) $term->term_id;
				foreach ( get_ancestors( $term->term_id, 'product_cat' ) as $ancestor ) {
					$category_ids[] = (int) $ancestor;
				}
			}
		}

		return array_unique( $category_ids );
	}

	/**
	 * Return the category IDs assigned to a product, including ancestors.
	 *
	 * @param int $product_id
	 * @return int[]
	 */
	private function get_product_category_ids( $product_id ) {
		$category_ids = array();
		$terms        = get_the_terms( $product_id, 'product_cat' );

		if ( ! $terms || is_wp_error( $terms ) ) {
			return $category_ids;
		}

		foreach ( $terms as $term ) {
			$category_ids[] = (int) $term->term_id;
			foreach ( get_ancestors( $term->term_id, 'product_cat' ) as $ancestor ) {
				$category_ids[] = (int) $ancestor;
			}
		}

		return array_unique( $category_ids );
	}

	/**
	 * Return the names of trigger categories that are blocking the given product.
	 * Returns an empty array when the product is not restricted.
	 *
	 * @param int $product_id
	 * @return string[]
	 */
	private function get_trigger_category_names( $product_id ) {
		$rules = Category_Cart_Restrictions_Admin::get_rules();
		if ( empty( $rules ) ) {
			return array();
		}

		$cart_categories = $this->get_cart_category_ids();
		if ( empty( $cart_categories ) ) {
			return array();
		}

		$product_categories = $this->get_product_category_ids( $product_id );
		if ( empty( $product_categories ) ) {
			return array();
		}

		$trigger_names = array();

		foreach ( $rules as $rule ) {
			$cat_a = (int) $rule['trigger'];
			$cat_b = (int) $rule['restricted'];

			// Direction A→B: cart contains A, product is in B.
			if ( in_array( $cat_a, $cart_categories, true ) && in_array( $cat_b, $product_categories, true ) ) {
				$trigger_names[] = Category_Cart_Restrictions_Admin::get_category_label( $cat_a );
			}

			// Direction B→A: cart contains B, product is in A.
			if ( in_array( $cat_b, $cart_categories, true ) && in_array( $cat_a, $product_categories, true ) ) {
				$trigger_names[] = Category_Cart_Restrictions_Admin::get_category_label( $cat_b );
			}
		}

		return array_unique( $trigger_names );
	}

	/**
	 * Check whether the given product is blocked by any active rule.
	 *
	 * @param int $product_id
	 * @return bool
	 */
	public function is_product_restricted( $product_id ) {
		return ! empty( $this->get_trigger_category_names( $product_id ) );
	}

	/**
	 * Build the customer-facing restriction message, naming the trigger category/categories.
	 *
	 * @param int $product_id
	 * @return string
	 */
	private function get_restriction_message( $product_id ) {
		$names = $this->get_trigger_category_names( $product_id );

		if ( empty( $names ) ) {
			return '';
		}

		if ( 1 === count( $names ) ) {
			return sprintf(
				/* translators: %s: product category name */
				__( 'This product cannot be added as long as the cart contains any products from the "%s" category (or any of its subcategories).', 'category-cart-restrictions-for-woocommerce' ),
				$names[0]
			);
		}

		$last    = array_pop( $names );
		$all_but = array_map( function ( $n ) { return '"' . $n . '"'; }, $names );

		return sprintf(
			/* translators: 1: comma-separated list of category names, 2: final category name */
			__( 'This product cannot be added as long as the cart contains any products from the %1$s or "%2$s" categories (or any of their subcategories).', 'category-cart-restrictions-for-woocommerce' ),
			implode( ', ', $all_but ),
			$last
		);
	}

	/**
	 * Prevent a restricted product from being added to the cart (server-side).
	 * Shows a WooCommerce error notice at the top of the page.
	 */
	public function validate_cart_addition( $passed, $product_id ) {
		if ( ! $passed ) {
			return $passed;
		}

		$message = $this->get_restriction_message( $product_id );

		if ( $message ) {
			wc_add_notice( wp_strip_all_tags( $message ), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Replace the add-to-cart button with a disabled version on loop/archive pages.
	 */
	public function maybe_disable_loop_button( $html, $product ) {
		$message = $this->get_restriction_message( $product->get_id() );

		if ( ! $message ) {
			return $html;
		}

		$btn = sprintf(
			'<button type="button" class="button add_to_cart_button category-cart-restrictions-disabled-button" disabled aria-disabled="true">%s</button>',
			esc_html__( 'Add to cart', 'category-cart-restrictions-for-woocommerce' )
		);

		if ( 'tooltip' === Category_Cart_Restrictions_Admin::get_display_style() ) {
			$this->maybe_enqueue_tooltip_js();
			return sprintf(
				'<span class="category-cart-restrictions-tooltip-wrap">'
				. '<button type="button" class="button add_to_cart_button category-cart-restrictions-disabled-button" disabled aria-disabled="true">%s</button>'
				. '<button type="button" class="category-cart-restrictions-tooltip-trigger" aria-expanded="false" aria-label="%s">?</button>'
				. '<span class="category-cart-restrictions-tooltip-message" role="tooltip">%s</span>'
				. '</span>',
				esc_html__( 'Add to cart', 'category-cart-restrictions-for-woocommerce' ),
				esc_attr__( 'Why is this disabled?', 'category-cart-restrictions-for-woocommerce' ),
				esc_html( $message )
			);
		}

		return $btn . sprintf( '<p class="category-cart-restrictions-restricted-message">%s</p>', esc_html( $message ) );
	}

	/**
	 * Add a body class on restricted single product pages so CSS can disable
	 * the add-to-cart button on initial page load.
	 */
	public function add_restriction_body_class( $classes ) {
		if ( ! is_product() ) {
			return $classes;
		}
		$product_id = get_queried_object_id();
		if ( $product_id && $this->get_restriction_message( $product_id ) ) {
			$classes[] = 'category-cart-restrictions-product-restricted';
		}
		return $classes;
	}

	/**
	 * On single product pages, show the restriction message or tooltip notice
	 * next to the add-to-cart button. The button itself is NOT disabled here;
	 * server-side validation handles blocking the actual cart addition.
	 */
	public function maybe_show_single_product_notice() {
		global $product;

		if ( ! ( $product instanceof WC_Product ) ) {
			return;
		}

		$message = $this->get_restriction_message( $product->get_id() );

		if ( ! $message ) {
			return;
		}

		if ( 'tooltip' === Category_Cart_Restrictions_Admin::get_display_style() ) {
			// Output the tooltip wrap (without the button). JS moves the button
			// inside the wrap so that hovering it also triggers the tooltip.
			echo sprintf(
				'<span class="category-cart-restrictions-tooltip-wrap category-cart-restrictions-single-tooltip">'
				. '<button type="button" class="category-cart-restrictions-tooltip-trigger" aria-expanded="false" aria-label="%s">?</button>'
				. '<span class="category-cart-restrictions-tooltip-message" role="tooltip">%s</span>'
				. '</span>',
				esc_attr__( 'Why can\'t I add this to the cart?', 'category-cart-restrictions-for-woocommerce' ),
				esc_html( $message )
			);
			?>
			<script>
			( function () {
				var wrap = document.querySelector( ".category-cart-restrictions-single-tooltip" );
				var form = document.querySelector( "form.cart" );
				if ( ! wrap || ! form ) { return; }
				var btn = form.querySelector( ".single_add_to_cart_button, [name='add-to-cart']" );
				if ( btn && btn.parentNode ) {
					// Insert the wrap before the button, then move the button inside it.
					btn.parentNode.insertBefore( wrap, btn );
					wrap.insertBefore( btn, wrap.firstChild );
				}

				// Tooltip tap-toggle for mobile.
				document.addEventListener( "click", function ( e ) {
					var trigger = e.target.closest( ".category-cart-restrictions-tooltip-trigger" );
					if ( trigger ) {
						var expanded = "true" === trigger.getAttribute( "aria-expanded" );
						document.querySelectorAll( ".category-cart-restrictions-tooltip-trigger[aria-expanded=\"true\"]" ).forEach( function ( t ) {
							t.setAttribute( "aria-expanded", "false" );
						} );
						trigger.setAttribute( "aria-expanded", expanded ? "false" : "true" );
						e.stopPropagation();
					} else {
						document.querySelectorAll( ".category-cart-restrictions-tooltip-trigger[aria-expanded=\"true\"]" ).forEach( function ( t ) {
							t.setAttribute( "aria-expanded", "false" );
						} );
					}
				} );
			} )();
			</script>
			<?php
		} else {
			echo '<p class="category-cart-restrictions-restricted-message">' . esc_html( $message ) . '</p>';
		}
	}

	/**
	 * Enqueue the tooltip tap-toggle JS once per page load.
	 */
	private function maybe_enqueue_tooltip_js() {
		if ( self::$tooltip_js_added ) {
			return;
		}
		self::$tooltip_js_added = true;

		wp_add_inline_script(
			$this->get_inline_script_handle(),
			'( function () {
				document.addEventListener( "click", function ( e ) {
					var trigger = e.target.closest( ".category-cart-restrictions-tooltip-trigger" );
					if ( trigger ) {
						var expanded = "true" === trigger.getAttribute( "aria-expanded" );
						document.querySelectorAll( ".category-cart-restrictions-tooltip-trigger[aria-expanded=\'true\']" ).forEach( function ( t ) {
							t.setAttribute( "aria-expanded", "false" );
						} );
						trigger.setAttribute( "aria-expanded", expanded ? "false" : "true" );
						e.stopPropagation();
					} else {
						document.querySelectorAll( ".category-cart-restrictions-tooltip-trigger[aria-expanded=\'true\']" ).forEach( function ( t ) {
							t.setAttribute( "aria-expanded", "false" );
						} );
					}
				} );
			} )();'
		);
	}
}
