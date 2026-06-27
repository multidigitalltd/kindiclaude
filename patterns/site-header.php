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

// Marquee announcement items (texts editable in קינדי settings; icons cycle).
$kindi_ticker_icons = array( 'truck', 'gift', 'sparkles', 'shield', 'phone' );
$kindi_ticker       = array();
foreach ( kindi_opt_lines( 'ticker' ) as $i => $kindi_txt ) {
	$kindi_ticker[] = array(
		'icon' => $kindi_ticker_icons[ $i % count( $kindi_ticker_icons ) ],
		'text' => $kindi_txt,
	);
}

// Top-level navigation — from the assigned WP menu, or the curated default.
$kindi_nav = kindi_nav_items();

?>
<!-- wp:html -->
<div class="kindi-topbar">
	<div class="kindi-topbar__track">
		<?php foreach ( $kindi_ticker as $t ) : ?>
		<span class="kindi-topbar__item"><?php echo kindi_icon( $t['icon'], 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $t['text'] ); ?></span>
		<?php endforeach; ?>
	</div>
</div>

<div class="kindi-shipbar"><?php echo kindi_icon( 'truck', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( kindi_opt( 'shipbar' ) ); ?></div>

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
		<?php
		$kindi_account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
		$kindi_account = $kindi_account ? $kindi_account : wp_login_url();
		?>
		<a class="kindi-bar__util" href="<?php echo esc_url( $kindi_account ); ?>"><?php echo kindi_icon( 'user', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( is_user_logged_in() ? 'החשבון שלי' : 'התחברות' ); ?></span></a>
		<a class="kindi-bar__util kindi-wish-link" href="<?php echo esc_url( home_url( '/wishlist/' ) ); ?>"><span class="kindi-wish-ic"><?php echo kindi_icon( 'heart', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="kindi-wish-badge" data-kindi-wish-count hidden>0</span></span><span>מועדפים</span></a>
		<a class="kindi-cart" href="<?php echo esc_url( class_exists( 'WooCommerce' ) && wc_get_cart_url() ? wc_get_cart_url() : '#' ); ?>" aria-label="סל קניות">
			<span class="kindi-cart__txt"><small>סל הקניות</small><b class="kindi-cart-amount"><?php echo class_exists( 'WooCommerce' ) ? wp_kses_post( WC()->cart->get_cart_contents_count() . ' פריטים • ' . WC()->cart->get_cart_subtotal() ) : '0 פריטים'; ?></b></span>
			<span class="kindi-cart__ic"><?php echo kindi_icon( 'cart', 'kindi-icon--md kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="kindi-cart__badge kindi-cart-count"><?php echo class_exists( 'WooCommerce' ) ? absint( WC()->cart->get_cart_contents_count() ) : 0; ?></span></span>
		</a>
	</div>
</div>

<div class="kindi-msearch">
	<form class="kindi-search kindi-search--mobile" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="kindi-mq">חיפוש באתר</label>
		<?php echo kindi_icon( 'search', 'kindi-icon--md kindi-search__ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<input id="kindi-mq" type="search" name="s" placeholder="חפשו מוצרים…" autocomplete="off">
		<?php if ( class_exists( 'WooCommerce' ) ) : ?><input type="hidden" name="post_type" value="product"><?php endif; ?>
	</form>
</div>

<nav class="kindi-nav" aria-label="קטגוריות">
	<div class="kindi-nav__inner">
		<?php foreach ( $kindi_nav as $c ) :
			$is_hl   = ! empty( $c['highlight'] );
			$has_col = ! empty( $c['cols'] );
			?>
		<div class="kindi-nav__item<?php echo $is_hl ? ' kindi-nav__item--hl' : ''; ?>">
			<a class="kindi-nav__link" href="<?php echo esc_url( $c['url'] ); ?>"><span class="kindi-nav__ic"><?php echo kindi_icon( $c['icon'], 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php echo esc_html( $c['label'] ); ?><?php echo $has_col ? kindi_icon( 'chevrondown', 'kindi-icon--xs' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<?php if ( $has_col ) : ?>
			<div class="kindi-mega">
				<div class="kindi-mega__inner">
					<div class="kindi-mega__cols">
						<?php foreach ( $c['cols'] as $col ) : ?>
						<div class="kindi-mega__col">
							<h4 class="kindi-mega__title"><?php echo kindi_icon( 'sparkles', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $col['title'] ); ?></h4>
							<ul>
								<?php foreach ( $col['links'] as $link ) : ?>
								<li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<?php endforeach; ?>
					</div>
					<?php if ( ! empty( $c['feature'] ) ) : $kindi_f = $c['feature']; ?>
					<a class="kindi-mega__feature kindi-mega__feature--<?php echo esc_attr( $kindi_f['tone'] ); ?>" href="<?php echo esc_url( $kindi_f['url'] ); ?>">
						<span class="kindi-mega__feature-eyebrow">מומלץ עכשיו</span>
						<span class="kindi-mega__feature-title"><?php echo esc_html( $kindi_f['title'] ); ?></span>
						<?php if ( $kindi_f['sub'] ) : ?><span class="kindi-mega__feature-sub"><?php echo esc_html( $kindi_f['sub'] ); ?></span><?php endif; ?>
						<span class="kindi-mega__feature-cta">לצפייה<?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<img class="kindi-mega__feature-img" src="<?php echo kindi_img( 'mascot/wave.webp' ); ?>" alt="" loading="lazy" decoding="async">
					</a>
					<?php endif; ?>
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
			<?php
			foreach ( $kindi_nav as $c ) :
				if ( empty( $c['cols'] ) ) :
					?>
			<a class="kindi-drawer__link<?php echo ! empty( $c['highlight'] ) ? ' kindi-drawer__link--hl' : ''; ?>" href="<?php echo esc_url( $c['url'] ); ?>"><?php echo kindi_icon( $c['icon'], 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c['label'] ); ?></a>
					<?php else : ?>
			<details class="kindi-drawer__acc">
				<summary class="kindi-drawer__link"><?php echo kindi_icon( $c['icon'], 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $c['label'] ); ?></span></summary>
				<div class="kindi-drawer__sub">
					<?php foreach ( $c['cols'] as $col ) : ?>
					<div class="kindi-drawer__coltitle"><?php echo esc_html( $col['title'] ); ?></div>
						<?php foreach ( $col['links'] as $link ) : ?>
					<a class="kindi-drawer__sublink" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
			</details>
					<?php
				endif;
			endforeach;
			?>
		</div>
	</div>
</div>
<!-- /wp:html -->
