<?php
/**
 * Dynamic store sections — render live WooCommerce data with caching.
 *
 * Per the Multi Digital standard: minimal, prepared queries; rendered fragments
 * cached in transients (object-cache friendly) and invalidated only on relevant
 * changes; graceful static fallback when the store has no data yet.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Diagnostic shortcode: [kindi_ping] — confirms shortcodes run and reports the
 * active theme version (to catch stale installs). Safe to leave or remove.
 *
 * @return string
 */
function kindi_ping_shortcode(): string {
	$ver   = defined( 'KINDI_VERSION' ) ? KINDI_VERSION : '?';
	$parts = array( 'גרסה ' . $ver );

	$parts[] = 'WooCommerce: ' . ( class_exists( 'WooCommerce' ) ? 'כן' : 'לא' );

	// Wrapped so a failure here can never blank the shortcode — it reports instead.
	try {
		$n       = function_exists( 'kindi_get_store_categories' ) ? count( (array) kindi_get_store_categories( 12 ) ) : -1;
		$parts[] = 'קטגוריות שנמצאו: ' . $n;
	} catch ( \Throwable $e ) {
		$parts[] = 'שגיאת קטגוריות: ' . $e->getMessage();
	}

	return '<div style="padding:1rem 1.25rem;background:#1B2A52;color:#fff;border-radius:10px;font-weight:700;text-align:center;font-family:sans-serif;direction:rtl">'
		. '✅ Kindi פעיל · ' . esc_html( implode( ' · ', $parts ) ) . '</div>';
}
add_shortcode( 'kindi_ping', 'kindi_ping_shortcode' );

/**
 * Admin-only self-test — visit any front-end URL with ?kindi_selftest=1 while
 * logged in as an administrator to get a plain-text report. This bypasses
 * shortcodes AND the block renderer, so it works even when those are broken,
 * and isolates the failure: theme-not-loading vs. shortcode-not-registered vs.
 * shortcode-registered-but-not-rendered vs. a caching/optimization layer.
 *
 * @return void
 */
function kindi_selftest(): void {
	if ( empty( $_GET['kindi_selftest'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );

	$ver  = defined( 'KINDI_VERSION' ) ? KINDI_VERSION : '?';
	$line = str_repeat( '=', 48 );

	echo "Kindi self-test\n{$line}\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	echo 'Theme version (KINDI_VERSION): ' . esc_html( (string) $ver ) . "\n";
	echo 'style.css version: ' . esc_html( (string) wp_get_theme()->get( 'Version' ) ) . "\n";
	echo 'WooCommerce active: ' . ( class_exists( 'WooCommerce' ) ? 'yes' : 'no' ) . "\n";
	echo "{$line}\n"; // phpcs:ignore WordPress.Security.EscapeOutput

	$tests = array( 'kindi_ping', 'kindi_categories', 'kindi_hot_products', 'products' );
	foreach ( $tests as $tag ) {
		$registered = shortcode_exists( $tag ) ? 'registered' : 'NOT registered';
		$sample     = '[' . $tag . ( 'products' === $tag ? ' limit="1"' : '' ) . ']';
		$rendered   = do_shortcode( $sample );
		$len        = strlen( $rendered );
		$blank      = ( 0 === $len || $sample === $rendered );
		echo "[{$tag}] {$registered} | do_shortcode -> " // phpcs:ignore WordPress.Security.EscapeOutput
			. ( $blank ? 'BLANK/UNPROCESSED' : $len . ' chars' ) . "\n";
	}

	echo "{$line}\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	$cats_fn = function_exists( 'kindi_get_store_categories' );
	echo 'kindi_get_store_categories(): ' . ( $cats_fn ? 'exists' : 'MISSING' ) . "\n";
	if ( $cats_fn ) {
		try {
			echo 'categories found: ' . count( (array) kindi_get_store_categories( 12 ) ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		} catch ( \Throwable $e ) {
			echo 'categories ERROR: ' . esc_html( $e->getMessage() ) . "\n";
		}
	}

	// Detect common HTML-mangling optimizers that can strip injected markup.
	$opt = array();
	if ( defined( 'LSCWP_V' ) ) {
		$opt[] = 'LiteSpeed Cache';
	}
	if ( defined( 'WP_ROCKET_VERSION' ) ) {
		$opt[] = 'WP Rocket';
	}
	if ( defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) ) {
		$opt[] = 'Autoptimize';
	}
	if ( class_exists( 'Perfmatters' ) || defined( 'PERFMATTERS_VERSION' ) ) {
		$opt[] = 'Perfmatters';
	}
	echo 'optimizers detected: ' . esc_html( $opt ? implode( ', ', $opt ) : 'none' ) . "\n";
	echo "{$line}\nEND\n"; // phpcs:ignore WordPress.Security.EscapeOutput

	exit;
}
add_action( 'template_redirect', 'kindi_selftest' );

/**
 * Accent palette cycled across category cards.
 *
 * @return array<int,string>
 */
function kindi_accents(): array {
	return array( 'red', 'navy', 'blue' );
}

/**
 * Static fallback categories (used before the store has product categories).
 *
 * @return array<int,array{img:string,name:string,count:string,acc:string}>
 */
function kindi_fallback_categories(): array {
	return array(
		array( 'img' => 'blocks.webp', 'name' => 'צעצועים', 'count' => '', 'acc' => 'red' ),
		array( 'img' => 'boardgame.webp', 'name' => 'משחקי קופסה', 'count' => '', 'acc' => 'navy' ),
		array( 'img' => 'back-to-school.webp', 'name' => 'חזרה לביה"ס', 'count' => '', 'acc' => 'blue' ),
		array( 'img' => 'art-supplies.webp', 'name' => 'יצירה ואומנות', 'count' => '', 'acc' => 'red' ),
		array( 'img' => 'promo-art.webp', 'name' => 'ציוד לגננת', 'count' => '', 'acc' => 'navy' ),
		array( 'img' => 'puzzle.webp', 'name' => 'פאזלים', 'count' => '', 'acc' => 'blue' ),
		array( 'img' => 'baby-plush.webp', 'name' => 'תינוקות ופעוטות', 'count' => '', 'acc' => 'red' ),
		array( 'img' => 'gameboy.webp', 'name' => 'משחקי למידה', 'count' => '', 'acc' => 'navy' ),
		array( 'img' => 'playmobil.webp', 'name' => 'בובות ודמיון', 'count' => '', 'acc' => 'red' ),
		array( 'img' => 'promo-playmobil.webp', 'name' => 'כלי תחבורה', 'count' => '', 'acc' => 'blue' ),
		array( 'img' => 'promo-back-to-school.webp', 'name' => 'כתיבה ולמידה', 'count' => '', 'acc' => 'navy' ),
		array( 'img' => 'promo-blocks.webp', 'name' => 'מתנות ופרסים', 'count' => '', 'acc' => 'red' ),
	);
}

/**
 * Top product categories from WooCommerce (cached).
 *
 * @param int $limit Max categories.
 * @return array<int,array{name:string,url:string,count:int,img:string,acc:string}>
 */
function kindi_get_store_categories( int $limit = 12 ): array {
	if ( ! function_exists( 'wc_get_product' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$key    = 'kindi_store_cats_' . $limit;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$mode   = function_exists( 'kindi_opt' ) ? kindi_opt( 'home_cats_mode', 'auto' ) : 'auto';
	$manual = function_exists( 'kindi_opt' ) ? kindi_opt( 'home_cats', array() ) : array();

	if ( 'manual' === $mode && is_array( $manual ) && $manual ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'include'    => array_map( 'absint', $manual ),
				'orderby'    => 'include',
			)
		);
	} else {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => $limit,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'exclude'    => array_filter( array( (int) get_option( 'default_product_cat' ) ) ),
			)
		);
	}

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$accents = kindi_accents();
	$out     = array();
	$i       = 0;

	foreach ( $terms as $term ) {
		$thumb_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		$img      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'woocommerce_thumbnail' ) : '';

		$out[] = array(
			'name'  => $term->name,
			'url'   => (string) get_term_link( $term ),
			'count' => (int) $term->count,
			'img'   => (string) $img,
			'acc'   => $accents[ $i % count( $accents ) ],
		);
		++$i;
	}

	set_transient( $key, $out, 12 * HOUR_IN_SECONDS );

	return $out;
}

/**
 * Render the category grid markup (live or fallback).
 *
 * @return string
 */
function kindi_render_categories(): string {
	$cats = kindi_get_store_categories( 12 );
	$live = ! empty( $cats );

	ob_start();
	echo '<div class="kindi-cats">';

	if ( $live ) {
		foreach ( $cats as $c ) {
			$style = $c['img'] ? '' : ' style="background:var(--brand-blue-soft)"';
			printf(
				'<a class="kindi-cat kindi-cat--%1$s" href="%2$s"><span class="kindi-cat__media"%3$s>%4$s<span class="kindi-cat__tint"></span><span class="kindi-cat__pill">לצפייה במוצרים ←</span></span><span class="kindi-cat__bar"></span><span class="kindi-cat__name"><b>%5$s</b><span class="kindi-cat__count">%6$s</span></span></a>',
				esc_attr( $c['acc'] ),
				esc_url( $c['url'] ),
				$style, // phpcs:ignore WordPress.Security.EscapeOutput -- static literal.
				$c['img'] ? sprintf( '<img src="%s" alt="%s" loading="lazy" decoding="async" width="400" height="400">', esc_url( $c['img'] ), esc_attr( $c['name'] ) ) : '',
				esc_html( $c['name'] ),
				esc_html( sprintf( '%s מוצרים', number_format_i18n( $c['count'] ) ) )
			);
		}
	} else {
		foreach ( kindi_fallback_categories() as $c ) {
			printf(
				'<a class="kindi-cat kindi-cat--%1$s" href="%2$s"><span class="kindi-cat__media"><img src="%3$s" alt="%4$s" loading="lazy" decoding="async" width="400" height="400"><span class="kindi-cat__tint"></span><span class="kindi-cat__pill">לצפייה במוצרים ←</span></span><span class="kindi-cat__bar"></span><span class="kindi-cat__name"><b>%4$s</b><span class="kindi-cat__count">%5$s</span></span></a>',
				esc_attr( $c['acc'] ),
				esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '#' ),
				kindi_img( 'photos/' . $c['img'] ),
				esc_attr( $c['name'] ),
				esc_html( $c['count'] )
			);
		}
	}

	echo '</div>';

	return (string) ob_get_clean();
}

/**
 * Shortcode: [kindi_categories] — full section (heading + live grid).
 *
 * @return string
 */
function kindi_categories_shortcode(): string {
	$head = kindi_section_head(
		array(
			'eyebrow'   => 'קטגוריות מובילות',
			'title'     => 'בחרו את',
			'highlight' => 'העולם המתאים',
			'suffix'    => 'לכם',
			'desc'      => 'קולקציה מסודרת לפי עניין, גיל ומטרה — כדי שתמצאו בדיוק מה שאתם מחפשים',
		)
	);

	return '<section class="kindi-section" id="categories">' . $head . kindi_render_categories() . '</section>';
}
add_shortcode( 'kindi_categories', 'kindi_categories_shortcode' );

/**
 * Invalidate cached store data when products or categories change.
 *
 * @return void
 */
function kindi_flush_store_cache(): void {
	for ( $i = 1; $i <= 12; $i++ ) {
		delete_transient( 'kindi_store_cats_' . $i );
	}
}
add_action( 'edited_product_cat', 'kindi_flush_store_cache' );
add_action( 'created_product_cat', 'kindi_flush_store_cache' );
add_action( 'delete_product_cat', 'kindi_flush_store_cache' );
add_action( 'save_post_product', 'kindi_flush_store_cache' );
