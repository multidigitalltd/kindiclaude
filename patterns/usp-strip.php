<?php
/**
 * Title: USP Strip
 * Slug: kindi/usp-strip
 * Categories: kindi
 * Description: רצועת יתרונות — משלוח, החזרה, תשלום מאובטח, מועדון.
 *
 * @package Kindi
 */

$kindi_usps = array(
	array( 'icon' => 'truck', 'title' => 'משלוח חינם', 'sub' => 'בהזמנה מעל 299 ₪ (למעט ריהוט)' ),
	array( 'icon' => 'phone', 'title' => 'שירות אישי', 'sub' => 'ומקצועי לכל שאלה' ),
	array( 'icon' => 'shield', 'title' => 'תשלום מאובטח', 'sub' => 'SSL + אישור PCI' ),
	array( 'icon' => 'gift', 'title' => 'מועדון קינדי', 'sub' => 'נקודות, הנחות ומתנות' ),
);
?>
<!-- wp:html -->
<section class="kindi-usp" aria-label="יתרונות">
	<?php foreach ( $kindi_usps as $u ) : ?>
	<div class="kindi-usp__item">
		<span class="kindi-usp__ic"><?php echo kindi_icon( $u['icon'], 'kindi-icon--xl' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<span>
			<span class="kindi-usp__title"><?php echo esc_html( $u['title'] ); ?></span><br>
			<span class="kindi-usp__sub"><?php echo esc_html( $u['sub'] ); ?></span>
		</span>
	</div>
	<?php endforeach; ?>
</section>
<!-- /wp:html -->
