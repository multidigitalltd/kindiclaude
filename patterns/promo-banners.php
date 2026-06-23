<?php
/**
 * Title: Promo Banners
 * Slug: kindi/promo-banners
 * Categories: kindi
 * Description: באנרי מבצע — חזרה לבית ספר, גמבוי, לגו.
 *
 * @package Kindi
 */

?>
<!-- wp:html -->
<section class="kindi-section">
	<?php echo kindi_section_head( array( // phpcs:ignore WordPress.Security.EscapeOutput
		'eyebrow'   => 'מבצעים חמים',
		'title'     => 'המבצעים',
		'highlight' => 'של קינדי',
	) ); ?>
	<div class="kindi-promos">

		<a class="kindi-promo kindi-promo--red kindi-promo--big" href="/shop/?on_sale=1">
			<img class="kindi-promo__img" src="<?php echo kindi_img( 'photos/promo-back-to-school.jpg' ); ?>" alt="" loading="lazy">
			<span class="kindi-promo__body">
				<span class="kindi-promo__badge"><?php echo kindi_icon( 'clock', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>מוגבל בזמן</span>
				<h3 class="kindi-promo__title">חזרה לבית הספר<br><span>עד 40%- על הכל!</span></h3>
				<p class="kindi-promo__sub">ילקוטים, קלמרים, מחברות, עפרונות ועוד מאות מוצרים במחירי השקה</p>
				<span class="kindi-promo__cta">לכל המבצעים<?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</span>
		</a>

		<a class="kindi-promo kindi-promo--navy kindi-promo--sm" href="/shop/">
			<img class="kindi-promo__img" src="<?php echo kindi_img( 'photos/promo-gameboy.jpg' ); ?>" alt="" loading="lazy">
			<span class="kindi-promo__body">
				<span class="kindi-promo__badge"><?php echo kindi_icon( 'sparkles', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>חדש בקינדי</span>
				<h3 class="kindi-promo__title">גמבוי כשר X3</h3>
				<p class="kindi-promo__sub">222 משחקים</p>
				<span class="kindi-promo__cta">לרכישה<?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</span>
		</a>

		<a class="kindi-promo kindi-promo--blue kindi-promo--sm" href="/shop/">
			<img class="kindi-promo__img" src="<?php echo kindi_img( 'photos/promo-blocks.jpg' ); ?>" alt="" loading="lazy">
			<span class="kindi-promo__body">
				<span class="kindi-promo__badge"><?php echo kindi_icon( 'gem', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>מחיר מיוחד</span>
				<h3 class="kindi-promo__title">לגו בכל הסדרות</h3>
				<p class="kindi-promo__sub">החל מ-19.90 ₪</p>
				<span class="kindi-promo__cta">לקולקציה<?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</span>
		</a>

	</div>
</section>
<!-- /wp:html -->
