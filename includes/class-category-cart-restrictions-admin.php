<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Category_Cart_Restrictions_Admin {

	const OPTION_KEY    = 'category_cart_restrictions_rules';
	const TAB_ID        = 'category_cart_restrictions';
	const STYLE_OPTION  = 'category_cart_restrictions_display_style';

	public function __construct() {
		// Register as a WooCommerce settings tab.
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_' . self::TAB_ID, array( $this, 'render_settings_tab' ) );

		// Handle our form submissions before any output.
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );

		// Add Settings link on the Plugins page.
		add_filter( 'plugin_action_links_' . CATEGORY_CART_RESTRICTIONS_BASENAME, array( $this, 'add_plugin_action_links' ) );

		// Remove rules that reference a category when it is deleted.
		add_action( 'delete_term', array( $this, 'remove_rules_for_deleted_term' ), 10, 3 );

		// Remove rules that become hierarchically invalid when a category is moved.
		add_action( 'edited_term', array( $this, 'remove_invalid_rules_after_term_update' ), 10, 3 );
	}

	public function remove_invalid_rules_after_term_update( $term_id, $tt_id, $taxonomy ) {
		if ( 'product_cat' !== $taxonomy ) {
			return;
		}

		$rules    = self::get_rules();
		$filtered = array_values( array_filter( $rules, function ( $rule ) {
			$trigger    = (int) $rule['trigger'];
			$restricted = (int) $rule['restricted'];

			$trigger_ancestors    = get_ancestors( $trigger, 'product_cat' );
			$restricted_ancestors = get_ancestors( $restricted, 'product_cat' );

			// Remove the rule if either category is now an ancestor of the other.
			if ( in_array( $restricted, $trigger_ancestors, true ) ) {
				return false;
			}
			if ( in_array( $trigger, $restricted_ancestors, true ) ) {
				return false;
			}

			return true;
		} ) );

		if ( count( $filtered ) !== count( $rules ) ) {
			update_option( self::OPTION_KEY, $filtered );
		}
	}

	public function remove_rules_for_deleted_term( $term_id, $tt_id, $taxonomy ) {
		if ( 'product_cat' !== $taxonomy ) {
			return;
		}

		$rules    = self::get_rules();
		$filtered = array_values( array_filter( $rules, function ( $rule ) use ( $term_id ) {
			return (int) $rule['trigger'] !== (int) $term_id
				&& (int) $rule['restricted'] !== (int) $term_id;
		} ) );

		if ( count( $filtered ) !== count( $rules ) ) {
			update_option( self::OPTION_KEY, $filtered );
		}
	}

	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB_ID ) ),
			esc_html__( 'Settings', 'category-cart-restrictions-for-woocommerce' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function add_settings_tab( $tabs ) {
		$tabs[ self::TAB_ID ] = __( 'Category Cart Restrictions', 'category-cart-restrictions-for-woocommerce' );
		return $tabs;
	}

	public function handle_form_submission() {
		$is_add        = isset( $_POST['category_cart_restrictions_action'] ) && 'add_rule' === $_POST['category_cart_restrictions_action'];
		$is_delete     = isset( $_POST['category_cart_restrictions_delete_index'] );
		$is_save_style = isset( $_POST['category_cart_restrictions_action'] ) && 'save_display_style' === $_POST['category_cart_restrictions_action'];

		if ( ! $is_add && ! $is_delete && ! $is_save_style ) {
			return;
		}
		if ( ! check_admin_referer( 'category_cart_restrictions_manage_rules', '_category_cart_restrictions_nonce' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$rules       = self::get_rules();
		$category_cart_restrictions_message = '';

		if ( $is_add ) {
			$trigger    = isset( $_POST['trigger_category'] ) ? absint( $_POST['trigger_category'] ) : 0;
			$restricted = isset( $_POST['restricted_category'] ) ? absint( $_POST['restricted_category'] ) : 0;

			if ( $trigger && $restricted && $trigger !== $restricted ) {
				$already_exists = false;
				foreach ( $rules as $rule ) {
					$a = (int) $rule['trigger'];
					$b = (int) $rule['restricted'];
					if ( ( $a === $trigger && $b === $restricted ) || ( $a === $restricted && $b === $trigger ) ) {
						$already_exists = true;
						break;
					}
				}
				if ( ! $already_exists ) {
					$rules[] = array(
						'trigger'    => $trigger,
						'restricted' => $restricted,
					);
					update_option( self::OPTION_KEY, $rules );
					$category_cart_restrictions_message = 'added';
				}
			}
		}

		if ( $is_save_style ) {
			$style = isset( $_POST['category_cart_restrictions_display_style'] ) && 'tooltip' === $_POST['category_cart_restrictions_display_style'] ? 'tooltip' : 'inline';
			update_option( self::STYLE_OPTION, $style );
			$category_cart_restrictions_message = 'display_saved';
		}

		if ( $is_delete ) {
			$index = absint( $_POST['category_cart_restrictions_delete_index'] );
			if ( isset( $rules[ $index ] ) ) {
				array_splice( $rules, $index, 1 );
				update_option( self::OPTION_KEY, $rules );
				$category_cart_restrictions_message = 'deleted';
			}
		}

		$redirect = admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB_ID );
		if ( $category_cart_restrictions_message ) {
			$redirect = add_query_arg( 'category_cart_restrictions_message', $category_cart_restrictions_message, $redirect );
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function get_display_style() {
		$style = get_option( self::STYLE_OPTION, 'inline' );
		return 'tooltip' === $style ? 'tooltip' : 'inline';
	}

	public static function get_rules() {
		$raw = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		// Discard any rule that is missing expected keys or contains non-integer values.
		return array_values(
			array_filter( $raw, function ( $rule ) {
				return isset( $rule['trigger'], $rule['restricted'] )
					&& is_numeric( $rule['trigger'] )
					&& is_numeric( $rule['restricted'] )
					&& (int) $rule['trigger'] > 0
					&& (int) $rule['restricted'] > 0;
			} )
		);
	}

	/**
	 * Build a human-readable label for a category including its ancestor path.
	 * E.g. "Games → Toys" instead of just "Toys". Used in the rules table.
	 *
	 * @param int $term_id
	 * @return string
	 */
	public static function get_category_label( $term_id ) {
		$term = get_term( (int) $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return __( '(Unknown category)', 'category-cart-restrictions-for-woocommerce' );
		}

		$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
		$parts     = array();

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			if ( $ancestor && ! is_wp_error( $ancestor ) ) {
				$parts[] = $ancestor->name;
			}
		}
		$parts[] = $term->name;

		return implode( ' → ', $parts );
	}

	/**
	 * Return all product categories as term_id => label for use in <select> elements.
	 * Categories are ordered hierarchically (depth-first, alphabetical within each level).
	 * Subcategories are indented with non-breaking spaces and a bullet per depth level.
	 *
	 * @return array  term_id => display label
	 */
	private function get_categories_for_select() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		// Group terms by parent ID.
		$by_parent = array();
		foreach ( $terms as $term ) {
			$by_parent[ (int) $term->parent ][] = $term;
		}

		// Sort each group alphabetically.
		foreach ( $by_parent as &$group ) {
			usort( $group, function ( $a, $b ) {
				return strcmp( $a->name, $b->name );
			} );
		}
		unset( $group );

		$options = array();
		$nbsp    = "\xc2\xa0"; // UTF-8 non-breaking space
		$bullet  = "\xe2\x80\xa2"; // UTF-8 bullet •

		// Walk depth-first and build indented labels.
		$walk = function ( $parent_id, $depth ) use ( &$walk, &$options, &$by_parent, $nbsp, $bullet ) {
			if ( empty( $by_parent[ $parent_id ] ) ) {
				return;
			}
			foreach ( $by_parent[ $parent_id ] as $term ) {
				if ( $depth === 0 ) {
					$label = $term->name;
				} else {
					$label = str_repeat( $nbsp . $nbsp, $depth ) . $bullet . ' ' . $term->name;
				}
				$options[ $term->term_id ] = $label;
				$walk( $term->term_id, $depth + 1 );
			}
		};

		$walk( 0, 0 );

		return $options;
	}

	public function render_settings_tab() {
		$GLOBALS['hide_save_button'] = true; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce-defined global key.

		$rules         = self::get_rules();
		$categories    = $this->get_categories_for_select();
		$display_style = self::get_display_style();

		// Build a term_id => parent_id map to pass to JS for descendant detection.
		$all_terms  = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		$parent_map = array();
		if ( ! is_wp_error( $all_terms ) ) {
			foreach ( $all_terms as $term ) {
				$parent_map[ (int) $term->term_id ] = (int) $term->parent;
			}
		}
		?>
		<?php wp_nonce_field( 'category_cart_restrictions_manage_rules', '_category_cart_restrictions_nonce' ); ?>

		<?php
		$category_cart_restrictions_messages = array(
			'added'        => __( 'Restriction added.', 'category-cart-restrictions-for-woocommerce' ),
			'deleted'      => __( 'Restriction deleted.', 'category-cart-restrictions-for-woocommerce' ),
			'display_saved' => __( 'Display settings saved.', 'category-cart-restrictions-for-woocommerce' ),
		);
		$category_cart_restrictions_message_key = isset( $_GET['category_cart_restrictions_message'] ) ? sanitize_key( $_GET['category_cart_restrictions_message'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of our own redirect parameter.
		if ( $category_cart_restrictions_message_key && isset( $category_cart_restrictions_messages[ $category_cart_restrictions_message_key ] ) ) :
		?>
		<div class="notice notice-success inline">
			<p><?php echo esc_html( $category_cart_restrictions_messages[ $category_cart_restrictions_message_key ] ); ?></p>
		</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Category Cart Restrictions for WooCommerce', 'category-cart-restrictions-for-woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Each rule defines two categories whose products can never be in the cart at the same time. The restriction works in both directions: if a product from Category A is in the cart, products from Category B cannot be added, and vice versa.', 'category-cart-restrictions-for-woocommerce' ); ?> <?php esc_html_e( 'Note: when selecting a category, it will also automatically include all subcategories under it.', 'category-cart-restrictions-for-woocommerce' ); ?></p>

		<h3><?php esc_html_e( 'Add New Restriction', 'category-cart-restrictions-for-woocommerce' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Category A:', 'category-cart-restrictions-for-woocommerce' ); ?></th>
				<td>
					<select name="trigger_category">
						<option value=""><?php esc_html_e( '— Select category —', 'category-cart-restrictions-for-woocommerce' ); ?></option>
						<?php foreach ( $categories as $term_id => $label ) : ?>
							<option value="<?php echo esc_attr( $term_id ); ?>">
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Category B:', 'category-cart-restrictions-for-woocommerce' ); ?></th>
				<td>
					<select name="restricted_category">
						<option value=""><?php esc_html_e( '— Select category —', 'category-cart-restrictions-for-woocommerce' ); ?></option>
						<?php foreach ( $categories as $term_id => $label ) : ?>
							<option value="<?php echo esc_attr( $term_id ); ?>">
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<p class="description">
			<?php esc_html_e( 'When a product from Category A is in the cart, customers cannot add a product from Category B (and vice versa).', 'category-cart-restrictions-for-woocommerce' ); ?>
			<br>
			<?php esc_html_e( 'Note: restrictions between a parent category and any of its own subcategories are not allowed, since subcategories are already considered part of their parent. For example, if APPAREL has the subcategories MEN, WOMEN and KIDS, a restriction between APPAREL and MEN would be the same as restricting APPAREL from itself.', 'category-cart-restrictions-for-woocommerce' ); ?>
		</p>
		<p class="submit">
			<button type="submit" name="category_cart_restrictions_action" value="add_rule" class="button button-primary">
				<?php esc_html_e( 'Add Restriction', 'category-cart-restrictions-for-woocommerce' ); ?>
			</button>
		</p>

		<h3><?php esc_html_e( 'Current Restrictions', 'category-cart-restrictions-for-woocommerce' ); ?></h3>
		<?php if ( empty( $rules ) ) : ?>
			<p><?php esc_html_e( 'No restrictions configured yet.', 'category-cart-restrictions-for-woocommerce' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Category A', 'category-cart-restrictions-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Category B', 'category-cart-restrictions-for-woocommerce' ); ?></th>
						<th style="width:100px;"><?php esc_html_e( 'Actions', 'category-cart-restrictions-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rules as $index => $rule ) : ?>
					<tr>
						<td><?php echo esc_html( self::get_category_label( (int) $rule['trigger'] ) ); ?></td>
						<td><?php echo esc_html( self::get_category_label( (int) $rule['restricted'] ) ); ?></td>
						<td>
							<button
								type="submit"
								name="category_cart_restrictions_delete_index"
								value="<?php echo esc_attr( $index ); ?>"
								class="button button-small"
								onclick="window.onbeforeunload = null; return confirm('<?php echo esc_js( __( 'Delete this rule?', 'category-cart-restrictions-for-woocommerce' ) ); ?>')"
							>
								<?php esc_html_e( 'Delete', 'category-cart-restrictions-for-woocommerce' ); ?>
							</button>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h3><?php esc_html_e( 'Display Settings', 'category-cart-restrictions-for-woocommerce' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Restriction message style:', 'category-cart-restrictions-for-woocommerce' ); ?></th>
				<td>
					<select name="category_cart_restrictions_display_style">
						<option value="inline" <?php selected( $display_style, 'inline' ); ?>>
							<?php esc_html_e( 'Message below button', 'category-cart-restrictions-for-woocommerce' ); ?>
						</option>
						<option value="tooltip" <?php selected( $display_style, 'tooltip' ); ?>>
							<?php esc_html_e( 'Tooltip on hover / tap', 'category-cart-restrictions-for-woocommerce' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'Choose how the restriction notice is shown to customers on product and shop pages.', 'category-cart-restrictions-for-woocommerce' ); ?></p>
				</td>
			</tr>
		</table>
		<p class="submit">
			<button type="submit" name="category_cart_restrictions_action" value="save_display_style" class="button button-primary">
				<?php esc_html_e( 'Save Display Settings', 'category-cart-restrictions-for-woocommerce' ); ?>
			</button>
		</p>

		<script>
		( function () {
			var selectA      = document.querySelector( 'select[name="trigger_category"]' );
			var selectB      = document.querySelector( 'select[name="restricted_category"]' );
			var parentMap    = <?php echo wp_json_encode( $parent_map ); ?>;
			var existingRules = <?php echo wp_json_encode( array_values( $rules ) ); ?>;

			if ( ! selectA || ! selectB ) {
				return;
			}

			// Build a children map (parent_id => [child_ids]) from the parent map.
			var childrenMap = {};
			Object.keys( parentMap ).forEach( function ( termId ) {
				var parentId = parentMap[ termId ];
				if ( ! childrenMap[ parentId ] ) {
					childrenMap[ parentId ] = [];
				}
				childrenMap[ parentId ].push( parseInt( termId, 10 ) );
			} );

			// Recursively collect all descendant term IDs for a given term.
			function getDescendants( termId ) {
				var result   = [];
				var children = childrenMap[ termId ] || [];
				children.forEach( function ( childId ) {
					result.push( childId );
					getDescendants( childId ).forEach( function ( d ) {
						result.push( d );
					} );
				} );
				return result;
			}

			// Collect all ancestor term IDs for a given term.
			function getAncestors( termId ) {
				var ancestors = [];
				var current   = termId;
				while ( parentMap[ current ] ) {
					var parentId = parentMap[ current ];
					if ( ! parentId ) {
						break;
					}
					ancestors.push( parentId );
					current = parentId;
				}
				return ancestors;
			}

			function syncDisabled( source, target ) {
				// Re-enable every option in the target dropdown.
				Array.prototype.forEach.call( target.options, function ( opt ) {
					opt.disabled = false;
				} );

				var val = parseInt( source.value, 10 );
				if ( val ) {
					// Disable the selected category, all its descendants, and all its ancestors.
					var toDisable = [ val ].concat( getDescendants( val ) ).concat( getAncestors( val ) );
					toDisable.forEach( function ( termId ) {
						var match = target.querySelector( 'option[value="' + termId + '"]' );
						if ( match ) {
							match.disabled = true;
							// If target already has this value selected, reset it.
							if ( parseInt( target.value, 10 ) === termId ) {
								target.value = '';
							}
						}
					} );

					// Disable categories that already have a rule paired with the selected category.
					existingRules.forEach( function ( rule ) {
						var ruleA  = parseInt( rule.trigger, 10 );
						var ruleB  = parseInt( rule.restricted, 10 );
						var paired = null;
						if ( ruleA === val ) {
							paired = ruleB;
						} else if ( ruleB === val ) {
							paired = ruleA;
						}
						if ( paired ) {
							var match = target.querySelector( 'option[value="' + paired + '"]' );
							if ( match ) {
								match.disabled = true;
								if ( parseInt( target.value, 10 ) === paired ) {
									target.value = '';
								}
							}
						}
					} );
				}
			}

			selectA.addEventListener( 'change', function () {
				syncDisabled( selectA, selectB );
			} );

			selectB.addEventListener( 'change', function () {
				syncDisabled( selectB, selectA );
			} );

			// Apply on page load in case a value is pre-selected (e.g. after a failed save).
			syncDisabled( selectA, selectB );
			syncDisabled( selectB, selectA );
		} )();
		</script>
		<?php
	}
}
