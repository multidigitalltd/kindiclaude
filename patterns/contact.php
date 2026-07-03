<?php
/**
 * Title: Contact Page
 * Slug: kindi/contact
 * Categories: kindi
 * Inserter: false
 * Description: עמוד "יצירת קשר" — פרטי החנות + טופס פנייה.
 *
 * @package Kindi
 */

$kindi_email   = (string) kindi_opt( 'email', 'office@kindertoys.co.il' );
$kindi_phone   = (string) kindi_opt( 'phone', '03-5293383' );
$kindi_tel     = preg_replace( '/[^0-9+]/', '', $kindi_phone );
$kindi_wa      = function_exists( 'kindi_whatsapp_url' ) ? kindi_whatsapp_url( 'היי, אשמח לעזרה 🙂' ) : '';

$kindi_cards = array(
	array( 'icon' => 'phone', 'label' => 'טלפון', 'value' => $kindi_phone . ' • שירות אישי וייעוץ', 'href' => 'tel:' . $kindi_tel ),
	array( 'icon' => 'mail',  'label' => 'אימייל', 'value' => $kindi_email, 'href' => 'mailto:' . $kindi_email ),
	array( 'icon' => 'pin',   'label' => 'כתובת החנות', 'value' => (string) kindi_opt( 'store_address' ), 'href' => (string) kindi_opt( 'store_waze' ) ),
	array( 'icon' => 'clock', 'label' => 'שעות פתיחה', 'value' => (string) kindi_opt( 'store_hours' ), 'href' => '' ),
);
if ( '' !== $kindi_wa ) {
	$kindi_cards[] = array( 'icon' => 'whatsapp', 'label' => 'וואטסאפ', 'value' => 'צוות השירות זמין לכל שאלה', 'href' => $kindi_wa );
}
?>
<!-- wp:html -->
<section class="kindi-contactpage">
	<header class="kindi-contactpage__head">
		<span class="kindi-eyebrow"><?php echo kindi_icon( 'phone', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>אנחנו כאן בשבילכם</span>
		<h1 class="kindi-contactpage__title">יצירת קשר</h1>
		<p class="kindi-contactpage__lead">יש שאלה על מוצר, הזמנה או משלוח? מלאו את הטופס או פנו אלינו ישירות — אנחנו עונים לכל פנייה, בדרך כלל בתוך יום עסקים אחד.</p>
	</header>

	<div class="kindi-contactpage__grid">
		<div class="kindi-about-contact kindi-contactpage__cards">
			<?php foreach ( $kindi_cards as $c ) : ?>
			<?php $kindi_tag = ! empty( $c['href'] ) ? 'a' : 'div'; ?>
			<<?php echo $kindi_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- 'a'/'div' literal. ?> class="kindi-about-contact__item"<?php echo ! empty( $c['href'] ) ? ' href="' . esc_url( $c['href'] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<span class="kindi-about-contact__t"><strong><?php echo esc_html( $c['label'] ); ?></strong><span><?php echo esc_html( $c['value'] ); ?></span></span>
				<span class="kindi-about-contact__ic"><?php echo kindi_icon( $c['icon'], 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</<?php echo $kindi_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php endforeach; ?>
		</div>

		<div class="kindi-contactpage__form">
			<?php echo do_shortcode( '[kindi_contact_form]' ); // phpcs:ignore WordPress.Security.EscapeOutput -- shortcode output is theme-built, escaped markup. ?>
		</div>
	</div>
</section>
<!-- /wp:html -->
