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
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[products limit="10" columns="5" orderby="popularity" order="DESC" class="kindi-hot-products"]
<!-- /wp:shortcode -->
