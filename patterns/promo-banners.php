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

		<a class="kindi-promo kindi-promo--red kindi-promo--big" href="<?php echo esc_url( kindi_opt( 'promo1_url' ) ); ?>">
			<img class="kindi-promo__img" src="<?php echo kindi_img( 'photos/promo-back-to-school.webp' ); ?>" alt="" loading="lazy" decoding="async">
			<span class="kindi-promo__body">
				<span class="kindi-promo__badge"><?php echo kindi_icon( 'clock', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( kindi_opt( 'promo1_badge' ) ); ?></span>
				<h3 class="kindi-promo__title"><?php echo esc_html( kindi_opt( 'promo1_title' ) ); ?></h3>
				<p class="kindi-promo__sub"><?php echo esc_html( kindi_opt( 'promo1_sub' ) ); ?></p>
				<span class="kindi-promo__cta"><?php echo esc_html( kindi_opt( 'promo1_cta' ) ); ?><?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</span>
		</a>

		<a class="kindi-promo kindi-promo--navy kindi-promo--sm" href="<?php echo esc_url( kindi_opt( 'promo2_url' ) ); ?>">
			<img class="kindi-promo__img" src="<?php echo kindi_img( 'photos/promo-gameboy.webp' ); ?>" alt="" loading="lazy" decoding="async">
			<span class="kindi-promo__body">
				<span class="kindi-promo__badge"><?php echo kindi_icon( 'sparkles', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( kindi_opt( 'promo2_badge' ) ); ?></span>
				<h3 class="kindi-promo__title"><?php echo esc_html( kindi_opt( 'promo2_title' ) ); ?></h3>
				<p class="kindi-promo__sub"><?php echo esc_html( kindi_opt( 'promo2_sub' ) ); ?></p>
				<span class="kindi-promo__cta"><?php echo esc_html( kindi_opt( 'promo2_cta' ) ); ?><?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</span>
		</a>

		<a class="kindi-promo kindi-promo--blue kindi-promo--sm" href="<?php echo esc_url( kindi_opt( 'promo3_url' ) ); ?>">
			<img class="kindi-promo__img" src="<?php echo kindi_img( 'photos/promo-blocks.webp' ); ?>" alt="" loading="lazy" decoding="async">
			<span class="kindi-promo__body">
				<span class="kindi-promo__badge"><?php echo kindi_icon( 'gem', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( kindi_opt( 'promo3_badge' ) ); ?></span>
				<h3 class="kindi-promo__title"><?php echo esc_html( kindi_opt( 'promo3_title' ) ); ?></h3>
				<p class="kindi-promo__sub"><?php echo esc_html( kindi_opt( 'promo3_sub' ) ); ?></p>
				<span class="kindi-promo__cta"><?php echo esc_html( kindi_opt( 'promo3_cta' ) ); ?><?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</span>
		</a>

	</div>
</section>
<!-- /wp:html -->
