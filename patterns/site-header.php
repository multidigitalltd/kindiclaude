<?php
/**
 * Title: Site Header
 * Slug: kindi/site-header
 * Categories: kindi
 * Inserter: false
 * Description: ראש האתר — רצועת מבצעים, לוגו, חיפוש, סל ותפריט קטגוריות.
 *
 * @package Kindi
 */

// Marquee announcement items.
$kindi_ticker = array(
	array( 'icon' => 'truck', 'text' => 'משלוח חינם בהזמנה מעל 299 ₪' ),
	array( 'icon' => 'gift', 'text' => 'מועדון קינדי — 5% חזרה על כל קנייה' ),
	array( 'icon' => 'sparkles', 'text' => 'קולקציית חזרה לבית הספר 2026 נחתה' ),
	array( 'icon' => 'shield', 'text' => 'תשלום מאובטח SSL + PCI' ),
	array( 'icon' => 'phone', 'text' => 'שירות אישי 03-5293383' ),
);

// Top-level category navigation (mega-menu columns trimmed to lead items).
$kindi_nav = array(
	array( 'label' => 'כל הקטגוריות', 'icon' => 'grid', 'cols' => array(
		'פופולרי'        => array( 'חדש באתר', 'רבי מכר', 'מבצעי השבוע', 'מתחת ל-50 ₪' ),
		'לפי תחום'       => array( 'משחקי קופסה', 'יצירה', 'בובות', 'לגו ובנייה', 'פאזלים' ),
		'לפי גיל'        => array( '0–2', '3–5', '6–8', '9–12', 'נוער' ),
		'מותגים מובילים' => array( 'Hape', 'Melissa & Doug', 'Janod', 'Playmobil' ),
	) ),
	array( 'label' => 'משחקים ותעסוקה', 'icon' => 'dice', 'cols' => array(
		'משחקי קופסה' => array( 'משפחתי', 'אסטרטגיה', 'מסיבה', 'דו-קרב' ),
		'תעסוקה'      => array( 'פאזלים', 'ספרי פעילות', 'מדבקות', 'חשיבה ולוגיקה' ),
		'STEM ולמידה' => array( 'מדע וניסויים', 'רובוטיקה', 'תכנות לילדים' ),
	) ),
	array( 'label' => 'חזרה לבית ספר', 'icon' => 'backpack', 'cols' => array(
		'ילקוטים ותיקים' => array( 'כיתה א\'', 'כיתות ב-ג', 'חטיבה', 'קלמרים' ),
		'כלי כתיבה'      => array( 'עטים', 'עפרונות', 'טושים', 'סטים מתנה' ),
		'ציוד נלווה'     => array( 'מחברות', 'תיקיות', 'כיסויי ספרים' ),
	) ),
	array( 'label' => 'יצירה ואומנות', 'icon' => 'palette', 'cols' => array(
		'ערכות יצירה' => array( 'לפי גיל', 'תכשיטים', 'ציור וצביעה', 'פיסול ובצק' ),
		'ציוד אומנות' => array( 'צבעי גואש', 'צבעי מים', 'פסטלים', 'ניירות' ),
	) ),
	array( 'label' => 'הכל לגננת ולגן', 'icon' => 'teddy', 'cols' => array(
		'ציוד גן'   => array( 'שטיחי משחק', 'פינות יצירה', 'ארגונית גן' ),
		'משחקי גן'  => array( 'בלוקים', 'פאזלי רצפה', 'מוטוריקה עדינה' ),
	) ),
	array( 'label' => 'משחקי חצר וגינה', 'icon' => 'ball', 'cols' => array(
		'ספורט וכדורים' => array( 'כדורגל', 'כדורסל', 'טניס וחבטות' ),
		'אופניים ורכיבה' => array( 'אופני איזון', 'קורקינטים', 'קסדות' ),
		'חצר'           => array( 'טרמפולינות', 'אוהלים', 'משחקי דשא' ),
	) ),
	array( 'label' => 'מוצרי קיץ', 'icon' => 'sun', 'cols' => array(
		'בריכות' => array( 'בריכות מתנפחות', 'מזרני ים', 'משאבות' ),
		'ים וחוף' => array( 'צעצועי חול', 'מחבטים', 'כדורי ים' ),
	) ),
	array( 'label' => 'חגים', 'icon' => 'party', 'cols' => array(
		'לפי חג'          => array( 'חנוכה', 'פורים', 'פסח', 'ראש השנה' ),
		'תחפושות ואביזרים' => array( 'תחפושות בנים', 'תחפושות בנות', 'איפור ושיער' ),
	) ),
	array( 'label' => 'לפי גילאים', 'icon' => 'baby', 'cols' => array(
		'תינוקות 0–2' => array( 'נשכנים', 'ניידים', 'ספרי בד' ),
		'גיל הרך 3–5' => array( 'בנייה', 'תפקידים', 'מוטוריקה' ),
		'ילדים 6–8'   => array( 'משחקי חשיבה', 'STEM', 'פאזלים גדולים' ),
	) ),
	array( 'label' => 'מותגים', 'icon' => 'gem', 'cols' => array(
		'פרימיום' => array( 'Hape', 'Janod', 'Plan Toys' ),
		'אספנים'  => array( 'LEGO', 'Schleich', 'Playmobil' ),
	) ),
	array( 'label' => 'מבצעים חמים', 'icon' => 'fire', 'highlight' => true ),
);

$kindi_ticker_loop = array_merge( $kindi_ticker, $kindi_ticker );
?>
<!-- wp:html -->
<div class="kindi-topbar">
	<div class="kindi-topbar__track animate-marquee">
		<?php foreach ( $kindi_ticker_loop as $t ) : ?>
		<span class="kindi-topbar__item"><?php echo kindi_icon( $t['icon'], 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $t['text'] ); ?></span>
		<?php endforeach; ?>
	</div>
</div>

<div class="kindi-shipbar"><?php echo kindi_icon( 'truck', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> משלוח מהיר חינם מעל 299 ₪ | מועדון הלקוחות — 10% הנחה על הקנייה הראשונה</div>

<div class="kindi-bar">
	<button class="kindi-bar__burger" type="button" aria-label="פתח תפריט" data-kindi-menu-open><?php echo kindi_icon( 'menu', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>

	<a class="kindi-bar__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="קינדר טויס — לעמוד הבית">
		<img src="<?php echo kindi_img( 'logo.png' ); ?>" alt="קינדר טויס" width="160" height="56">
	</a>

	<form class="kindi-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="kindi-q">חיפוש באתר</label>
		<?php echo kindi_icon( 'search', 'kindi-icon--md kindi-search__ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input id="kindi-q" type="search" name="s" placeholder="חפשו משחקים, מותגים או קטגוריות…" autocomplete="off">
		<?php if ( class_exists( 'WooCommerce' ) ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
		<button type="submit" class="kindi-search__btn"><?php echo kindi_icon( 'sparkles', 'kindi-icon--xs kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>חיפוש</button>
	</form>

	<div class="kindi-bar__actions">
		<a class="kindi-bar__util" href="<?php echo esc_url( wp_login_url() ); ?>"><?php echo kindi_icon( 'user', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>התחברות</span></a>
		<a class="kindi-bar__util" href="#"><?php echo kindi_icon( 'heart', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>מועדפים</span></a>
		<a class="kindi-cart" href="<?php echo esc_url( class_exists( 'WooCommerce' ) && wc_get_cart_url() ? wc_get_cart_url() : '#' ); ?>" aria-label="סל קניות">
			<span class="kindi-cart__txt"><small>סל הקניות</small><b><?php echo class_exists( 'WooCommerce' ) ? esc_html( WC()->cart->get_cart_contents_count() . ' פריטים' ) : '0 פריטים'; ?></b></span>
			<span class="kindi-cart__ic"><?php echo kindi_icon( 'cart', 'kindi-icon--md kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="kindi-cart__badge"><?php echo class_exists( 'WooCommerce' ) ? absint( WC()->cart->get_cart_contents_count() ) : 0; ?></span></span>
		</a>
	</div>
</div>

<nav class="kindi-nav" aria-label="קטגוריות">
	<div class="kindi-nav__inner">
		<?php foreach ( $kindi_nav as $c ) :
			$is_hl   = ! empty( $c['highlight'] );
			$has_col = ! empty( $c['cols'] );
			?>
		<div class="kindi-nav__item<?php echo $is_hl ? ' kindi-nav__item--hl' : ''; ?>">
			<a class="kindi-nav__link" href="#"><span class="kindi-nav__ic"><?php echo kindi_icon( $c['icon'], 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php echo esc_html( $c['label'] ); ?><?php echo $has_col ? kindi_icon( 'chevrondown', 'kindi-icon--xs' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php if ( $has_col ) : ?>
			<div class="kindi-mega">
				<div class="kindi-mega__cols">
					<?php foreach ( $c['cols'] as $title => $items ) : ?>
					<div class="kindi-mega__col">
						<h4 class="kindi-mega__title"><?php echo kindi_icon( 'sparkles', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $title ); ?></h4>
						<ul>
							<?php foreach ( $items as $it ) : ?>
							<li><a href="#"><?php echo esc_html( $it ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
</nav>

<!-- Mobile drawer -->
<div class="kindi-drawer" data-kindi-drawer hidden>
	<div class="kindi-drawer__overlay" data-kindi-menu-close></div>
	<div class="kindi-drawer__panel" role="dialog" aria-modal="true" aria-label="תפריט קטגוריות">
		<div class="kindi-drawer__head">
			<span class="kindi-drawer__title"><img src="<?php echo kindi_img( 'mascot/wave.webp' ); ?>" alt="" width="40" height="40">תפריט קטגוריות</span>
			<button type="button" class="kindi-drawer__close" aria-label="סגור" data-kindi-menu-close><?php echo kindi_icon( 'close', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
		<div class="kindi-drawer__body">
			<?php foreach ( $kindi_nav as $c ) : ?>
			<a class="kindi-drawer__link<?php echo ! empty( $c['highlight'] ) ? ' kindi-drawer__link--hl' : ''; ?>" href="#"><?php echo kindi_icon( $c['icon'], 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<!-- /wp:html -->
