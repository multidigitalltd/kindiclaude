<?php
/**
 * Title: Kindy Club Zone
 * Slug: kindi/kindy-zone
 * Categories: kindi
 * Description: באנר מועדון קינדי עם הטבות ומסקוט.
 *
 * @package Kindi
 */

$kindi_benefits = kindi_opt_lines( 'club_benefits' );
$kindi_sparks   = array(
	array( 'icon' => 'star', 'style' => 'top:10%;right:10%;animation-delay:0s', 'cls' => 'kindi-icon--xl' ),
	array( 'icon' => 'sparkles', 'style' => 'top:30%;left:5%;animation-delay:0.5s', 'cls' => 'kindi-icon--2xl' ),
	array( 'icon' => 'star', 'style' => 'bottom:20%;right:5%;animation-delay:1s', 'cls' => 'kindi-icon--lg' ),
	array( 'icon' => 'sparkles', 'style' => 'bottom:10%;left:20%;animation-delay:1.5s', 'cls' => 'kindi-icon--xl' ),
);
?>
<!-- wp:html -->
<section class="kindi-section">
	<div class="kindi-zone">
		<span class="kindi-zone__blob kindi-zone__blob--1" aria-hidden="true"></span>
		<span class="kindi-zone__blob kindi-zone__blob--2" aria-hidden="true"></span>

		<div class="kindi-zone__body">
			<span class="kindi-zone__badge"><?php echo kindi_icon( 'gift', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>מועדון קינדי</span>
			<?php $kindi_club_hl = (string) kindi_opt( 'club_title_hl' ); ?>
			<h2 class="kindi-zone__title"><?php echo esc_html( kindi_opt( 'club_title' ) ); ?><?php echo '' !== $kindi_club_hl ? '<br><mark>' . esc_html( $kindi_club_hl ) . '</mark>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
			<p class="kindi-zone__lead"><?php echo esc_html( kindi_opt( 'club_lead' ) ); ?></p>
			<div class="kindi-zone__benefits">
				<?php foreach ( $kindi_benefits as $b ) : ?>
				<div class="kindi-zone__benefit"><span class="kindi-zone__check"><?php echo kindi_icon( 'check', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php echo esc_html( $b ); ?></div>
				<?php endforeach; ?>
			</div>
			<?php // kindi-club-trigger / #clubcta are the selectors registered in Simply Club's "toggle the authentication popup" setting; the href stays as a fallback when the plugin is off. ?>
			<a class="kindi-zone__cta kindi-club-trigger" id="clubcta" href="<?php echo esc_url( kindi_opt( 'club_cta_url' ) ); ?>">הצטרפות חינם — לחצו כאן<?php echo kindi_icon( 'arrowleft', 'kindi-icon--md kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>

		<div class="kindi-zone__stage" aria-hidden="true">
			<span class="kindi-zone__halo"></span>
			<span class="kindi-zone__disc"></span>
			<span class="kindi-ground-shadow animate-ground"></span>
			<?php echo kindi_mascot_img( 'kindyzone_mascot', 'mascot/kindy-celebrate-card.webp', 'kindi-zone__mascot animate-mascot', '(max-width: 767px) 70vw, 480px', '', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within. ?>
			<?php foreach ( $kindi_sparks as $s ) : ?>
			<span class="kindi-spark animate-sparkle" style="<?php echo esc_attr( $s['style'] ); ?>"><?php echo kindi_icon( $s['icon'], $s['cls'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<!-- /wp:html -->
