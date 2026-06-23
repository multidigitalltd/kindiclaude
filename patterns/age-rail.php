<?php
/**
 * Title: Age Rail
 * Slug: kindi/age-rail
 * Categories: kindi
 * Description: בחירת מוצרים לפי גיל.
 *
 * @package Kindi
 */

$kindi_ages = array(
	array( 'icon' => 'bottle', 'range' => '0-2', 'label' => 'תינוקות' ),
	array( 'icon' => 'teddy', 'range' => '3-5', 'label' => 'גיל הגן' ),
	array( 'icon' => 'backpack', 'range' => '6-8', 'label' => 'בית ספר יסודי' ),
	array( 'icon' => 'dice', 'range' => '9-12', 'label' => 'חטיבת ביניים' ),
	array( 'icon' => 'gamepad', 'range' => '+13', 'label' => 'נערים ונערות' ),
);
?>
<!-- wp:html -->
<section class="kindi-section">
	<?php echo kindi_section_head( array( // phpcs:ignore WordPress.Security.EscapeOutput
		'eyebrow'   => 'בוחרים לפי גיל',
		'title'     => 'למצוא את המתנה',
		'highlight' => 'המושלמת',
		'desc'      => 'סננו לפי גיל וקבלו המלצות מדויקות לכל ילד וילד',
	) ); ?>
	<div class="kindi-ages">
		<?php foreach ( $kindi_ages as $a ) : ?>
		<a class="kindi-age" href="#">
			<span class="kindi-age__ic"><?php echo kindi_icon( $a['icon'], 'kindi-icon--2xl' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="kindi-age__range"><?php echo esc_html( $a['range'] ); ?></span>
			<span class="kindi-age__label"><?php echo esc_html( $a['label'] ); ?></span>
		</a>
		<?php endforeach; ?>
	</div>
</section>
<!-- /wp:html -->
