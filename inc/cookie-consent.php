<?php
/**
 * Cookie consent notice.
 *
 * A lightweight, accessible, dismissible banner. Consent is stored client-side
 * (localStorage) so there is no extra request or server state; the tiny reveal
 * script is inlined to avoid an HTTP request. Text + policy link are editable
 * from the control panel; the whole banner can be switched off there too.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Render the consent banner in the footer (hidden until JS reveals it).
 *
 * @return void
 */
function kindi_cookie_banner(): void {
	if ( '0' === (string) kindi_opt( 'cookie_enable', '1' ) ) {
		return;
	}

	$text   = (string) kindi_opt( 'cookie_text' );
	$btn    = (string) kindi_opt( 'cookie_btn' );
	$url    = (string) kindi_opt( 'cookie_policy_url' );
	$ptext  = (string) kindi_opt( 'cookie_policy_text' );
	?>
	<div class="kindi-cookie" data-kindi-cookie hidden role="region" aria-label="<?php esc_attr_e( 'הודעה על שימוש בעוגיות', 'kindi' ); ?>">
		<p class="kindi-cookie__txt">
			<?php echo esc_html( $text ); ?>
			<?php if ( '' !== $url && '' !== $ptext ) : ?>
				<a class="kindi-cookie__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $ptext ); ?></a>
			<?php endif; ?>
		</p>
		<button type="button" class="kindi-btn kindi-btn--red kindi-cookie__ok" data-kindi-cookie-ok><?php echo esc_html( $btn ); ?></button>
	</div>
	<script>
	(function(){try{var K='kindi_cookie_ok',b=document.querySelector('[data-kindi-cookie]');if(!b)return;if(!localStorage.getItem(K)){b.hidden=false;}var o=b.querySelector('[data-kindi-cookie-ok]');if(o){o.addEventListener('click',function(){try{localStorage.setItem(K,'1');}catch(e){}b.hidden=true;});}}catch(e){}})();
	</script>
	<?php
}
add_action( 'wp_footer', 'kindi_cookie_banner', 30 );
