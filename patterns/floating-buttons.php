<?php
/**
 * Title: Floating Buttons
 * Slug: kindi/floating-buttons
 * Categories: kindi
 * Description: כפתורי וואטסאפ ונגישות צפים.
 *
 * @package Kindi
 */

?>
<!-- wp:html -->
<a class="kindi-float kindi-float--wa animate-bob" href="https://wa.me/<?php echo esc_attr( kindi_opt( 'whatsapp' ) ); ?>" aria-label="צ'אט בוואטסאפ" target="_blank" rel="noopener"><?php echo kindi_icon( 'whatsapp', 'kindi-icon--3xl' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<button class="kindi-float kindi-float--a11y" type="button" aria-label="הגדרות נגישות" data-kindi-a11y><?php echo kindi_icon( 'accessibility', 'kindi-icon--md kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
<!-- /wp:html -->
