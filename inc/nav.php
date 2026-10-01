<?php
/**
 * Navigation — flexible mega-menu driven by a WordPress menu.
 *
 * Assign a menu to the "תפריט ראשי (קינדי)" location (Appearance → Menus) for
 * full drag-and-drop control: level 1 = top item, level 2 = mega column heading,
 * level 3 = column links. Add a menu-item CSS class `kindi-icon-<name>` to set
 * its icon and `highlight` to render it as the red promo item. Falls back to a
 * curated default menu when none is assigned.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the primary menu location.
 *
 * @return void
 */
function kindi_register_menus(): void {
	register_nav_menus(
		array(
			'primary'             => __( 'תפריט ראשי (קינדי)', 'kindi' ),
			'footer-main'         => __( 'פוטר — ראשי', 'kindi' ),
			'footer-institutions' => __( 'פוטר — גננות ומוסדות', 'kindi' ),
			'footer-news'         => __( 'פוטר — חדשות ועדכונים', 'kindi' ),
		)
	);
}
add_action( 'after_setup_theme', 'kindi_register_menus' );

/**
 * Render one footer link column: the assigned WordPress menu when set (edit it
 * under Appearance → Menus), otherwise a sensible default link list. Lets the
 * store control the footer links from the dashboard without touching code.
 *
 * @param string                     $location Registered menu location.
 * @param array<string,string>       $fallback label => URL default links.
 * @return void
 */
function kindi_footer_menu( string $location, array $fallback ): void {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => '',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	echo '<ul>';
	foreach ( $fallback as $label => $url ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * Render a top-level nav item's icon. The default is a small Kindi face image;
 * a `kindi-icon-<name>` menu class swaps in a brand SVG icon instead.
 *
 * @param string $icon Icon name ('face' for the default mascot face).
 * @return string
 */
function kindi_nav_icon( string $icon ): string {
	if ( 'face' === $icon ) {
		$src = function_exists( 'kindi_img' ) ? kindi_img( 'mascot/face.webp' ) : '';
		return '<img class="kindi-nav__face" src="' . esc_url( $src ) . '" alt="" width="18" height="18" loading="lazy" decoding="async">';
	}
	return kindi_icon( $icon, 'kindi-icon--sm' );
}

/**
 * Guess a fitting brand icon for a category label (matches the reference design's
 * pairing). Used when a menu item has no explicit `kindi-icon-<name>` class, so
 * WP-menu categories get sensible icons automatically. Falls back to the Kindi
 * face. More specific keywords are checked before generic ones.
 *
 * @param string $label Category label.
 * @return string Icon key.
 */
function kindi_guess_category_icon( string $label ): string {
	$rules = array(
		'קטגוריות' => 'grid',
		'חדש'      => 'sparkles',
		'מומלצ'    => 'star',
		'חצר'      => 'ball',
		'גינה'     => 'ball',
		'ספורט'    => 'ball',
		'גיל'      => 'baby',
		'תינוק'    => 'baby',
		'פעוט'     => 'baby',
		'בית ספר'  => 'backpack',
		'בית הספר' => 'backpack',
		'ילקוט'    => 'backpack',
		'כתיבה'    => 'pencil',
		'יציר'     => 'palette',
		'אומנות'   => 'palette',
		'אמנות'    => 'palette',
		'גננת'     => 'teddy',
		'לגן'      => 'teddy',
		'קיץ'      => 'sun',
		'בריכ'     => 'sun',
		'חג'       => 'party',
		'פורים'    => 'party',
		'פסח'      => 'party',
		'חנוכה'    => 'party',
		'תחפוש'    => 'party',
		'מותג'     => 'gem',
		'מבצע'     => 'fire',
		'הנחה'     => 'fire',
		'מתנ'      => 'gift',
		'פאזל'     => 'puzzle',
		'לגו'      => 'blocks',
		'בנייה'    => 'blocks',
		'רכב'      => 'car',
		'בוב'      => 'baby',
		'משחק'     => 'dice',
		'קופס'     => 'dice',
	);
	foreach ( $rules as $needle => $icon ) {
		if ( false !== mb_stripos( $label, $needle ) ) {
			return $icon;
		}
	}
	return 'face';
}

/**
 * Pick the announcement-strip icon that matches a message's content (so icons
 * always pair with their message exactly like the design), with an index-based
 * fallback for custom lines.
 *
 * @param string $text     Message text.
 * @param string $fallback Fallback icon key.
 * @return string Icon key.
 */
function kindi_ticker_icon( string $text, string $fallback = 'sparkles' ): string {
	$map = array(
		'משלוח'   => 'truck',
		'מועדון'  => 'gift',
		'חזרה על' => 'gift',
		'קולקצי'  => 'sparkles',
		'בית הספר' => 'sparkles',
		'נחת'     => 'sparkles',
		'מאובטח'  => 'shield',
		'תשלום'   => 'shield',
		'SSL'     => 'shield',
		'PCI'     => 'shield',
		'שירות'   => 'phone',
	);
	foreach ( $map as $needle => $icon ) {
		if ( false !== mb_stripos( $text, $needle ) ) {
			return $icon;
		}
	}
	if ( preg_match( '/0\d[-\d ]{6,}/', $text ) ) {
		return 'phone';
	}
	return $fallback;
}

/**
 * Normalised navigation items (from the assigned menu, or the default).
 *
 * @return array<int,array{label:string,url:string,icon:string,highlight:bool,cols:array<int,array{title:string,links:array<int,array{label:string,url:string}>}>}>
 */
function kindi_nav_items(): array {
	$locations = get_nav_menu_locations();
	$menu_id   = ! empty( $locations['primary'] ) ? (int) $locations['primary'] : 0;

	if ( $menu_id ) {
		// The menu rarely changes — cache the built tree, flushed on menu save.
		$key    = 'kindi_nav_tree_' . $menu_id;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$items = wp_get_nav_menu_items( $menu_id );
		if ( $items ) {
			$tree = kindi_build_nav_tree( $items );
			set_transient( $key, $tree, DAY_IN_SECONDS );
			return $tree;
		}
	}

	return kindi_default_nav();
}

/**
 * Flush the cached nav tree when any menu is edited.
 *
 * @param int $menu_id Menu ID.
 * @return void
 */
function kindi_flush_nav_cache( $menu_id = 0 ): void {
	if ( $menu_id ) {
		delete_transient( 'kindi_nav_tree_' . (int) $menu_id );
	}
	// Location assignment may change which menu is primary — clear broadly.
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['primary'] ) ) {
		delete_transient( 'kindi_nav_tree_' . (int) $locations['primary'] );
	}
}
add_action( 'wp_update_nav_menu', 'kindi_flush_nav_cache' );
add_action( 'wp_delete_nav_menu', 'kindi_flush_nav_cache' );

/**
 * Build the 3-level nav tree from flat WP menu items.
 *
 * @param array<int,WP_Post> $items Menu items.
 * @return array<int,array<string,mixed>>
 */
function kindi_build_nav_tree( array $items ): array {
	$children = array();
	foreach ( $items as $item ) {
		$children[ (int) $item->menu_item_parent ][] = $item;
	}

	$out = array();
	foreach ( ( $children[0] ?? array() ) as $top ) {
		$cols    = array();
		$feature = null;

		foreach ( ( $children[ $top->ID ] ?? array() ) as $col ) {
			$col_classes = is_array( $col->classes ) ? $col->classes : array();

			// A child marked as a feature (menu-item field or `kindi-feature`
			// class) becomes the promo card, not a column.
			$is_feature = '1' === get_post_meta( $col->ID, '_kindi_feature', true ) || in_array( 'kindi-feature', $col_classes, true );
			if ( $is_feature ) {
				$tone = (string) get_post_meta( $col->ID, '_kindi_tone', true );
				if ( ! in_array( $tone, array( 'red', 'navy', 'blue' ), true ) ) {
					$tone = 'red';
					foreach ( $col_classes as $class ) {
						if ( preg_match( '/^kindi-tone-(red|navy|blue)$/', $class, $mt ) ) {
							$tone = $mt[1];
						}
					}
				}
				$sub = (string) get_post_meta( $col->ID, '_kindi_subtitle', true );
				$feature = array(
					'title' => $col->title,
					'sub'   => '' !== $sub ? $sub : (string) $col->attr_title,
					'url'   => $col->url,
					'tone'  => $tone,
				);
				continue;
			}

			$links = array();
			foreach ( ( $children[ $col->ID ] ?? array() ) as $leaf ) {
				$links[] = array(
					'label' => $leaf->title,
					'url'   => $leaf->url,
				);
			}
			// No sub-categories: the column keeps an empty list and its own URL —
			// the templates then render the title ONCE as a clickable heading,
			// instead of duplicating the name as both heading and sub-item.
			$cols[] = array(
				'title' => $col->title,
				'url'   => (string) $col->url,
				'links' => $links,
			);
		}

		$classes = is_array( $top->classes ) ? $top->classes : array();

		// Icon priority: menu-item field → kindi-icon-* class → guess by name.
		$icon = kindi_guess_category_icon( (string) $top->title );
		foreach ( $classes as $class ) {
			if ( preg_match( '/^kindi-icon-([a-z0-9]+)$/', $class, $m ) ) {
				$icon = $m[1];
			}
		}
		$meta_icon = (string) get_post_meta( $top->ID, '_kindi_icon', true );
		if ( '' !== $meta_icon && 'auto' !== $meta_icon ) {
			$icon = $meta_icon;
		}

		$highlight = '1' === get_post_meta( $top->ID, '_kindi_highlight', true ) || in_array( 'highlight', $classes, true );

		$out[] = array(
			'label'     => $top->title,
			'url'       => $top->url,
			'icon'      => $icon,
			'highlight' => $highlight,
			'cols'      => $cols,
			'feature'   => $feature,
		);
	}

	return $out;
}

/**
 * Curated default navigation (used until a menu is assigned).
 *
 * @return array<int,array<string,mixed>>
 */
function kindi_default_nav(): array {
	$mk = static function ( array $cols ): array {
		$out = array();
		foreach ( $cols as $title => $labels ) {
			$links = array();
			foreach ( $labels as $label ) {
				$links[] = array(
					'label' => $label,
					'url'   => '#',
				);
			}
			$out[] = array(
				'title' => $title,
				'links' => $links,
			);
		}
		return $out;
	};

	$nav = array(
		array( 'label' => 'כל הקטגוריות', 'url' => '#', 'icon' => 'grid', 'highlight' => false, 'cols' => $mk( array(
			'פופולרי'        => array( 'חדש באתר', 'רבי מכר', 'מבצעי השבוע', 'מתחת ל-50 ₪' ),
			'לפי תחום'       => array( 'משחקי קופסה', 'יצירה', 'בובות', 'לגו ובנייה', 'פאזלים' ),
			'לפי גיל'        => array( '0–2', '3–5', '6–8', '9–12', 'נוער' ),
			'מותגים מובילים' => array( 'Hape', 'Melissa & Doug', 'Janod', 'Playmobil' ),
		) ) ),
		array( 'label' => 'משחקים ותעסוקה', 'url' => '#', 'icon' => 'dice', 'highlight' => false, 'cols' => $mk( array(
			'משחקי קופסה' => array( 'משפחתי', 'אסטרטגיה', 'מסיבה', 'דו-קרב' ),
			'תעסוקה'      => array( 'פאזלים', 'ספרי פעילות', 'מדבקות', 'חשיבה ולוגיקה' ),
			'STEM ולמידה' => array( 'מדע וניסויים', 'רובוטיקה', 'תכנות לילדים' ),
		) ) ),
		array( 'label' => 'חזרה לבית ספר', 'url' => '#', 'icon' => 'backpack', 'highlight' => false, 'cols' => $mk( array(
			'ילקוטים ותיקים' => array( "כיתה א'", 'כיתות ב-ג', 'חטיבה', 'קלמרים' ),
			'כלי כתיבה'      => array( 'עטים', 'עפרונות', 'טושים', 'סטים מתנה' ),
			'ציוד נלווה'     => array( 'מחברות', 'תיקיות', 'כיסויי ספרים' ),
		) ) ),
		array( 'label' => 'יצירה ואומנות', 'url' => '#', 'icon' => 'palette', 'highlight' => false, 'cols' => $mk( array(
			'ערכות יצירה' => array( 'לפי גיל', 'תכשיטים', 'ציור וצביעה', 'פיסול ובצק' ),
			'ציוד אומנות' => array( 'צבעי גואש', 'צבעי מים', 'פסטלים', 'ניירות' ),
		) ) ),
		array( 'label' => 'הכל לגננת ולגן', 'url' => '#', 'icon' => 'teddy', 'highlight' => false, 'cols' => $mk( array(
			'ציוד גן'  => array( 'שטיחי משחק', 'פינות יצירה', 'ארגונית גן' ),
			'משחקי גן' => array( 'בלוקים', 'פאזלי רצפה', 'מוטוריקה עדינה' ),
		) ) ),
		array( 'label' => 'מוצרי קיץ', 'url' => '#', 'icon' => 'sun', 'highlight' => false, 'cols' => $mk( array(
			'בריכות'  => array( 'בריכות מתנפחות', 'מזרני ים', 'משאבות' ),
			'ים וחוף' => array( 'צעצועי חול', 'מחבטים', 'כדורי ים' ),
		) ) ),
		array( 'label' => 'חגים', 'url' => '#', 'icon' => 'party', 'highlight' => false, 'cols' => $mk( array(
			'לפי חג'          => array( 'חנוכה', 'פורים', 'פסח', 'ראש השנה' ),
			'תחפושות ואביזרים' => array( 'תחפושות בנים', 'תחפושות בנות', 'איפור ושיער' ),
		) ) ),
		array( 'label' => 'מותגים', 'url' => '#', 'icon' => 'gem', 'highlight' => false, 'cols' => $mk( array(
			'פרימיום' => array( 'Hape', 'Janod', 'Plan Toys' ),
			'אספנים'  => array( 'LEGO', 'Schleich', 'Playmobil' ),
		) ) ),
		array( 'label' => 'מבצעים חמים', 'url' => '#', 'icon' => 'fire', 'highlight' => true, 'cols' => array() ),
	);

	// Example feature cards on a few items (matches the reference design).
	$nav[0]['feature'] = array( 'title' => 'מבצעי סוף שבוע', 'sub' => 'עד 40% הנחה על מאות פריטים', 'url' => '#', 'tone' => 'red' );
	$nav[1]['feature'] = array( 'title' => 'פאזלים 1 + 1', 'sub' => 'מאות דגמים בסטוק', 'url' => '#', 'tone' => 'blue' );
	$nav[2]['feature'] = array( 'title' => 'ילקוטים 2026', 'sub' => 'כל המותגים, משלוח חינם', 'url' => '#', 'tone' => 'navy' );
	$nav[5]['feature'] = array( 'title' => 'קיץ 2026', 'sub' => 'הכל לחופש הגדול', 'url' => '#', 'tone' => 'red' );

	return $nav;
}
