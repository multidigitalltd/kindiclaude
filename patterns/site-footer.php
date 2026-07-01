<?php
/**
 * Title: Site Footer
 * Slug: kindi/site-footer
 * Categories: kindi
 * Inserter: false
 * Description: כותרת תחתונה — קישורים, יצירת קשר ואמצעי תשלום.
 *
 * @package Kindi
 */

// Footer link columns. Each column shows its assigned WordPress menu when set
// (Appearance → Menus), otherwise these default links. Titles + defaults follow
// the store's footer; edit the links from the dashboard, no code needed.
$kindi_foot_menus = array(
	array(
		'title'    => 'ראשי',
		'location' => 'footer-main',
		'links'    => array(
			'הסיפור שלנו'       => home_url( '/about/' ),
			'שאלות ותשובות'     => home_url( '/faq/' ),
			'לקוחות מספרים'     => home_url( '/reviews/' ),
			'מועדון לקוחות'     => home_url( '/my-account/' ),
			'תקנון האתר'        => home_url( '/terms/' ),
			'ביטול עסקה'        => home_url( '/refund_returns/' ),
			'משלוחים והחזרות'   => home_url( '/shipping/' ),
			'מדיניות פרטיות'    => home_url( '/privacy-policy/' ),
			'הצהרת נגישות'      => home_url( '/accessibility-statement/' ),
			'הבלוג שלנו'        => home_url( '/blog/' ),
			'יצירת קשר'         => home_url( '/contact/' ),
		),
	),
	array(
		'title'    => 'גננות ומוסדות',
		'location' => 'footer-institutions',
		'links'    => array(
			'התחבר / הרשם' => home_url( '/my-account/' ),
			'משאלות'       => home_url( '/wishlist/' ),
		),
	),
	array(
		'title'    => 'חדשות ועדכונים',
		'location' => 'footer-news',
		'links'    => array(
			'הבלוג שלנו'          => home_url( '/blog/' ),
			'המבצעים החמים'       => home_url( '/sale/' ),
			'החדשים והמומלצים'    => home_url( '/new/' ),
			'gift card'           => home_url( '/gift-card/' ),
		),
	),
);
$kindi_pay = array( 'VISA', 'MC', 'ISRACARD', 'PayPal', 'Bit' );
?>
<!-- wp:html -->
<div class="kindi-footer__inner">
	<div class="kindi-footer__grid">

		<div class="kindi-footer__brand">
			<img class="kindi-footer__logo" src="<?php echo kindi_img( 'logo.webp' ); ?>" alt="קינדר טויס" width="109" height="80">
			<p><?php echo esc_html( kindi_opt( 'about' ) ); ?></p>
			<div class="kindi-footer__social">
				<a href="<?php echo esc_url( kindi_opt( 'fb' ) ); ?>" aria-label="פייסבוק" class="kindi-footer__soc">f</a>
				<a href="<?php echo esc_url( kindi_opt( 'ig' ) ); ?>" aria-label="אינסטגרם" class="kindi-footer__soc">◎</a>
				<a href="https://wa.me/<?php echo esc_attr( kindi_opt( 'whatsapp' ) ); ?>" aria-label="וואטסאפ" class="kindi-footer__soc"><?php echo kindi_icon( 'whatsapp', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</div>

		<?php foreach ( $kindi_foot_menus as $kindi_col ) : ?>
		<div class="kindi-footer__col">
			<h4><?php echo esc_html( $kindi_col['title'] ); ?></h4>
			<?php kindi_footer_menu( $kindi_col['location'], $kindi_col['links'] ); ?>
		</div>
		<?php endforeach; ?>

		<div class="kindi-footer__col kindi-footer__contact">
			<h4>צרו קשר</h4>
			<ul>
				<li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) kindi_opt( 'phone' ) ) ); ?>"><?php echo kindi_icon( 'phone', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( kindi_opt( 'phone' ) ); ?></a></li>
				<li><?php echo kindi_icon( 'mail', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( kindi_opt( 'email' ) ); ?></li>
				<li><?php echo kindi_icon( 'pin', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo esc_html( kindi_opt( 'store_address' ) ); ?></li>
				<li class="kindi-footer__hours"><?php echo esc_html( kindi_opt( 'store_hours' ) ); ?></li>
			</ul>
		</div>

	</div>

	<div class="kindi-footer__bottom">
		<div class="kindi-footer__copy">© 2026 קינדר טויס • כל הזכויות שמורות • <a href="/accessibility-statement">הצהרת נגישות</a></div>
		<div class="kindi-footer__pay">
			<span>תשלום מאובטח:</span>
			<?php foreach ( $kindi_pay as $p ) : ?>
			<span class="kindi-footer__paychip"><?php echo esc_html( $p ); ?></span>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<!-- /wp:html -->
