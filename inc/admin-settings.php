<?php
/**
 * Admin content panel — "קינדי" settings screen.
 *
 * A native, dependency-free control panel (Settings-style) for every editable
 * string and link on the site. Saves into the single `kindi_options` option.
 * Security: capability check + nonce on save; each field sanitised by type.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Tabs → sections → fields. Frequently-changed content lives in the "מבצעים
 * ותוכן" tab; rarely-touched section copy is tucked under "טקסטים והגדרות".
 *
 * @return array<string,array<string,mixed>>
 */
function kindi_settings_tabs(): array {
	$tabs = array(
		'promos'   => array(
			'label'    => 'מבצעים ותוכן',
			'sections' => array(
				'עמוד הבית — קטגוריות ומוצרים' => array(
					'home_cats_mode'       => array( 'type' => 'select', 'label' => 'קטגוריות בעמוד הבית', 'options' => array( 'auto' => 'אוטומטי (הפופולריות ביותר)', 'manual' => 'בחירה ידנית' ) ),
					'home_cats'            => array( 'type' => 'taxonomy_multi', 'label' => 'בחירת קטגוריות', 'help' => 'רלוונטי כשנבחר "בחירה ידנית". סמן את הקטגוריות שיופיעו בעמוד הבית.' ),
					'home_products_source' => array( 'type' => 'select', 'label' => 'אילו מוצרים להציג', 'options' => array( 'popularity' => 'הנמכרים ביותר', 'date' => 'החדשים ביותר', 'sale' => 'במבצע', 'best_selling' => 'רבי מכר', 'featured' => 'מוצרים מומלצים', 'category' => 'מקטגוריה מסוימת' ) ),
					'home_products_cat'    => array( 'type' => 'taxonomy_select', 'label' => 'קטגוריית מוצרים', 'help' => 'בשימוש רק כשנבחר "מקטגוריה מסוימת".' ),
					'home_products_count'  => array( 'type' => 'number', 'label' => 'כמות מוצרים להצגה' ),
				),
				'רצועות עליונות' => array(
					'shipbar' => array( 'type' => 'text', 'label' => 'רצועת המשלוח (מתחת ללוגו)' ),
					'ticker'  => array( 'type' => 'textarea', 'label' => 'רצועת מבצעים נעה', 'help' => 'שורה אחת לכל פריט' ),
				),
				'באנר מבצע גדול' => array(
					'promo1_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo1_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo1_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo1_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo1_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר מבצע 2'    => array(
					'promo2_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo2_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo2_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo2_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo2_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר מבצע 3'    => array(
					'promo3_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo3_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo3_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo3_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo3_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר ראשי (Hero)' => array(
					'hero_badge'    => array( 'type' => 'text', 'label' => 'תגית עליונה' ),
					'hero_title1'   => array( 'type' => 'text', 'label' => 'כותרת — חלק 1' ),
					'hero_hl'       => array( 'type' => 'text', 'label' => 'כותרת — חלק מודגש (אדום)' ),
					'hero_title2'   => array( 'type' => 'text', 'label' => 'כותרת — חלק 2 (כחול)' ),
					'hero_lead'     => array( 'type' => 'textarea', 'label' => 'תיאור' ),
					'hero_cta1'     => array( 'type' => 'text', 'label' => 'כפתור ראשי — טקסט' ),
					'hero_cta1_url' => array( 'type' => 'url', 'label' => 'כפתור ראשי — קישור' ),
					'hero_cta2'     => array( 'type' => 'text', 'label' => 'כפתור משני — טקסט' ),
					'hero_cta2_url' => array( 'type' => 'url', 'label' => 'כפתור משני — קישור' ),
				),
			),
		),
		'texts'    => array(
			'label'    => 'טקסטים והגדרות',
			'sections' => array(
				'כללי'         => array(
					'phone'                => array( 'type' => 'text', 'label' => 'טלפון' ),
					'whatsapp'             => array( 'type' => 'text', 'label' => 'מספר וואטסאפ (בינלאומי, ללא +)', 'help' => 'לדוגמה: 972500000000' ),
					'whatsapp_hours'       => array( 'type' => 'text', 'label' => 'שעות שירות וואטסאפ (תצוגה)' ),
					'whatsapp_from'        => array( 'type' => 'number', 'label' => 'וואטסאפ — שעת פתיחה (0-23)', 'help' => 'משמש לסימון "זמינים עכשיו" בכפתור המוצר.' ),
					'whatsapp_to'          => array( 'type' => 'number', 'label' => 'וואטסאפ — שעת סגירה (0-23)' ),
					'whatsapp_product_msg' => array( 'type' => 'textarea', 'label' => 'הודעת וואטסאפ מעמוד מוצר', 'help' => 'אפשר להשתמש ב-{product} ו-{url}.' ),
					'free_shipping'        => array( 'type' => 'number', 'label' => 'סף משלוח חינם (₪)' ),
				),
				'מועדון קינדי' => array(
					'club_title'    => array( 'type' => 'text', 'label' => 'כותרת' ),
					'club_lead'     => array( 'type' => 'textarea', 'label' => 'תיאור' ),
					'club_benefits' => array( 'type' => 'textarea', 'label' => 'הטבות', 'help' => 'שורה אחת לכל הטבה' ),
					'club_cta_url'  => array( 'type' => 'url', 'label' => 'קישור הצטרפות' ),
				),
				'פרטי החנות'   => array(
					'store_address' => array( 'type' => 'text', 'label' => 'כתובת' ),
					'store_phone'   => array( 'type' => 'text', 'label' => 'טלפון בחנות' ),
					'store_hours'   => array( 'type' => 'text', 'label' => 'שעות פתיחה' ),
					'store_waze'    => array( 'type' => 'url', 'label' => 'קישור ניווט (Waze)' ),
					'news_title'    => array( 'type' => 'text', 'label' => 'ניוזלטר — כותרת' ),
					'news_sub'      => array( 'type' => 'textarea', 'label' => 'ניוזלטר — תיאור' ),
				),
				'ניוזלטר ודיוור' => array(
					'newsletter_webhook' => array( 'type' => 'text', 'label' => 'Webhook URL', 'help' => 'כתובת ה-webhook של מערכת הדיוור (Zapier / Make / ActiveTrail / smoove ועוד). בכל הרשמה תישלח אליה בקשת POST עם האימייל בפורמט JSON.' ),
					'newsletter_field'   => array( 'type' => 'text', 'label' => 'שם שדה האימייל', 'help' => 'שם המפתח שבו תישלח כתובת האימייל (ברירת מחדל: email).' ),
					'newsletter_secret'  => array( 'type' => 'text', 'label' => 'סוד אימות (אופציונלי)', 'help' => 'אם תוגדר — תישלח ככותרת X-Kindi-Secret לאימות מקור הבקשה.' ),
				),
				'ביקורות גוגל' => array(
					'google_place_id' => array( 'type' => 'text', 'label' => 'Google Place ID', 'help' => 'מזהה המקום של החנות בגוגל. ניתן למצוא ב-Google Places ID Finder.' ),
					'google_api_key'  => array( 'type' => 'text', 'label' => 'Google API Key', 'help' => 'מפתח API עם הרשאת Places API. הביקורות יוצגו אוטומטית בסקשן "לקוחות מספרים".' ),
				),
				'פוטר ורשתות'  => array(
					'about' => array( 'type' => 'textarea', 'label' => 'טקסט "אודות"' ),
					'email' => array( 'type' => 'text', 'label' => 'אימייל' ),
					'fb'    => array( 'type' => 'url', 'label' => 'קישור פייסבוק' ),
					'ig'    => array( 'type' => 'url', 'label' => 'קישור אינסטגרם' ),
				),
			),
		),
	);

	/**
	 * Filter the settings tabs/sections/fields (used to inject the ACF bridge).
	 *
	 * @param array<string,array<string,mixed>> $tabs Settings tabs.
	 */
	return apply_filters( 'kindi_settings_tabs', $tabs );
}

/**
 * Flat map of every field key → its definition (for saving/sanitising).
 *
 * @return array<string,array{type:string,label:string,help?:string}>
 */
function kindi_settings_all_fields(): array {
	$all = array();
	foreach ( kindi_settings_tabs() as $tab ) {
		foreach ( $tab['sections'] as $fields ) {
			$all += $fields;
		}
	}
	return $all;
}

/**
 * Sanitise a value according to its field type.
 *
 * @param string $type  Field type.
 * @param mixed  $value Raw value.
 * @return mixed
 */
function kindi_sanitize_field( string $type, $value ) {
	switch ( $type ) {
		case 'number':
		case 'taxonomy_select':
			return absint( $value );
		case 'url':
			return esc_url_raw( trim( (string) $value ) );
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Product categories for select/checkbox fields (id => name).
 *
 * @return array<int,string>
 */
function kindi_admin_product_cats(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 300,
			'orderby'    => 'name',
		)
	);
	$out = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$out[ (int) $term->term_id ] = $term->name;
		}
	}
	return $out;
}

/**
 * Register the admin menu page.
 *
 * @return void
 */
function kindi_settings_menu(): void {
	add_menu_page(
		__( 'קינדי — תוכן האתר', 'kindi' ),
		__( 'קינדי', 'kindi' ),
		'manage_options',
		'kindi-settings',
		'kindi_settings_render',
		'dashicons-store',
		59
	);
}
add_action( 'admin_menu', 'kindi_settings_menu' );

/**
 * Handle save + render the settings screen.
 *
 * @return void
 */
function kindi_settings_render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tabs    = kindi_settings_tabs();
	$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'promos'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $tabs[ $current ] ) ) {
		$current = 'promos';
	}
	$saved = false;

	if ( isset( $_POST['kindi_settings_submit'] ) && check_admin_referer( 'kindi_save_settings', 'kindi_nonce' ) ) {
		$clean = get_option( 'kindi_options', array() );
		if ( ! is_array( $clean ) ) {
			$clean = array();
		}

		// Only persist fields actually submitted (the active tab), so other
		// tabs' values are never wiped.
		foreach ( kindi_settings_all_fields() as $key => $field ) {
			if ( 'taxonomy_multi' === $field['type'] ) {
				if ( isset( $_POST['kindi__multi'][ $key ] ) ) {
					$vals          = isset( $_POST['kindi'][ $key ] ) ? (array) wp_unslash( $_POST['kindi'][ $key ] ) : array();
					$clean[ $key ] = array_values( array_filter( array_map( 'absint', $vals ) ) );
				}
				continue;
			}
			if ( ! isset( $_POST['kindi'][ $key ] ) ) {
				continue;
			}
			$clean[ $key ] = kindi_sanitize_field( $field['type'], wp_unslash( $_POST['kindi'][ $key ] ) );
		}

		update_option( 'kindi_options', $clean );
		if ( function_exists( 'kindi_flush_store_cache' ) ) {
			kindi_flush_store_cache();
		}
		$saved = true;
	}

	echo '<div class="wrap kindi-settings"><h1>' . esc_html__( 'קינדי — ניהול תוכן האתר', 'kindi' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'קטגוריות ומוצרים נמשכים אוטומטית מ-WooCommerce. כאן עורכים את תוכן השיווק והטקסטים.', 'kindi' ) . '</p>';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'התוכן נשמר.', 'kindi' ) . '</p></div>';
	}

	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $slug => $tab ) {
		printf(
			'<a href="%s" class="nav-tab%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=kindi-settings&tab=' . $slug ) ),
			$slug === $current ? ' nav-tab-active' : '',
			esc_html( $tab['label'] )
		);
	}
	echo '</h2>';

	echo '<form method="post" action="">';
	wp_nonce_field( 'kindi_save_settings', 'kindi_nonce' );

	foreach ( $tabs[ $current ]['sections'] as $section => $fields ) {
		echo '<h2 style="margin-top:2em">' . esc_html( $section ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $key => $field ) {
			$value = kindi_opt( $key );
			$id    = 'kindi_' . $key;
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

			if ( 'textarea' === $field['type'] ) {
				printf(
					'<textarea id="%1$s" name="kindi[%2$s]" rows="4" class="large-text" dir="rtl">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $key ),
					esc_textarea( (string) $value )
				);
			} elseif ( 'select' === $field['type'] ) {
				echo '<select id="' . esc_attr( $id ) . '" name="kindi[' . esc_attr( $key ) . ']">';
				foreach ( ( $field['options'] ?? array() ) as $opt_val => $opt_label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $opt_val ), selected( (string) $value, (string) $opt_val, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
			} elseif ( 'taxonomy_select' === $field['type'] ) {
				echo '<select id="' . esc_attr( $id ) . '" name="kindi[' . esc_attr( $key ) . ']"><option value="0">— ללא —</option>';
				foreach ( kindi_admin_product_cats() as $tid => $tname ) {
					printf( '<option value="%d"%s>%s</option>', (int) $tid, selected( (int) $value, (int) $tid, false ), esc_html( $tname ) );
				}
				echo '</select>';
			} elseif ( 'meta_select' === $field['type'] ) {
					$keys = function_exists( 'kindi_detected_meta_keys' ) ? kindi_detected_meta_keys() : array();
					echo '<select id="' . esc_attr( $id ) . '" name="kindi[' . esc_attr( $key ) . ']"><option value="">— ' . esc_html__( 'לא ממופה', 'kindi' ) . ' —</option>';
					foreach ( $keys as $mk ) {
						printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $mk ), selected( (string) $value, (string) $mk, false ) );
					}
					echo '</select>';
					if ( ! $keys ) {
						echo '<p class="description">' . esc_html__( 'לא נמצאו שדות מותאמים על המוצרים עדיין.', 'kindi' ) . '</p>';
					}
				} elseif ( 'note' === $field['type'] ) {
					echo ''; // Help text printed below handles the content.
				} elseif ( 'taxonomy_multi' === $field['type'] ) {
				$selected_ids = is_array( $value ) ? array_map( 'intval', $value ) : array();
				echo '<input type="hidden" name="kindi__multi[' . esc_attr( $key ) . ']" value="1">';
				echo '<div style="max-height:220px;overflow:auto;border:1px solid #dcdcde;border-radius:6px;padding:8px;column-count:2">';
				foreach ( kindi_admin_product_cats() as $tid => $tname ) {
					printf(
						'<label style="display:block;margin:2px 0"><input type="checkbox" name="kindi[%1$s][]" value="%2$d"%3$s> %4$s</label>',
						esc_attr( $key ),
						(int) $tid,
						checked( in_array( (int) $tid, $selected_ids, true ), true, false ),
						esc_html( $tname )
					);
				}
				echo '</div>';
			} else {
				// URLs render as text so relative paths (e.g. /shop/) are accepted.
				$input_type = 'number' === $field['type'] ? 'number' : 'text';
				printf(
					'<input type="%1$s" id="%2$s" name="kindi[%3$s]" value="%4$s" class="regular-text" dir="rtl">',
					esc_attr( $input_type ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( (string) $value )
				);
			}

			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	submit_button( __( 'שמירת תוכן', 'kindi' ), 'primary', 'kindi_settings_submit' );
	echo '</form>';

	/**
	 * Fires after the settings form — used for standalone tools (e.g. ACF import).
	 */
	do_action( 'kindi_settings_after_form' );

	echo '</div>';
}
