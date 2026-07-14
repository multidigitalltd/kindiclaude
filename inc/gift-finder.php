<?php
/**
 * Gift-by-age finder.
 *
 * Lets shoppers filter the store by a child's age band (and optional budget),
 * powered by the existing toy meta. The free-text "גיל מומלץ" value is mirrored
 * to a numeric `_kindi_age_min` meta so it can be range-queried efficiently
 * (no LIKE scans, no N+1). The age source is resolved through the ACF bridge,
 * so it works on ACF-authored data too — and keeps working after ACF is removed.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Age bands: key => [label, min, max].
 *
 * @return array<string,array{label:string,min:int,max:int}>
 */
function kindi_age_bands(): array {
	return array(
		'0-2'    => array( 'label' => '0-2 (תינוקות)', 'min' => 0, 'max' => 2 ),
		'3-5'    => array( 'label' => '3-5 (גיל הגן)', 'min' => 3, 'max' => 5 ),
		'6-8'    => array( 'label' => '6-8 (יסודי)', 'min' => 6, 'max' => 8 ),
		'9-12'   => array( 'label' => '9-12 (חטיבה)', 'min' => 9, 'max' => 12 ),
		'13plus' => array( 'label' => '13+ (נוער)', 'min' => 13, 'max' => 99 ),
	);
}

/**
 * Budget tiers (max price): key => [label, max].
 *
 * @return array<string,array{label:string,max:int}>
 */
function kindi_budget_tiers(): array {
	return array(
		'50'  => array( 'label' => 'עד 50 ₪', 'max' => 50 ),
		'100' => array( 'label' => 'עד 100 ₪', 'max' => 100 ),
		'200' => array( 'label' => 'עד 200 ₪', 'max' => 200 ),
		'500' => array( 'label' => 'עד 500 ₪', 'max' => 500 ),
	);
}

/**
 * Extract the minimum recommended age (first integer) from a free-text label.
 *
 * "3+" → 3, "3-5" → 3, "+13" → 13, "מתאים מגיל 6" → 6, "" → null.
 *
 * @param string $label Age label.
 * @return int|null
 */
function kindi_parse_age_min( string $label ): ?int {
	if ( preg_match( '/\d+/', $label, $m ) ) {
		return (int) $m[0];
	}
	return null;
}

/**
 * Sync the numeric age mirror from the resolved age label.
 *
 * @param int $post_id Product ID.
 * @return void
 */
function kindi_sync_age_min( int $post_id ): void {
	if ( 'product' !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$label = function_exists( 'kindi_resolve_age_label' ) ? kindi_resolve_age_label( $post_id ) : (string) get_post_meta( $post_id, '_kindi_age', true );
	$min   = kindi_parse_age_min( $label );

	if ( null === $min ) {
		delete_post_meta( $post_id, '_kindi_age_min' );
	} else {
		update_post_meta( $post_id, '_kindi_age_min', $min );
	}
}
add_action( 'save_post_product', 'kindi_sync_age_min', 20 );

/**
 * Register the public query vars.
 *
 * @param array<int,string> $vars Query vars.
 * @return array<int,string>
 */
function kindi_gift_query_vars( array $vars ): array {
	$vars[] = 'kindi_age';
	$vars[] = 'kindi_budget';
	return $vars;
}
add_filter( 'query_vars', 'kindi_gift_query_vars' );

/**
 * Apply the age/budget filter to the main store query.
 *
 * @param WP_Query $query Query.
 * @return void
 */
function kindi_gift_filter_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$is_store = ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
		|| ( is_search() && 'product' === $query->get( 'post_type' ) );
	if ( ! $is_store ) {
		return;
	}

	$meta = (array) $query->get( 'meta_query' );

	$age_key = get_query_var( 'kindi_age' );
	$bands   = kindi_age_bands();
	if ( $age_key && isset( $bands[ $age_key ] ) ) {
		// Exact band tag first (products tagged with the fixed age bands), OR the
		// legacy numeric mirror parsed from the old free-text field — so the
		// filter keeps matching products that were never re-tagged.
		$meta[] = array(
			'relation' => 'OR',
			array(
				'key'     => '_kindi_age_band',
				'value'   => $age_key,
				'compare' => '=',
			),
			array(
				'key'     => '_kindi_age_min',
				'value'   => array( $bands[ $age_key ]['min'], $bands[ $age_key ]['max'] ),
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			),
		);
	}

	$budget = get_query_var( 'kindi_budget' );
	$tiers  = kindi_budget_tiers();
	if ( $budget && isset( $tiers[ $budget ] ) ) {
		$meta[] = array(
			'key'     => '_price',
			'value'   => $tiers[ $budget ]['max'],
			'type'    => 'NUMERIC',
			'compare' => '<=',
		);
	}

	if ( count( $meta ) > 1 ) {
		$meta['relation'] = 'AND';
	}
	if ( $meta ) {
		$query->set( 'meta_query', $meta );
	}
}
add_action( 'pre_get_posts', 'kindi_gift_filter_query' );

/**
 * Build a shop URL pre-filtered by age band (and optional budget).
 *
 * @param string $age_key    Age band key.
 * @param string $budget_key Budget tier key.
 * @return string
 */
function kindi_gift_url( string $age_key, string $budget_key = '' ): string {
	$base = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	$args = array( 'kindi_age' => $age_key );
	if ( '' !== $budget_key ) {
		$args['kindi_budget'] = $budget_key;
	}
	return add_query_arg( $args, $base );
}

/**
 * Shortcode: [kindi_gift_finder] — an age + budget gift wizard (GET to shop).
 *
 * @return string
 */
function kindi_gift_finder_shortcode(): string {
	$bands  = kindi_age_bands();
	$tiers  = kindi_budget_tiers();
	$action = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

	$cur_age    = (string) get_query_var( 'kindi_age' );
	$cur_budget = (string) get_query_var( 'kindi_budget' );

	ob_start();
	?>
	<form class="kindi-giftfinder" method="get" action="<?php echo esc_url( $action ); ?>" role="search" aria-label="<?php esc_attr_e( 'מציאת מתנה לפי גיל', 'kindi' ); ?>">
		<div class="kindi-giftfinder__row">
			<span class="kindi-giftfinder__label"><?php esc_html_e( 'גיל הילד/ה', 'kindi' ); ?></span>
			<div class="kindi-giftfinder__chips">
				<?php foreach ( $bands as $key => $band ) : ?>
					<label class="kindi-chip">
						<input type="radio" name="kindi_age" value="<?php echo esc_attr( $key ); ?>" <?php checked( $cur_age, $key ); ?> required>
						<span><?php echo esc_html( $band['label'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<div class="kindi-giftfinder__row">
			<label class="kindi-giftfinder__label" for="kindi-budget"><?php esc_html_e( 'תקציב', 'kindi' ); ?></label>
			<select id="kindi-budget" name="kindi_budget" class="kindi-giftfinder__select">
				<option value=""><?php esc_html_e( 'כל מחיר', 'kindi' ); ?></option>
				<?php foreach ( $tiers as $key => $tier ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $cur_budget, $key ); ?>><?php echo esc_html( $tier['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="kindi-btn kindi-btn--red"><?php esc_html_e( 'מצאו לי מתנה', 'kindi' ); ?></button>
	</form>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'kindi_gift_finder', 'kindi_gift_finder_shortcode' );

/**
 * Incrementally backfill `_kindi_age_min` for products that lack it, so the
 * filter works on the existing catalogue without a manual re-save. Runs a small
 * batch per admin request until the catalogue is covered.
 *
 * @return void
 */
function kindi_backfill_age_min(): void {
	if ( 'done' === get_option( 'kindi_age_backfill', '' ) ) {
		return;
	}

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_kindi_age_scanned',
					'compare' => 'NOT EXISTS',
				),
			),
			'no_found_rows'  => true,
		)
	);

	if ( ! $ids ) {
		update_option( 'kindi_age_backfill', 'done', false );
		return;
	}

	foreach ( $ids as $id ) {
		// Builds (or clears) `_kindi_age_min` only when a real age exists, so the
		// numeric index never holds a bogus 0 that would match the 0-2 band.
		kindi_sync_age_min( (int) $id );
		// Mark as scanned in a separate key so empty-age products aren't re-scanned.
		update_post_meta( $id, '_kindi_age_scanned', 1 );
	}

	// More products remain — run the next batch shortly via cron, off the request.
	if ( ! wp_next_scheduled( 'kindi_age_backfill_cron' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'kindi_age_backfill_cron' );
	}
}
add_action( 'kindi_age_backfill_cron', 'kindi_backfill_age_min' );

/**
 * Ensure the age backfill is scheduled (once) until complete — replaces the old
 * per-request admin_init scan. Cheap: only a single get_option + wp_next_scheduled.
 *
 * @return void
 */
function kindi_schedule_age_backfill(): void {
	if ( 'done' === get_option( 'kindi_age_backfill', '' ) ) {
		return;
	}
	if ( ! wp_next_scheduled( 'kindi_age_backfill_cron' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'kindi_age_backfill_cron' );
	}
}
add_action( 'init', 'kindi_schedule_age_backfill' );
