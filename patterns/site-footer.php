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

$kindi_foot_cols = array(
	'קטגוריות'     => array( 'צעצועים', 'משחקי קופסה', 'חזרה לבית הספר', 'יצירה ואומנות', 'תינוקות', 'משחקי יהדות' ),
	'שירות לקוחות' => array( 'צור קשר', 'משלוחים', 'החזרות', 'שאלות נפוצות', 'מעקב הזמנה', 'תקנון' ),
	'עלינו'        => array( 'אודות', 'מועדון קינדי', 'הסניף בבני ברק', 'לקוחות מספרים', 'בלוג' ),
);
$kindi_pay = array( 'VISA', 'MC', 'ISRACARD', 'PayPal', 'Bit' );
?>
<!-- wp:html -->
<div class="kindi-footer__inner">
	<div class="kindi-footer__grid">

		<div class="kindi-footer__brand">
			<img class="kindi-footer__logo" src="<?php echo kindi_img( 'logo.png' ); ?>" alt="קינדר טויס" width="160" height="80">
			<p>החנות המובילה לצעצועים, מכשירי כתיבה, חומרי יצירה וציוד לגני ילדים ובתי ספר. שירות אישי, מחירים הוגנים ואלפי לקוחות מרוצים.</p>
			<div class="kindi-footer__social">
				<a href="#" aria-label="פייסבוק" class="kindi-footer__soc">f</a>
				<a href="#" aria-label="אינסטגרם" class="kindi-footer__soc">◎</a>
				<a href="https://wa.me/972500000000" aria-label="וואטסאפ" class="kindi-footer__soc"><?php echo kindi_icon( 'whatsapp', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</div>

		<?php foreach ( $kindi_foot_cols as $title => $links ) : ?>
		<div class="kindi-footer__col">
			<h4><?php echo esc_html( $title ); ?></h4>
			<ul>
				<?php foreach ( $links as $l ) : ?>
				<li><a href="#"><?php echo esc_html( $l ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endforeach; ?>

		<div class="kindi-footer__col kindi-footer__contact">
			<h4>צרו קשר</h4>
			<ul>
				<li><a href="tel:035293383"><?php echo kindi_icon( 'phone', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> 03-5293383</a></li>
				<li><?php echo kindi_icon( 'mail', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> info@kindertoys.co.il</li>
				<li><?php echo kindi_icon( 'pin', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> הרב יעקב לנדא 7, בני ברק</li>
				<li class="kindi-footer__hours">א'-ה' 9:00-21:00<br>ו' 9:00-14:00</li>
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
