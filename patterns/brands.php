<?php
/**
 * Title: Brands Strip
 * Slug: kindi/brands
 * Categories: kindi
 * Description: רצועת מותגים מובילים.
 *
 * @package Kindi
 */

$kindi_brands = array( 'LEGO®', 'PLAYMOBIL', 'לי גיא', 'קל-גב', 'סטיץ׳', 'פאו פטרול', 'דורה', 'גבי הבובה', 'מודן', 'NICI', 'פלפוט', 'SMASH' );
?>
<!-- wp:html -->
<section class="kindi-section">
	<?php echo kindi_section_head( array( // phpcs:ignore WordPress.Security.EscapeOutput
		'eyebrow'   => 'מותגים אהובים',
		'title'     => 'רק',
		'highlight' => 'המקוריים',
		'suffix'    => 'והאיכותיים',
	) ); ?>
	<div class="kindi-brands">
		<div class="kindi-brands__grid">
			<?php foreach ( $kindi_brands as $b ) : ?>
			<div class="kindi-brand"><?php echo esc_html( $b ); ?></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<!-- /wp:html -->
