<?php
/**
 * Branded HTML email template + sender.
 *
 * One small, RTL, inline-styled wrapper used by every transactional email the
 * theme sends (back-in-stock, saved cart…), so they share a consistent design
 * while the copy stays editable from the control panel.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Wrap body HTML in the branded email shell.
 *
 * @param string                     $heading Top heading.
 * @param string                     $body    Inner HTML (already escaped/safe).
 * @param array{label:string,url:string}|null $cta Optional call-to-action button.
 * @return string
 */
function kindi_email_template( string $heading, string $body, ?array $cta = null ): string {
	$logo  = function_exists( 'kindi_img' ) ? kindi_img( 'logo.png' ) : '';
	$name  = get_bloginfo( 'name' );
	$red   = '#E63946';
	$navy  = '#1B2A52';
	$year  = esc_html( wp_date( 'Y' ) );

	$button = '';
	if ( $cta && ! empty( $cta['url'] ) ) {
		$button = '<tr><td style="padding:8px 0 4px">'
			. '<a href="' . esc_url( $cta['url'] ) . '" style="display:inline-block;background:' . $red . ';color:#fff;text-decoration:none;font-weight:700;padding:13px 28px;border-radius:14px;font-size:16px">'
			. esc_html( $cta['label'] ) . '</a></td></tr>';
	}

	$logo_html = $logo
		? '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $name ) . '" height="44" style="height:44px;width:auto">'
		: '<span style="font-size:22px;font-weight:800;color:#fff">' . esc_html( $name ) . '</span>';

	return '<!DOCTYPE html><html dir="rtl" lang="he"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
		. '<body style="margin:0;background:#f3f5f9;font-family:Arial,Helvetica,sans-serif;color:' . $navy . '">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5f9;padding:24px 12px"><tr><td align="center">'
		. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 8px 30px rgba(20,35,63,.08)">'
		. '<tr><td style="background:linear-gradient(135deg,' . $navy . ',' . $red . ');padding:22px 28px" align="right">' . $logo_html . '</td></tr>'
		. '<tr><td style="padding:32px 32px 8px"><h1 style="margin:0 0 14px;font-size:23px;color:' . $navy . '">' . esc_html( $heading ) . '</h1>'
		. '<div style="font-size:16px;line-height:1.7;color:#3a4761">' . $body . '</div></td></tr>'
		. '<tr><td style="padding:8px 32px 32px"><table role="presentation" cellpadding="0" cellspacing="0">' . $button . '</table></td></tr>'
		. '<tr><td style="background:#f7f9fc;padding:18px 32px;font-size:12px;color:#8a93a6" align="center">© ' . $year . ' ' . esc_html( $name ) . ' · נשלח כי נרשמת לעדכון באתר</td></tr>'
		. '</table></td></tr></table></body></html>';
}

/**
 * Send an HTML email through wp_mail.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param string $html    HTML body.
 * @return bool
 */
function kindi_send_html_mail( string $to, string $subject, string $html ): bool {
	return wp_mail( $to, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
}

/**
 * Replace {placeholders} in a template string.
 *
 * @param string                $template Template.
 * @param array<string,string>  $vars     name => value (without braces).
 * @return string
 */
function kindi_email_fill( string $template, array $vars ): string {
	$search  = array();
	$replace = array();
	foreach ( $vars as $key => $value ) {
		$search[]  = '{' . $key . '}';
		$replace[] = $value;
	}
	return str_replace( $search, $replace, $template );
}
