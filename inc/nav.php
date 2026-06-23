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
			'primary' => __( 'תפריט ראשי (קינדי)', 'kindi' ),
		)
	);
}
add_action( 'after_setup_theme', 'kindi_register_menus' );

/**
 * Normalised navigation items (from the assigned menu, or the default).
 *
 * @return array<int,array{label:string,url:string,icon:string,highlight:bool,cols:array<int,array{title:string,links:array<int,array{label:string,url:string}>}>}>
 */
function kindi_nav_items(): array {
	$locations = get_nav_menu_locations();

	if ( ! empty( $locations['primary'] ) ) {
		$items = wp_get_nav_menu_items( $locations['primary'] );
		if ( $items ) {
			return kindi_build_nav_tree( $items );
		}
	}

	return kindi_default_nav();
}

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
		$cols = array();
		foreach ( ( $children[ $top->ID ] ?? array() ) as $col ) {
			$links = array();
			foreach ( ( $children[ $col->ID ] ?? array() ) as $leaf ) {
				$links[] = array(
					'label' => $leaf->title,
					'url'   => $leaf->url,
				);
			}
			if ( ! $links ) {
				$links[] = array(
					'label' => $col->title,
					'url'   => $col->url,
				);
			}
			$cols[] = array(
				'title' => $col->title,
				'links' => $links,
			);
		}

		$classes = is_array( $top->classes ) ? $top->classes : array();
		$icon    = 'grid';
		foreach ( $classes as $class ) {
			if ( preg_match( '/^kindi-icon-([a-z0-9]+)$/', $class, $m ) ) {
				$icon = $m[1];
			}
		}

		$out[] = array(
			'label'     => $top->title,
			'url'       => $top->url,
			'icon'      => $icon,
			'highlight' => in_array( 'highlight', $classes, true ),
			'cols'      => $cols,
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

	return array(
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
}
