<?php
/**
 * Transactional emails (port of src/lib/mailer.ts). Sent through wp_mail, so
 * whatever SMTP plugin the site uses delivers them.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Mail;

defined( 'ABSPATH' ) || exit;

final class Mailer {

	/** @param string[] $extra_headers */
	private static function send( string $to, string $subject, string $html, array $extra_headers = array() ): bool {
		$headers = array_merge( array( 'Content-Type: text/html; charset=UTF-8' ), $extra_headers );
		return (bool) wp_mail( $to, $subject, $html, $headers );
	}

	public static function otp( string $to, string $code ): bool {
		return self::send(
			$to,
			'Your Veloria Vault login code',
			'<div style="font-family:sans-serif">'
			. '<h2 style="color:#b59a5c;margin:0 0 8px">Veloria Vault</h2>'
			. '<p>Your login code is</p>'
			. '<p style="font-size:28px;font-weight:bold;letter-spacing:4px">' . esc_html( $code ) . '</p>'
			. '<p style="color:#666">It expires in 10 minutes. If you didn\'t request this, ignore this email.</p>'
			. '</div>'
		);
	}

	public static function return_request( string $to, string $order_number, string $customer_email, string $reason ): bool {
		return self::send(
			$to,
			'Return request — Order #' . $order_number,
			'<div style="font-family:sans-serif">'
			. '<h3>Return request — Order #' . esc_html( $order_number ) . '</h3>'
			. '<p><b>Customer:</b> ' . esc_html( $customer_email ) . '</p>'
			. '<p><b>Reason:</b></p><p>' . nl2br( esc_html( $reason ) ) . '</p>'
			. '</div>',
			array( 'Reply-To: ' . $customer_email )
		);
	}

	public static function newsletter_signup( string $to, string $subscriber ): bool {
		return self::send(
			$to,
			'Newsletter signup — ' . $subscriber,
			'<div style="font-family:sans-serif"><p><b>New newsletter subscriber:</b> ' . esc_html( $subscriber ) . '</p></div>'
		);
	}

	/**
	 * The headless contact form only simulated sending. Messages are now
	 * delivered to the support inbox with Reply-To set to the customer.
	 */
	public static function contact( string $to, string $name, string $email, string $subject, string $message ): bool {
		// The display name must not be able to add addresses or headers.
		$reply_name = trim( (string) preg_replace( '/[\r\n\t,;:<>@"\'\\\\()\[\]]+/', ' ', $name ) );
		$reply_name = trim( (string) preg_replace( '/\s+/', ' ', $reply_name ) );
		$reply_to   = '' !== $reply_name ? $reply_name . ' <' . $email . '>' : $email;
		return self::send(
			$to,
			'Contact form — ' . $subject . ' — ' . $name,
			'<div style="font-family:sans-serif">'
			. '<p><b>Name:</b> ' . esc_html( $name ) . '</p>'
			. '<p><b>Email:</b> ' . esc_html( $email ) . '</p>'
			. '<p><b>Subject:</b> ' . esc_html( $subject ) . '</p>'
			. '<p><b>Message:</b></p><p>' . nl2br( esc_html( $message ) ) . '</p>'
			. '</div>',
			array( 'Reply-To: ' . $reply_to )
		);
	}
}
