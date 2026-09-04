<?php
/**
 * Friendly per-menu-item controls in Appearance → Menus: an icon picker, a
 * "highlight" toggle, and a "promo card" toggle (with subtitle + colour) — so
 * the mega-menu is configured from dropdowns instead of CSS classes. Stored as
 * menu-item post meta and read in kindi_build_nav_tree(). Admin-only: zero
 * front-end weight.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Icon choices for the menu-item picker (curated, with Hebrew labels).
 *
 * @return array<string,string>
 */
function kindi_icon_choices(): array {
	return array(
		'auto'     => __( 'אוטומטי (לפי שם)', 'kindi' ),
		'face'     => __( 'פרצוף קינדי', 'kindi' ),
		'grid'     => __( 'כל הקטגוריות (גריד)', 'kindi' ),
		'dice'     => __( 'משחקים (קוביה)', 'kindi' ),
		'gamepad'  => __( 'גיימינג', 'kindi' ),
		'puzzle'   => __( 'פאזלים', 'kindi' ),
		'blocks'   => __( 'בנייה / לגו', 'kindi' ),
		'backpack' => __( 'חזרה לבית ספר (ילקוט)', 'kindi' ),
		'pencil'   => __( 'כלי כתיבה', 'kindi' ),
		'palette'  => __( 'יצירה ואומנות', 'kindi' ),
		'teddy'    => __( 'גן / בובות (דובי)', 'kindi' ),
		'ball'     => __( 'חצר וספורט (כדור)', 'kindi' ),
		'car'      => __( 'רכבים', 'kindi' ),
		'sun'      => __( 'קיץ (שמש)', 'kindi' ),
		'party'    => __( 'חגים (מסיבה)', 'kindi' ),
		'baby'     => __( 'תינוקות', 'kindi' ),
		'gem'      => __( 'מותגים (יהלום)', 'kindi' ),
		'fire'     => __( 'מבצעים (אש)', 'kindi' ),
		'gift'     => __( 'מתנות', 'kindi' ),
		'crown'    => __( 'פרימיום (כתר)', 'kindi' ),
		'rocket'   => __( 'חלל / חללית', 'kindi' ),
		'star'     => __( 'כוכב', 'kindi' ),
		'heart'    => __( 'לב', 'kindi' ),
		'shield'   => __( 'מגן', 'kindi' ),
		'truck'    => __( 'משלוח', 'kindi' ),
		'sparkles' => __( 'נצנוצים', 'kindi' ),
		'tag'      => __( 'תווית מחיר', 'kindi' ),
	);
}

/**
 * Render the custom fields inside each menu-item box (WP 5.4+).
 *
 * @param int $item_id Menu item (post) ID.
 * @return void
 */
function kindi_nav_menu_item_fields( $item_id ): void {
	$item_id = (int) $item_id;
	$icon    = (string) get_post_meta( $item_id, '_kindi_icon', true );
	$icon    = '' !== $icon ? $icon : 'auto';
	$hl      = '1' === get_post_meta( $item_id, '_kindi_highlight', true );
	$feat    = '1' === get_post_meta( $item_id, '_kindi_feature', true );
	$tone    = (string) get_post_meta( $item_id, '_kindi_tone', true );
	$tone    = in_array( $tone, array( 'red', 'navy', 'blue' ), true ) ? $tone : 'red';
	$sub     = (string) get_post_meta( $item_id, '_kindi_subtitle', true );

	wp_nonce_field( 'kindi_menu_fields', 'kindi_menu_nonce' );
	?>
	<p class="description description-wide kindi-menu-field">
		<label><?php esc_html_e( 'אייקון (קינדי)', 'kindi' ); ?><br>
			<select name="kindi_menu_icon[<?php echo esc_attr( (string) $item_id ); ?>]" class="widefat">
				<?php foreach ( kindi_icon_choices() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</p>
	<p class="description description-wide kindi-menu-field">
		<label><input type="checkbox" name="kindi_menu_highlight[<?php echo esc_attr( (string) $item_id ); ?>]" value="1" <?php checked( $hl ); ?>> <?php esc_html_e( 'פריט מודגש (כפתור אדום)', 'kindi' ); ?></label>
	</p>
	<p class="description description-wide kindi-menu-field">
		<label><input type="checkbox" name="kindi_menu_feature[<?php echo esc_attr( (string) $item_id ); ?>]" value="1" <?php checked( $feat ); ?>> <?php esc_html_e( 'כרטיס קידום במגה-מניו (לשים כפריט-בן של קטגוריה)', 'kindi' ); ?></label>
	</p>
	<p class="description description-wide kindi-menu-field">
		<label><?php esc_html_e( 'כותרת משנה לכרטיס הקידום', 'kindi' ); ?><br>
			<input type="text" name="kindi_menu_subtitle[<?php echo esc_attr( (string) $item_id ); ?>]" value="<?php echo esc_attr( $sub ); ?>" class="widefat">
		</label>
	</p>
	<p class="description description-wide kindi-menu-field">
		<label><?php esc_html_e( 'צבע כרטיס הקידום', 'kindi' ); ?><br>
			<select name="kindi_menu_tone[<?php echo esc_attr( (string) $item_id ); ?>]">
				<option value="red" <?php selected( $tone, 'red' ); ?>><?php esc_html_e( 'אדום', 'kindi' ); ?></option>
				<option value="navy" <?php selected( $tone, 'navy' ); ?>><?php esc_html_e( 'כחול כהה', 'kindi' ); ?></option>
				<option value="blue" <?php selected( $tone, 'blue' ); ?>><?php esc_html_e( 'כחול', 'kindi' ); ?></option>
			</select>
		</label>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'kindi_nav_menu_item_fields' );

/**
 * Persist the custom menu-item fields on menu save.
 *
 * @param int $menu_id         Menu ID.
 * @param int $menu_item_db_id Menu item (post) ID.
 * @return void
 */
function kindi_save_nav_menu_item_fields( $menu_id, $menu_item_db_id ): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	if ( ! isset( $_POST['kindi_menu_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kindi_menu_nonce'] ) ), 'kindi_menu_fields' ) ) {
		return;
	}

	$id = (int) $menu_item_db_id;

	$icon = isset( $_POST['kindi_menu_icon'][ $id ] ) ? sanitize_key( wp_unslash( $_POST['kindi_menu_icon'][ $id ] ) ) : 'auto';
	if ( '' === $icon || 'auto' === $icon ) {
		delete_post_meta( $id, '_kindi_icon' );
	} else {
		update_post_meta( $id, '_kindi_icon', $icon );
	}

	if ( ! empty( $_POST['kindi_menu_highlight'][ $id ] ) ) {
		update_post_meta( $id, '_kindi_highlight', '1' );
	} else {
		delete_post_meta( $id, '_kindi_highlight' );
	}

	if ( ! empty( $_POST['kindi_menu_feature'][ $id ] ) ) {
		update_post_meta( $id, '_kindi_feature', '1' );
	} else {
		delete_post_meta( $id, '_kindi_feature' );
	}

	$tone = isset( $_POST['kindi_menu_tone'][ $id ] ) ? sanitize_key( wp_unslash( $_POST['kindi_menu_tone'][ $id ] ) ) : 'red';
	update_post_meta( $id, '_kindi_tone', in_array( $tone, array( 'red', 'navy', 'blue' ), true ) ? $tone : 'red' );

	$sub = isset( $_POST['kindi_menu_subtitle'][ $id ] ) ? sanitize_text_field( wp_unslash( $_POST['kindi_menu_subtitle'][ $id ] ) ) : '';
	if ( '' === $sub ) {
		delete_post_meta( $id, '_kindi_subtitle' );
	} else {
		update_post_meta( $id, '_kindi_subtitle', $sub );
	}
}
add_action( 'wp_update_nav_menu_item', 'kindi_save_nav_menu_item_fields', 10, 2 );
