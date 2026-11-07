<?php
/**
 * Fixed-window rate limiter backed by transients (object cache when present).
 * Port of src/lib/rate-limit.ts, but shared across PHP workers instead of
 * per-serverless-instance memory.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Security;

defined( 'ABSPATH' ) || exit;

final class RateLimiter {

	/** @return array{success:bool,remaining:int,reset:int} */
	public static function hit( string $bucket, int $limit, int $window_seconds, ?string $identity = null ): array {
		$identity = $identity ?? self::client_ip();
		$key      = 'veloria_rl_' . md5( $bucket . '|' . $identity );
		$now      = time();

		// A persistent object cache (Redis/Memcached) gives an atomic counter.
		if ( wp_using_ext_object_cache() ) {
			$window = (int) floor( $now / $window_seconds );
			$ckey   = $key . '_' . $window;
			wp_cache_add( $ckey, 0, 'veloria_rl', $window_seconds );
			$count = wp_cache_incr( $ckey, 1, 'veloria_rl' );
			if ( false !== $count ) {
				$reset = ( $window + 1 ) * $window_seconds;
				return (int) $count > $limit
					? array( 'success' => false, 'remaining' => 0, 'reset' => $reset )
					: array( 'success' => true, 'remaining' => $limit - (int) $count, 'reset' => $reset );
			}
		}

		$state = get_transient( $key );

		if ( ! is_array( $state ) || (int) ( $state['reset'] ?? 0 ) <= $now ) {
			$state = array( 'count' => 0, 'reset' => $now + $window_seconds );
		}

		if ( (int) $state['count'] >= $limit ) {
			return array( 'success' => false, 'remaining' => 0, 'reset' => (int) $state['reset'] );
		}

		$state['count'] = (int) $state['count'] + 1;
		set_transient( $key, $state, max( 1, (int) $state['reset'] - $now ) );

		return array( 'success' => true, 'remaining' => $limit - (int) $state['count'], 'reset' => (int) $state['reset'] );
	}

	/**
	 * Client IP. REMOTE_ADDR by default. Behind a CDN/proxy that overwrites the
	 * header (e.g. Cloudflare), define VELORIA_CLIENT_IP_HEADER in wp-config.php,
	 * e.g. 'HTTP_CF_CONNECTING_IP'. Never set it for a header clients can forge.
	 */
	public static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		if ( defined( 'VELORIA_CLIENT_IP_HEADER' ) && is_string( VELORIA_CLIENT_IP_HEADER ) && isset( $_SERVER[ VELORIA_CLIENT_IP_HEADER ] ) ) {
			$first = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ VELORIA_CLIENT_IP_HEADER ] ) ) )[0] );
			if ( false !== filter_var( $first, FILTER_VALIDATE_IP ) ) {
				$ip = $first;
			}
		}
		return (string) apply_filters( 'veloria_client_ip', $ip );
	}
}
