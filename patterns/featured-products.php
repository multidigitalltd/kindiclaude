<?php
/**
 * Title: Featured Products
 * Slug: kindi/featured-products
 * Categories: kindi
 * Description: גריד מוצרים נבחרים מ-WooCommerce.
 *
 * @package Kindi
 */

?>
<!-- wp:html -->
<section class="kindi-section">
	<?php echo kindi_section_head( array( // phpcs:ignore WordPress.Security.EscapeOutput
		'eyebrow'   => 'נבחרים בשבילכם',
		'title'     => 'המוצרים',
		'highlight' => 'החמים שלנו',
	) ); ?>
	<?php echo do_shortcode( '[kindi_hot_products]' ); // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce/shortcode output. ?>
</section>
<!-- /wp:html -->
