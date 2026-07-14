<?php
/**
 * Archive filters — category chips, price range and product-attribute facets
 * (brand, age, …) on shop and product-taxonomy archives. WooCommerce applies
 * the chosen `filter_*` attributes automatically; price is applied via the
 * product meta-lookup table.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Apply min/max price from the query string to the main shop query.
 *
 * @param array<string,string> $clauses Query clauses.
 * @param WP_Query             $query   Query.
 * @return array<string,string>
 */
function kindi_price_filter_clauses( array $clauses, $query ): array {
	if ( is_admin() || ! $query->is_main_query() ) {
		return $clauses;
	}
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() || is_search() ) ) {
		return $clauses;
	}
	if ( ! isset( $_GET['min_price'] ) && ! isset( $_GET['max_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $clauses;
	}

	global $wpdb;
	$min = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : 0;          // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : PHP_INT_MAX; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$clauses['join']    .= " INNER JOIN {$wpdb->wc_product_meta_lookup} kpl ON {$wpdb->posts}.ID = kpl.product_id ";
	$clauses['where']   .= $wpdb->prepare( ' AND kpl.min_price >= %f AND kpl.max_price <= %f ', $min, $max );
	$clauses['groupby']  = "{$wpdb->posts}.ID";

	return $clauses;
}
add_filter( 'posts_clauses', 'kindi_price_filter_clauses', 20, 2 );

/**
 * Which special shop view is active: 'sale' (/shop/?on_sale=1), 'new'
 * (/shop/?new=1) or '' for the regular archive. Dedicated params — the sort
 * dropdown's orderby never renames the page by accident.
 *
 * @return string
 */
function kindi_special_shop_view(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view flags.
	if ( ! empty( $_GET['on_sale'] ) ) {
		return 'sale';
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_GET['new'] ) ) {
		return 'new';
	}
	return '';
}

/**
 * Special shop views for menu links (e.g. footer "מבצעים" / "מוצרים חדשים"):
 * ?on_sale=1 restricts the archive to on-sale products; ?new=1 orders the
 * archive newest-first. The archive hero titles both views accordingly.
 *
 * @param WP_Query $query Main query.
 * @return void
 */
function kindi_shop_special_views( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$view = kindi_special_shop_view();
	if ( '' === $view ) {
		return;
	}
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}
	if ( 'sale' === $view ) {
		$ids = wc_get_product_ids_on_sale(); // Cached by WooCommerce in a transient.
		$query->set( 'post__in', $ids ? array_map( 'intval', $ids ) : array( 0 ) );
	} else {
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
	}
}
add_action( 'pre_get_posts', 'kindi_shop_special_views', 20 );

/**
 * Sub-categories of the current category (direct children only) — or the
 * top-level categories on the shop. Each entry carries an icon + product count
 * for the hero chips and the sidebar sub-category list. Cached per context.
 *
 * @return array<int,array{name:string,url:string,count:int,icon:string,id:int}>
 */
function kindi_category_chips(): array {
	$term = ( function_exists( 'is_tax' ) && is_tax( 'product_cat' ) ) ? get_queried_object() : null;
	$ctx  = ( $term instanceof WP_Term ) ? 'term_' . $term->term_id : 'shop';
	$ver  = (int) get_option( 'kindi_term_ver', 1 );
	// New cache namespace (v2 structure: name/url/count/icon/id) so any stale
	// pre-update cache is ignored.
	$key  = 'kindi_subcats_v' . $ver . '_' . $ctx;

	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$parent   = $term instanceof WP_Term ? $term->term_id : 0;
	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => $parent,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 0 === $parent ? 14 : 0,
		)
	);

	$out = array();
	if ( ! is_wp_error( $children ) ) {
		foreach ( $children as $child ) {
			$out[] = array(
				'name'  => $child->name,
				'url'   => (string) get_term_link( $child ),
				'count' => (int) $child->count,
				'icon'  => function_exists( 'kindi_guess_category_icon' ) ? kindi_guess_category_icon( $child->name ) : 'grid',
				'id'    => (int) $child->term_id,
			);
		}
	}

	set_transient( $key, $out, 12 * HOUR_IN_SECONDS );

	return $out;
}

/**
 * Attribute facets relevant to the current view, cached per context:
 * - Category: only attributes/terms used by products in that category (incl.
 *   sub-categories), so the facets reflect the real catalogue there.
 * - Shop: all product attributes and their terms.
 *
 * @return array<int,array{attribute_name:string,label:string,taxonomy:string,terms:array<int,array{slug:string,name:string}>}>
 */
function kindi_archive_attribute_terms(): array {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return array();
	}

	$term = ( function_exists( 'is_tax' ) && is_tax( 'product_cat' ) ) ? get_queried_object() : null;
	$ctx  = ( $term instanceof WP_Term ) ? 'term_' . $term->term_id : 'shop';
	$ver  = (int) get_option( 'kindi_term_ver', 1 );
	$key  = 'kindi_attrterms_v' . $ver . '_' . $ctx;

	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$atts = wc_get_attribute_taxonomies();
	$out  = array();

	if ( $term instanceof WP_Term ) {
		// Products in this category (incl. children), capped for safety.
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 800,
				'no_found_rows'  => true,
				'tax_query'      => array(
					array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id, 'include_children' => true ),
				),
			)
		);

		if ( $ids ) {
			$taxonomies = array();
			foreach ( $atts as $att ) {
				$taxonomies[] = wc_attribute_taxonomy_name( $att->attribute_name );
			}
			$obj_terms = wp_get_object_terms( $ids, $taxonomies, array( 'fields' => 'all' ) );

			$by_tax = array();
			if ( ! is_wp_error( $obj_terms ) ) {
				foreach ( $obj_terms as $t ) {
					$by_tax[ $t->taxonomy ][ $t->term_id ] = array( 'slug' => $t->slug, 'name' => $t->name );
				}
			}
			foreach ( $atts as $att ) {
				$tx = wc_attribute_taxonomy_name( $att->attribute_name );
				if ( ! empty( $by_tax[ $tx ] ) ) {
					$out[] = array( 'attribute_name' => $att->attribute_name, 'label' => $att->attribute_label, 'taxonomy' => $tx, 'terms' => array_values( $by_tax[ $tx ] ) );
				}
			}
		}
	} else {
		foreach ( $atts as $att ) {
			$tx    = wc_attribute_taxonomy_name( $att->attribute_name );
			$terms = get_terms( array( 'taxonomy' => $tx, 'hide_empty' => true ) );
			if ( is_wp_error( $terms ) || ! $terms ) {
				continue;
			}
			$list = array();
			foreach ( $terms as $t ) {
				$list[] = array( 'slug' => $t->slug, 'name' => $t->name );
			}
			$out[] = array( 'attribute_name' => $att->attribute_name, 'label' => $att->attribute_label, 'taxonomy' => $tx, 'terms' => $list );
		}
	}

	set_transient( $key, $out, 12 * HOUR_IN_SECONDS );

	return $out;
}

/**
 * Is the current view one of our product archives (shop / category / search)?
 *
 * @return bool
 */
function kindi_is_product_archive(): bool {
	return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || is_search() );
}

/**
 * Archive hero band: breadcrumb, title, description (right) + sub-category chips
 * (left), on a soft full-width background. Replaces WooCommerce's default
 * breadcrumb + products-header (suppressed in kindi_archive_dechrome).
 *
 * @return void
 */
function kindi_archive_hero(): void {
	if ( ! kindi_is_product_archive() ) {
		return;
	}

	$desc = '';
	if ( is_search() ) {
		/* translators: %s: search term. */
		$title = sprintf( __( 'תוצאות חיפוש: %s', 'kindi' ), get_search_query() );
	} elseif ( is_product_taxonomy() ) {
		$term  = get_queried_object();
		$title = $term instanceof WP_Term ? $term->name : wp_strip_all_tags( woocommerce_page_title( false ) );
		$desc  = $term instanceof WP_Term ? wp_strip_all_tags( term_description( $term ) ) : '';
	} else {
		$title = wp_strip_all_tags( woocommerce_page_title( false ) );
	}

	// Special shop views (menu links) get their own title, so the visitor sees
	// a "מבצעים" / "מוצרים חדשים" page — not a plain "חנות" that happens to be
	// filtered/sorted.
	$kindi_view = kindi_special_shop_view();
	if ( 'sale' === $kindi_view ) {
		$title = __( 'מבצעים חמים', 'kindi' );
		$desc  = __( 'כל המוצרים שבמבצע עכשיו — במקום אחד. המלאי מתעדכן, שווה להזדרז!', 'kindi' );
	} elseif ( 'new' === $kindi_view ) {
		$title = __( 'מוצרים חדשים', 'kindi' );
		$desc  = __( 'ההגעות האחרונות לקינדר טויס — הכי חדש, ראשונים אצלכם.', 'kindi' );
	}

	echo '<section class="kindi-archhero"><div class="kindi-archhero__inner">';
	echo '<div class="kindi-archhero__text">';
	if ( function_exists( 'woocommerce_breadcrumb' ) ) {
		woocommerce_breadcrumb( array( 'wrap_before' => '<nav class="kindi-archhero__crumbs">', 'wrap_after' => '</nav>' ) );
	}
	echo '<h1 class="kindi-archhero__title">' . esc_html( $title ) . '</h1>';
	if ( '' !== $desc ) {
		echo '<div class="kindi-archhero__descwrap">';
		echo '<p class="kindi-archhero__desc" data-kindi-clamp>' . esc_html( $desc ) . '</p>';
		echo '<button type="button" class="kindi-archhero__more" data-kindi-clamp-toggle aria-expanded="false" data-more="' . esc_attr__( 'קרא עוד', 'kindi' ) . '" data-less="' . esc_attr__( 'הצג פחות', 'kindi' ) . '" hidden>' . esc_html__( 'קרא עוד', 'kindi' ) . '</button>';
		echo '</div>';
	}
	echo '</div>';

	// Hero chips = the current category's sub-categories only (top categories on
	// the shop). No parent/sibling chips.
	$chips = kindi_category_chips();
	if ( $chips ) {
		echo '<div class="kindi-archhero__chips">';
		foreach ( $chips as $chip ) {
			printf(
				'<a class="kindi-chip" href="%s">%s</a>',
				esc_url( (string) ( $chip['url'] ?? '' ) ),
				esc_html( (string) ( $chip['name'] ?? '' ) )
			);
		}
		echo '</div>';
	}
	echo '</div></section>';
}
add_action( 'woocommerce_before_main_content', 'kindi_archive_hero', 5 );

/**
 * Suppress WooCommerce's default breadcrumb + page title on archives (the hero
 * renders them). Single-product pages keep their breadcrumb.
 *
 * @return void
 */
function kindi_archive_dechrome(): void {
	if ( kindi_is_product_archive() ) {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		add_filter( 'woocommerce_show_page_title', '__return_false' );
	}
}
add_action( 'wp', 'kindi_archive_dechrome' );

/**
 * Open the two-column archive layout: products (main, left) + filters (aside,
 * right). Fires inside the loop area, before the toolbar/products.
 *
 * @return void
 */
function kindi_archive_open(): void {
	if ( ! kindi_is_product_archive() ) {
		return;
	}
	echo '<div class="kindi-archive">';
	echo '<aside class="kindi-archive__side">';
	kindi_archive_sidebar();
	echo '</aside>';
	echo '<div class="kindi-archive__main">';
}
add_action( 'woocommerce_before_shop_loop', 'kindi_archive_open', 1 );

/**
 * Close the two-column layout after the loop + pagination.
 *
 * @return void
 */
function kindi_archive_close(): void {
	if ( ! kindi_is_product_archive() ) {
		return;
	}
	echo '</div></div>';
}
add_action( 'woocommerce_after_shop_loop', 'kindi_archive_close', 999 );

/**
 * Toolbar above the grid: result count (right) + view toggle and sort (left).
 *
 * @return void
 */
function kindi_archive_toolbar(): void {
	if ( ! kindi_is_product_archive() ) {
		return;
	}
	$query = $GLOBALS['wp_query'] ?? null;
	$total = function_exists( 'wc_get_loop_prop' ) ? (int) wc_get_loop_prop( 'total' ) : 0;
	$shown = $query instanceof WP_Query ? (int) $query->post_count : 0;

	echo '<div class="kindi-archive__toolbar">';
	echo '<span class="kindi-archive__count">' . esc_html( sprintf( /* translators: 1: shown, 2: total. */ __( '%1$d מוצרים מתוך %2$d', 'kindi' ), $shown, $total ) ) . '</span>';
	echo '<div class="kindi-archive__tools">';
	// Mobile-only "filter" button — opens the sidebar as a drawer.
	echo '<button type="button" class="kindi-archive__filterbtn" data-kindi-filters-open>' . kindi_icon( 'filter', 'kindi-icon--sm' ) . esc_html__( 'סינון', 'kindi' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="kindi-view" data-kindi-view>'
		. '<button type="button" class="kindi-view__btn" data-view="list" aria-label="' . esc_attr__( 'תצוגת רשימה', 'kindi' ) . '">' . kindi_icon( 'menu', 'kindi-icon--sm' ) . '</button>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '<button type="button" class="kindi-view__btn is-active" data-view="grid" aria-label="' . esc_attr__( 'תצוגת קוביות', 'kindi' ) . '">' . kindi_icon( 'grid', 'kindi-icon--sm' ) . '</button>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '</div>';
	woocommerce_catalog_ordering();
	echo '</div>';
	echo '</div>';
}
add_action( 'woocommerce_before_shop_loop', 'kindi_archive_toolbar', 20 );

/**
 * Move the default result count + ordering out of the loop (the toolbar renders
 * them), and render the filter sidebar content (facets + price + clear).
 *
 * @return void
 */
function kindi_archive_dechrome_loop(): void {
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
}
add_action( 'init', 'kindi_archive_dechrome_loop' );

/**
 * The filter sidebar: attribute facets, price range and a "clear" link.
 *
 * @return void
 */
function kindi_archive_sidebar(): void {
	echo '<div class="kindi-side__head">' . kindi_icon( 'grid', 'kindi-icon--sm' ) . '<span>' . esc_html__( 'סינון וחיפוש', 'kindi' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<button type="button" class="kindi-side__close" data-kindi-filters-close aria-label="' . esc_attr__( 'סגירה', 'kindi' ) . '">' . kindi_icon( 'close', 'kindi-icon--md' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';

	// "Clear filters" at the TOP — visible without scrolling the facets.
	$reset_url = kindi_archive_reset_url();
	if ( '' !== $reset_url ) {
		printf(
			'<a class="kindi-chip kindi-chip--reset" href="%s">%s%s</a>',
			esc_url( $reset_url ),
			kindi_icon( 'close', 'kindi-icon--xs' ), // phpcs:ignore WordPress.Security.EscapeOutput
			esc_html__( 'ביטול סינון', 'kindi' )
		);
	}

	// Sub-categories of the current category (icon + name + count) — open.
	$subcats = kindi_category_chips();
	if ( $subcats ) {
		echo '<details class="kindi-side__group" open><summary>' . esc_html__( 'תתי קטגוריה', 'kindi' ) . '</summary><div class="kindi-side__cats">';
		foreach ( $subcats as $c ) {
			printf(
				'<a class="kindi-side__cat" href="%s"><span class="kindi-side__cat-name">%s</span><span class="kindi-side__cat-count">(%d)</span></a>',
				esc_url( (string) ( $c['url'] ?? '' ) ),
				esc_html( (string) ( $c['name'] ?? '' ) ),
				(int) ( $c['count'] ?? 0 )
			);
		}
		echo '</div></details>';
	}

	// Attribute facets — only those relevant to the current category with results.
	// Colour → round swatches; age → pills; everything else → checkable options.
	foreach ( kindi_archive_attribute_terms() as $facet ) {
		$param  = 'filter_' . $facet['attribute_name'];
		$chosen = isset( $_GET[ $param ] ) ? array_filter( explode( ',', sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$type   = kindi_facet_type( $facet );

		echo '<details class="kindi-side__group"><summary>' . esc_html( $facet['label'] ) . '</summary><div class="kindi-side__opts kindi-side__opts--' . esc_attr( $type ) . '">';
		foreach ( $facet['terms'] as $t ) {
			$is_on = in_array( $t['slug'], $chosen, true );
			$new   = $is_on ? array_diff( $chosen, array( $t['slug'] ) ) : array_merge( $chosen, array( $t['slug'] ) );
			$url   = $new ? add_query_arg( $param, implode( ',', $new ) ) : remove_query_arg( $param );

			if ( 'color' === $type ) {
				$hex = function_exists( 'kindi_resolve_color' ) ? kindi_resolve_color( $t['name'], $t['slug'] ) : '';
				printf(
					'<a class="kindi-swatch%s" href="%s" title="%s" aria-label="%s" style="--sw:%s"></a>',
					$is_on ? ' is-active' : '',
					esc_url( $url ),
					esc_attr( $t['name'] ),
					esc_attr( $t['name'] ),
					esc_attr( $hex ? $hex : '#ccc' )
				);
			} else {
				printf(
					'<a class="kindi-fopt%s%s" href="%s">%s</a>',
					$is_on ? ' is-active' : '',
					'age' === $type ? ' kindi-fopt--pill' : '',
					esc_url( $url ),
					esc_html( $t['name'] )
				);
			}
		}
		echo '</div></details>';
	}

	// Price range — dual slider (JS enhances; falls back to two number inputs).
	$bounds = kindi_archive_price_bounds();
	$min_v  = isset( $_GET['min_price'] ) ? (int) $_GET['min_price'] : $bounds['min']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max_v  = isset( $_GET['max_price'] ) ? (int) $_GET['max_price'] : $bounds['max']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<details class="kindi-side__group" open><summary>' . esc_html__( 'טווח מחירים', 'kindi' ) . '</summary>';
	printf(
		'<div class="kindi-priceslider" data-min="%1$d" data-max="%2$d" data-cur-min="%3$d" data-cur-max="%4$d">',
		(int) $bounds['min'],
		(int) $bounds['max'],
		(int) $min_v,
		(int) $max_v
	);
	echo '<div class="kindi-priceslider__track"><span class="kindi-priceslider__fill"></span></div>';
	printf( '<input type="range" class="kindi-priceslider__lo" min="%1$d" max="%2$d" value="%3$d" aria-label="%4$s">', (int) $bounds['min'], (int) $bounds['max'], (int) $min_v, esc_attr__( 'מחיר מינימום', 'kindi' ) );
	printf( '<input type="range" class="kindi-priceslider__hi" min="%1$d" max="%2$d" value="%3$d" aria-label="%4$s">', (int) $bounds['min'], (int) $bounds['max'], (int) $max_v, esc_attr__( 'מחיר מקסימום', 'kindi' ) );
	echo '<div class="kindi-priceslider__labels"><span data-pl-min>₪' . esc_html( (string) $bounds['min'] ) . '</span><span class="kindi-priceslider__pill" data-pl-pill></span><span data-pl-max>₪' . esc_html( (string) $bounds['max'] ) . '+</span></div>';
	echo '</div></details>';
}

/**
 * URL that clears all active attribute/price filters (plus pagination), or an
 * empty string when no such filter is active.
 *
 * @return string
 */
function kindi_archive_reset_url(): string {
	$remove = array();
	foreach ( array_keys( $_GET ) as $gk ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 0 === strpos( (string) $gk, 'filter_' ) ) {
			$remove[] = (string) $gk;
		}
	}
	foreach ( array( 'min_price', 'max_price', 'kindi_age', 'kindi_budget' ) as $qk ) {
		if ( isset( $_GET[ $qk ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$remove[] = $qk;
		}
	}
	if ( ! $remove ) {
		return '';
	}
	$remove[] = 'paged';
	return (string) remove_query_arg( $remove );
}

/**
 * No-products state on our archives. WooCommerce normally fires neither
 * `woocommerce_before_shop_loop` nor `woocommerce_after_shop_loop` when the loop
 * is empty, so our two-column wrappers (and the sidebar) would vanish. Re-render
 * the full layout here — filters stay in the sidebar — with a friendly message
 * and a clear-filters action.
 *
 * @return void
 */
function kindi_archive_no_products(): void {
	if ( ! kindi_is_product_archive() ) {
		wc_get_template( 'loop/no-products-found.php' );
		return;
	}

	echo '<div class="kindi-archive kindi-archive--empty">';
	echo '<aside class="kindi-archive__side">';
	kindi_archive_sidebar();
	echo '</aside>';
	echo '<div class="kindi-archive__main">';
	kindi_archive_toolbar();

	echo '<div class="kindi-archive__empty">';
	echo '<span class="kindi-archive__empty-ic">' . kindi_icon( 'search', 'kindi-icon--xl' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<h2 class="kindi-archive__empty-title">' . esc_html__( 'מצטערים, אין תוצאות', 'kindi' ) . '</h2>';
	echo '<p class="kindi-archive__empty-text">' . esc_html__( 'לא נמצאו מוצרים שתואמים את הסינון שבחרתם. אפשר לבטל את הסינון ולנסות שוב.', 'kindi' ) . '</p>';
	$reset = kindi_archive_reset_url();
	if ( '' !== $reset ) {
		echo '<a class="kindi-chip kindi-chip--reset" href="' . esc_url( $reset ) . '">' . kindi_icon( 'close', 'kindi-icon--xs' ) . esc_html__( 'ביטול סינון', 'kindi' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';

	echo '</div></div>';
}
remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
add_action( 'woocommerce_no_products_found', 'kindi_archive_no_products', 10 );

/**
 * Classify a facet for rendering: 'color' (swatches), 'age' (pills) or 'list'.
 *
 * @param array{attribute_name:string,label:string} $facet Facet.
 * @return string
 */
function kindi_facet_type( array $facet ): string {
	$key = strtolower( $facet['attribute_name'] . ' ' . $facet['label'] );
	if ( false !== strpos( $key, 'color' ) || false !== strpos( $key, 'colour' ) || false !== mb_strpos( $facet['label'], 'צבע' ) ) {
		return 'color';
	}
	if ( false !== strpos( $key, 'age' ) || false !== strpos( $key, 'gil' ) || false !== mb_strpos( $facet['label'], 'גיל' ) ) {
		return 'age';
	}
	return 'list';
}

/**
 * Min/max product price for the slider bounds (shop-wide, cached).
 *
 * @return array{min:int,max:int}
 */
function kindi_archive_price_bounds(): array {
	$cached = get_transient( 'kindi_price_bounds' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	global $wpdb;
	$row    = $wpdb->get_row( "SELECT MIN(min_price) AS lo, MAX(max_price) AS hi FROM {$wpdb->wc_product_meta_lookup} WHERE min_price > 0" ); // phpcs:ignore WordPress.DB
	$lo     = $row && null !== $row->lo ? (int) floor( (float) $row->lo ) : 0;
	$hi     = $row && null !== $row->hi ? (int) ceil( (float) $row->hi ) : 1000;
	if ( $hi <= $lo ) {
		$hi = $lo + 100;
	}
	$bounds = array( 'min' => $lo, 'max' => $hi );
	set_transient( 'kindi_price_bounds', $bounds, 12 * HOUR_IN_SECONDS );
	return $bounds;
}

/**
 * Flush a cached attribute-facet list when its terms change.
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy name.
 * @return void
 */
function kindi_flush_facet_cache( $term_id, $tt_id = 0, $taxonomy = '' ): void {
	if ( ! is_string( $taxonomy ) ) {
		return;
	}
	if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
		delete_transient( 'kindi_facet_' . $taxonomy );
	}
	// Bump the term-cache version so all cached category-chip sets refresh.
	if ( 'product_cat' === $taxonomy ) {
		update_option( 'kindi_term_ver', (int) get_option( 'kindi_term_ver', 1 ) + 1, false );
	}
	delete_transient( 'kindi_price_bounds' );
}
add_action( 'created_term', 'kindi_flush_facet_cache', 10, 3 );
add_action( 'edited_term', 'kindi_flush_facet_cache', 10, 3 );
add_action( 'delete_term', 'kindi_flush_facet_cache', 10, 3 );

/**
 * Refresh the price-slider bounds when a product changes.
 *
 * @return void
 */
function kindi_flush_price_bounds(): void {
	delete_transient( 'kindi_price_bounds' );
}
add_action( 'woocommerce_update_product', 'kindi_flush_price_bounds' );
add_action( 'save_post_product', 'kindi_flush_price_bounds' );
