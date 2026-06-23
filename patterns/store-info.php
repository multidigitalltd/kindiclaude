<?php
/**
 * Title: Store Info + Newsletter
 * Slug: kindi/store-info
 * Categories: kindi
 * Description: פרטי החנות הפיזית והרשמה לניוזלטר.
 *
 * @package Kindi
 */

$kindi_details = array(
	array( 'icon' => 'pin', 'bold' => 'הרב יעקב לנדא 7, בני ברק', 'sub' => '' ),
	array( 'icon' => 'phone', 'bold' => '03-5293383', 'sub' => 'שירות אישי וייעוץ' ),
	array( 'icon' => 'clock', 'bold' => 'א\'-ה\' 9:00-21:00 • ו\' 9:00-14:00', 'sub' => '' ),
	array( 'icon' => 'car', 'bold' => 'איסוף עצמי חינם', 'sub' => 'בהזמנה מראש' ),
);
?>
<!-- wp:html -->
<section class="kindi-section kindi-store">

	<div class="kindi-store__card">
		<span class="kindi-zone__blob kindi-zone__blob--2" aria-hidden="true"></span>
		<div class="kindi-store__body">
			<span class="kindi-store__badge"><?php echo kindi_icon( 'pin', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>בקרו אותנו</span>
			<h2 class="kindi-store__title">בואו לחנות שלנו<br><mark>בבני ברק</mark></h2>
			<p class="kindi-store__lead">חוויית קנייה אמיתית, שירות אישי וייעוץ מקצועי — בחנות הפיזית שלנו תוכלו לראות, לגעת ולבחור מאלפי מוצרים.</p>
			<div class="kindi-store__details">
				<?php foreach ( $kindi_details as $d ) : ?>
				<div class="kindi-store__detail">
					<span class="kindi-store__detail-ic"><?php echo kindi_icon( $d['icon'], 'kindi-icon--lg kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span><strong><?php echo esc_html( $d['bold'] ); ?></strong><?php echo $d['sub'] ? ' • ' . esc_html( $d['sub'] ) : ''; ?></span>
				</div>
				<?php endforeach; ?>
			</div>
			<a class="kindi-store__cta" href="https://waze.com/ul?q=הרב%20יעקב%20לנדא%207%20בני%20ברק" target="_blank" rel="noopener">ניווט בוויז<?php echo kindi_icon( 'arrowleft', 'kindi-icon--md kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>
		<div class="kindi-store__media">
			<span class="kindi-ground-shadow animate-ground"></span>
			<img class="kindi-store__mascot animate-mascot" src="<?php echo kindi_img( 'mascot/point.webp' ); ?>" alt="" loading="lazy">
			<span class="kindi-store__here"><?php echo kindi_icon( 'pin', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>כאן אנחנו!</span>
		</div>
	</div>

	<div class="kindi-news">
		<div class="kindi-news__body">
			<span class="kindi-news__badge"><?php echo kindi_icon( 'gift', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>מתנת הצטרפות</span>
			<h3 class="kindi-news__title">קבלו 10% הנחה על הזמנה ראשונה!</h3>
			<p class="kindi-news__sub">הירשמו לניוזלטר וקבלו עדכונים על מבצעים, מוצרים חדשים והטבות בלעדיות.</p>
		</div>
		<form class="kindi-news__form" action="#" method="post" novalidate>
			<label class="kindi-news__field">
				<span class="screen-reader-text">כתובת אימייל</span>
				<?php echo kindi_icon( 'mail', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<input type="email" name="kindi_newsletter_email" required placeholder="האימייל שלכם...">
			</label>
			<button type="submit" class="kindi-news__btn">קבלו הנחה<?php echo kindi_icon( 'gift', 'kindi-icon--md kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</form>
	</div>

</section>
<!-- /wp:html -->
