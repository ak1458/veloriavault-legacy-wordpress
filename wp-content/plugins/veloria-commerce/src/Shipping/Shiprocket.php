<?php
/**
 * Shiprocket API client (port of src/lib/shiprocket.ts).
 *
 * Credentials: wp-config constants VELORIA_SHIPROCKET_EMAIL and
 * VELORIA_SHIPROCKET_PASSWORD. The auth token is cached in a transient for
 * nine days (tokens are valid for ten).
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Shipping;

use Veloria\Commerce\Settings;
use Veloria\Commerce\Admin\OrderTools;

defined( 'ABSPATH' ) || exit;

final class Shiprocket {

	private const BASE      = 'https://apiv2.shiprocket.in/v1/external';
	private const TOKEN_KEY = 'veloria_shiprocket_token';

	public static function configured(): bool {
		return '' !== self::email() && '' !== self::password();
	}

	private static function email(): string {
		return defined( 'VELORIA_SHIPROCKET_EMAIL' ) ? trim( (string) VELORIA_SHIPROCKET_EMAIL ) : '';
	}

	private static function password(): string {
		return defined( 'VELORIA_SHIPROCKET_PASSWORD' ) ? trim( (string) VELORIA_SHIPROCKET_PASSWORD ) : '';
	}

	private static function token( bool $refresh = false ): string {
		if ( ! $refresh ) {
			$cached = get_transient( self::TOKEN_KEY );
			if ( is_string( $cached ) && '' !== $cached ) {
				return $cached;
			}
		}
		if ( ! self::configured() ) {
			return '';
		}
		$response = wp_remote_post(
			self::BASE . '/auth/login',
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'email' => self::email(), 'password' => self::password() ) ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			self::log( 'Authentication unavailable. Check credentials and the provider connection.' );
			return '';
		}
		$data  = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$token = is_array( $data ) ? (string) ( $data['token'] ?? '' ) : '';
		if ( '' !== $token ) {
			set_transient( self::TOKEN_KEY, $token, 9 * DAY_IN_SECONDS );
		}
		return $token;
	}

	/**
	 * @param array<string,mixed>|null $body
	 * @return array{status:int,data:mixed}
	 */
	private static function request( string $method, string $endpoint, ?array $body = null ): array {
		$attempt = static function ( string $token ) use ( $method, $endpoint, $body ) {
			return wp_remote_request(
				self::BASE . $endpoint,
				array(
					'method'  => $method,
					'timeout' => 20,
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $token,
					),
					'body'    => null === $body ? null : wp_json_encode( $body ),
				)
			);
		};

		$token = self::token();
		if ( '' === $token ) {
			return array( 'status' => 0, 'data' => null, 'sent' => false );
		}
		$response = $attempt( $token );
		if ( ! is_wp_error( $response ) && 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
			delete_transient( self::TOKEN_KEY );
			$token = self::token( true );
			if ( '' !== $token ) {
				$response = $attempt( $token );
			}
		}
		if ( is_wp_error( $response ) ) {
			return array( 'status' => 0, 'data' => null, 'sent' => true );
		}
		return array(
			'status' => (int) wp_remote_retrieve_response_code( $response ),
			'data'   => json_decode( (string) wp_remote_retrieve_body( $response ), true ),
			'sent'   => true,
		);
	}

	/**
	 * Create a Shiprocket order and store its ids on the WooCommerce order.
	 * Never throws; failures are logged and noted on the order.
	 */
	public static function create_order( \WC_Order $order ): bool {
		$result = self::sync_order( $order );
		return 'synced' === $result['state'];
	}

	/**
	 * Look up by our stable channel order ID before creation. A lost POST response
	 * can only be reconciled: an empty eventual-consistency lookup is not proof
	 * that the earlier request was rejected. Never automatically POST again.
	 *
	 * @return array{state:string,error:string}
	 */
	public static function sync_order( \WC_Order $order ): array {
		if ( 'yes' === $order->get_meta( '_veloria_cancel_requested' ) || 'yes' === $order->get_meta( '_return_requested' )
			|| in_array( $order->get_status(), array( 'cancelled', 'failed', 'refunded', 'on-hold' ), true )
			|| ( (string) Settings::get( 'cod_gateway', 'cod' ) !== $order->get_payment_method() && ! $order->is_paid() ) ) {
			return array( 'state' => 'blocked', 'error' => 'Order is not eligible for shipping.' );
		}
		if ( $order->get_meta( '_shiprocket_order_id' ) ) {
			return array( 'state' => 'synced', 'error' => '' );
		}
		if ( ! self::configured() ) {
			self::log( 'Skipped order #' . $order->get_id() . ': credentials not configured.' );
			return array( 'state' => 'review', 'error' => 'Shiprocket credentials are not configured.' );
		}

		$lookup = self::lookup_order( $order );
		if ( 'found' === $lookup['state'] ) {
			self::store_remote_ids( $order, $lookup['data'], 'Reconciled existing Shiprocket order' );
			return array( 'state' => 'synced', 'error' => '' );
		}
		if ( 'empty' !== $lookup['state'] ) {
			return array( 'state' => 'retry' === $lookup['state'] ? 'retry' : 'review', 'error' => $lookup['error'] );
		}
		if ( in_array( (string) $order->get_meta( '_veloria_sr_create_state' ), array( 'sending', 'ambiguous' ), true ) ) {
			return array( 'state' => 'retry', 'error' => 'Earlier creation response is uncertain. Lookup found no confirmed match yet; automatic recreation is blocked. Check Shiprocket by channel order ID.' );
		}

		$is_cod   = (string) Settings::get( 'cod_gateway', 'cod' ) === $order->get_payment_method();
		$shipping = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
		$items    = array();
		$subtotal = 0.0;
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			$qty     = max( 1, (int) $item->get_quantity() );
			$line_gross = (float) $item->get_subtotal() + (float) $item->get_subtotal_tax();
			$subtotal += $line_gross;
			$items[] = array(
				'name'          => mb_substr( $item->get_name(), 0, 100 ),
				'sku'           => ( $product && $product->get_sku() ) ? $product->get_sku() : 'VV-' . $item->get_product_id(),
				'units'         => $qty,
				'selling_price' => round( $line_gross / $qty, 2 ),
				'discount'      => 0,
				'tax'           => 0,
				'hsn'           => '',
			);
		}

		$cod_fee = 0.0;
		foreach ( $order->get_fees() as $fee ) {
			if ( (float) $fee->get_total() > 0 ) {
				$cod_fee += (float) $fee->get_total() + (float) $fee->get_total_tax();
			}
		}
		// Keep the courier's collectible amount equal to the native Woo total.
		// Using the already-discounted prepaid total as sub_total would add shipping twice.
		$discount = round( $subtotal + $shipping + $cod_fee - (float) $order->get_total(), 2 );
		if ( ! $items || $discount < -0.02 ) {
			return array( 'state' => 'review', 'error' => 'Order lines, shipping and fees do not reconcile to the order total. Shipment creation requires staff review.' );
		}
		$discount = max( 0, $discount );

		$created = $order->get_date_created();
		$payload = array(
			'order_id'              => (string) $order->get_id(),
			'order_date'            => $created ? $created->date( 'Y-m-d H:i' ) : gmdate( 'Y-m-d H:i' ),
			'pickup_location'       => (string) Settings::get( 'shiprocket_pickup', 'Primary' ),
			'billing_customer_name' => $order->get_billing_first_name(),
			'billing_last_name'     => $order->get_billing_last_name(),
			'billing_address'       => $order->get_billing_address_1(),
			'billing_address_2'     => $order->get_billing_address_2(),
			'billing_city'          => $order->get_billing_city(),
			'billing_pincode'       => $order->get_billing_postcode(),
			'billing_state'         => self::state_name( (string) $order->get_billing_state() ),
			'billing_country'       => 'India',
			'billing_email'         => $order->get_billing_email(),
			'billing_phone'         => $order->get_billing_phone(),
			'shipping_is_billing'   => true,
			'order_items'           => $items,
			'payment_method'        => $is_cod ? 'COD' : 'Prepaid',
			'sub_total'             => $subtotal,
			'shipping_charges'      => $shipping,
			'total_discount'        => $discount,
			'transaction_charges'   => $cod_fee,
			'length'                => 25,
			'breadth'               => 20,
			'height'                => 10,
			'weight'                => 0.5,
		);
		$channel = (string) Settings::get( 'shiprocket_channel_id', '' );
		if ( method_exists( $order, 'has_shipping_address' ) && $order->has_shipping_address() ) {
			$payload['shipping_is_billing'] = false;
			foreach ( array( 'first_name' => 'customer_name', 'last_name' => 'last_name', 'address_1' => 'address', 'address_2' => 'address_2', 'city' => 'city', 'postcode' => 'pincode' ) as $field => $key ) $payload[ 'shipping_' . $key ] = $order->{ 'get_shipping_' . $field }();
			$payload['shipping_state'] = self::state_name( (string) $order->get_shipping_state() );
			$payload['shipping_country'] = 'IN' === $order->get_shipping_country() ? 'India' : $order->get_shipping_country();
			$payload['shipping_phone'] = method_exists( $order, 'get_shipping_phone' ) && $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();
		}
		if ( '' !== $channel ) {
			$payload['channel_id'] = $channel;
		}

		// Persist BEFORE transport: a worker crash or timeout cannot cause a second creation.
		$order->update_meta_data( '_veloria_sr_create_state', 'sending' );
		$order->save();
		$result = self::request( 'POST', '/orders/create/adhoc', $payload );
		$data   = is_array( $result['data'] ) ? $result['data'] : array();
		if ( $result['status'] < 200 || $result['status'] >= 300 || empty( $data['order_id'] ) ) {
			// A documented rejection/auth failure did not create an order. Everything
			// else (transport loss, 5xx, malformed 2xx) stays ambiguous permanently.
			$rejected = empty( $result['sent'] ) || in_array( $result['status'], array( 401, 403, 422, 429 ), true );
			$order->update_meta_data( '_veloria_sr_create_state', $rejected ? 'rejected' : 'ambiguous' );
			$order->save();
			return array(
				'state' => $rejected && in_array( $result['status'], array( 401, 403, 422 ), true ) ? 'review' : 'retry',
				'error' => $rejected ? 'Shiprocket rejected or did not receive creation (HTTP ' . $result['status'] . '). Check configuration; retries are bounded.' : 'Shiprocket creation response is uncertain. Only lookup reconciliation will run; no second creation is allowed.',
			);
		}
		self::store_remote_ids( $order, $data, 'Synced to Shiprocket' );
		return array( 'state' => 'synced', 'error' => '' );
	}

	/** Only exact identity + billing email + total matches may attach a remote order. */
	private static function lookup_order( \WC_Order $order ): array {
		$query = array( 'filter_by' => 'channel_order_id', 'filter' => (string) $order->get_id(), 'per_page' => 100, 'page' => 1 );
		$channel = (string) Settings::get( 'shiprocket_channel_id', '' );
		if ( '' !== $channel ) {
			$query['channel_id'] = $channel;
		}
		$result = self::request( 'GET', '/orders?' . http_build_query( $query, '', '&', PHP_QUERY_RFC3986 ) );
		$data = $result['data'];
		if ( 200 !== $result['status'] || ! is_array( $data ) || ! isset( $data['data'] ) || ! is_array( $data['data'] ) || ! array_is_list( $data['data'] ) ) {
			return array( 'state' => 'retry', 'error' => 'Cannot safely look up existing Shiprocket orders. Creation is held until lookup succeeds.' );
		}
		$matches = array();
		$collision = false;
		foreach ( $data['data'] as $row ) {
			if ( ! is_array( $row ) || (string) ( $row['channel_order_id'] ?? '' ) !== (string) $order->get_id() ) {
				continue;
			}
			$channel_ok = '' !== $channel ? (string) ( $row['channel_id'] ?? '' ) === $channel : ( 'CS' === ( $row['base_channel_code'] ?? '' ) || 'CUSTOM' === strtoupper( (string) ( $row['channel_name'] ?? '' ) ) );
			if ( ! $channel_ok || ! ctype_digit( (string) ( $row['id'] ?? '' ) ) || strtolower( trim( (string) ( $row['customer_email'] ?? '' ) ) ) !== strtolower( trim( $order->get_billing_email() ) ) || ! isset( $row['total'] ) || ! is_numeric( $row['total'] ) || abs( (float) $row['total'] - (float) $order->get_total() ) > 0.02 ) {
				$collision = true;
				continue;
			}
			$shipment = $row['shipments'][0]['id'] ?? $row['shipments'][0]['shipment_id'] ?? null;
			$matches[] = array( 'order_id' => (string) $row['id'], 'shipment_id' => $shipment );
		}
		if ( $collision || count( $matches ) > 1 || count( $data['data'] ) >= 100 || (int) ( $data['meta']['pagination']['total_pages'] ?? 1 ) > 1 ) {
			return array( 'state' => 'review', 'error' => 'Shiprocket lookup has duplicate, conflicting or incomplete identities. Staff must reconcile; no order was created.' );
		}
		return $matches ? array( 'state' => 'found', 'data' => $matches[0] ) : array( 'state' => 'empty' );
	}

	private static function store_remote_ids( \WC_Order $order, array $data, string $note ): void {
		$order->update_meta_data( '_shiprocket_order_id', (string) $data['order_id'] );
		if ( ! empty( $data['shipment_id'] ) ) {
			$order->update_meta_data( '_shiprocket_shipment_id', (string) $data['shipment_id'] );
		}
		$order->update_meta_data( '_veloria_sr_create_state', 'confirmed' );
		$order->add_order_note( $note . ': ' . $data['order_id'] );
		$order->save();
	}

	/**
	 * Live tracking, normalised. Null on any failure so callers can fall back
	 * to the WooCommerce status.
	 *
	 * @return array{status:string,awb:?string,etaDate:?string,checkpoints:array<int,array<string,string>>}|null
	 */
	public static function track_shipment( string $shipment_id, ?\WC_Order $order = null, bool $fresh = false ): ?array {
		$cache_key = 'veloria_sr_track_' . md5( $shipment_id );
		$cached    = get_transient( $cache_key );
		if ( ! $fresh && is_array( $cached ) ) {
			self::ingest_delivery( $order, $shipment_id, $cached );
			return $cached;
		}
		$result = self::request( 'GET', '/courier/track/shipment/' . rawurlencode( $shipment_id ) );
		if ( 200 !== $result['status'] || ! is_array( $result['data'] ) ) {
			return null;
		}
		$data  = $result['data'];
		$td    = $data['tracking_data'] ?? ( is_array( reset( $data ) ) ? reset( $data ) : array() );
		$td    = is_array( $td ) ? $td : array();
		$track = isset( $td['shipment_track'][0] ) && is_array( $td['shipment_track'][0] ) ? $td['shipment_track'][0] : array();
		$acts  = isset( $td['shipment_track_activities'] ) && is_array( $td['shipment_track_activities'] ) ? $td['shipment_track_activities'] : array();

		$tracking = array(
			'status'      => (string) ( $track['current_status'] ?? $td['shipment_status'] ?? 'In Transit' ),
			'awb'         => isset( $track['awb_code'] ) && '' !== $track['awb_code'] ? (string) $track['awb_code'] : null,
			'etaDate'     => ( $track['edd'] ?? $td['etd'] ?? null ) ? (string) ( $track['edd'] ?? $td['etd'] ) : null,
			'checkpoints' => array_map(
				static fn( $a ) => array(
					'date'     => (string) ( $a['date'] ?? '' ),
					'activity' => (string) ( $a['activity'] ?? '' ),
					'location' => (string) ( $a['location'] ?? '' ),
				),
				$acts
			),
			'deliveredAt' => self::delivery_from_tracking( $track ),
			'verifiedShipmentId' => (string) ( $track['id'] ?? '' ),
		);
		self::ingest_delivery( $order, $shipment_id, $tracking );
		set_transient( $cache_key, $tracking, 10 * MINUTE_IN_SECONDS );
		return $tracking;
	}

	/** Carrier timestamps without offsets are interpreted in the store timezone. */
	public static function delivery_from_tracking( array $track ): ?int {
		if ( 'delivered' !== strtolower( trim( (string) ( $track['current_status'] ?? '' ) ) ) ) {
			return null;
		}
		$value = (string) ( $track['delivered_date'] ?? '' );
		foreach ( array( 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:sP' ) as $format ) {
			$date = \DateTimeImmutable::createFromFormat( '!' . $format, $value, wp_timezone() );
			$errors = \DateTimeImmutable::getLastErrors();
			if ( $date && ( false === $errors || ( ! $errors['warning_count'] && ! $errors['error_count'] ) ) && $date->format( $format ) === $value ) {
				return $date->getTimestamp();
			}
		}
		return null;
	}

	private static function ingest_delivery( ?\WC_Order $order, string $shipment_id, array $tracking ): void {
		if ( ! $order || (string) $order->get_meta( '_shiprocket_shipment_id' ) !== $shipment_id || (string) ( $tracking['verifiedShipmentId'] ?? '' ) !== $shipment_id || ! is_int( $tracking['deliveredAt'] ?? null ) ) {
			return;
		}
		OrderTools::record_delivery( $order, $tracking['deliveredAt'], 'shiprocket', 0, 'Authenticated tracking matched shipment #' . $shipment_id . ' with explicit Delivered status and delivered_date.' );
	}

	/**
	 * Cancel at the courier, then confirm by reading the exact remote order back:
	 * a 2xx (including a documented 204) alone is not proof the parcel is stopped.
	 */
	public static function cancel_order( int $shiprocket_order_id ): bool {
		$result = self::request( 'POST', '/orders/cancel', array( 'ids' => array( $shiprocket_order_id ) ) );
		if ( $result['status'] < 200 || $result['status'] >= 300 ) {
			return false;
		}
		$check = self::request( 'GET', '/orders/show/' . $shiprocket_order_id );
		$row   = is_array( $check['data'] ) && is_array( $check['data']['data'] ?? null ) ? $check['data']['data'] : array();
		return 200 === $check['status'] && (string) ( $row['id'] ?? '' ) === (string) $shiprocket_order_id
			&& self::forward_cancellation_complete( $row );
	}

	/** Requested cancellations and cancelled returns do not stop the forward parcel. */
	private static function forward_cancellation_complete( array $row ): bool {
		$status = strtoupper( trim( (string) ( $row['status'] ?? '' ) ) );
		$status = preg_replace( '/[_\s-]+/', ' ', $status );
		$codes  = array(
			'CANCELED'                    => 5,
			'CANCELLED'                   => 5,
			'CANCELED BEFORE DISPATCHED'  => 54,
			'CANCELLED BEFORE DISPATCHED' => 54,
		);
		return isset( $codes[ $status ] ) && ( ! isset( $row['status_code'] ) || (string) $row['status_code'] === (string) $codes[ $status ] );
	}

	/** Snapshot invalidates staff clearance when any local booking/evidence changes. */
	public static function cancellation_fingerprint( \WC_Order $order ): string {
		$snapshot = array( 'id' => $order->get_id(), 'total' => (string) $order->get_total(), 'method' => $order->get_payment_method() );
		foreach ( array( '_shiprocket_order_id', '_shiprocket_shipment_id', '_shiprocket_awb', 'awb', '_veloria_sr_create_state', '_veloria_delivery_verified', '_veloria_delivered_at', '_return_requested', '_veloria_return_decision' ) as $key ) {
			$snapshot[ $key ] = (string) $order->get_meta( $key );
		}
		return hash( 'sha256', (string) wp_json_encode( $snapshot ) );
	}

	/** Staff submits this evidence and executes cancellation inside the same locks. */
	public static function has_staff_clearance( \WC_Order $order ): bool {
		$proof = $order->get_meta( '_veloria_sr_staff_clearance' );
		$age   = is_array( $proof ) ? time() - (int) ( $proof['at'] ?? 0 ) : -1;
		return is_array( $proof ) && $age >= 0 && $age <= 300
			&& (int) ( $proof['actor'] ?? 0 ) > 0 && (int) $proof['actor'] === get_current_user_id()
			&& current_user_can( 'manage_woocommerce' ) && current_user_can( 'edit_shop_order', $order->get_id() )
			&& strlen( trim( (string) ( $proof['reference'] ?? '' ) ) ) >= 8
			&& strlen( (string) $proof['reference'] ) <= 200
			&& hash_equals( self::cancellation_fingerprint( $order ), (string) ( $proof['fingerprint'] ?? '' ) );
	}

	/** Record a verified stop; a later refund continuation must match this snapshot. */
	private static function record_cancellation_clearance( \WC_Order $order, string $method ): void {
		$order->update_meta_data( '_shiprocket_cancel_state', 'confirmed' );
		$order->update_meta_data( '_veloria_sr_cancel_fingerprint', self::cancellation_fingerprint( $order ) );
		$order->update_meta_data( '_veloria_sr_cancel_method', $method );
		$order->update_meta_data( '_veloria_sr_cancel_verified_at', time() );
		$order->save();
	}

	/**
	 * Verify courier clearance before money moves. The official importer can hold
	 * a remote order without writing our metadata; missing local IDs are not proof
	 * that no parcel exists. No creation/import request is ever made here.
	 *
	 * @return array{success:bool,error?:string,needs_staff?:bool}
	 */
	public static function cancel_for_order( \WC_Order $order ): array {
		// A pending refund or local-ledger repair continues the same verified
		// cancellation; never demand a second one-use staff action for that case.
		if ( self::has_verified_cancellation( $order ) ) {
			return array( 'success' => true );
		}
		if ( self::has_staff_clearance( $order ) ) {
			$proof = $order->get_meta( '_veloria_sr_staff_clearance' );
			$order->delete_meta_data( '_veloria_sr_staff_clearance' );
			$order->update_meta_data( '_veloria_sr_cancel_staff_actor', (int) $proof['actor'] );
			$order->update_meta_data( '_veloria_sr_cancel_reference', sanitize_text_field( (string) $proof['reference'] ) );
			$order->add_order_note( 'Courier clearance verified by staff #' . (int) $proof['actor'] . '. Reference: ' . sanitize_text_field( (string) $proof['reference'] ) . '. One-use evidence consumed for this cancellation.' );
			self::record_cancellation_clearance( $order, 'staff' );
			return array( 'success' => true );
		}

		$official = ShiprocketSync::official_sync_active();
		$remote  = (int) $order->get_meta( '_shiprocket_order_id' );
		if ( ! $remote && self::configured() && ( ! $official || '' !== trim( (string) Settings::get( 'shiprocket_channel_id', '' ) ) ) ) {
			$lookup = self::lookup_order( $order );
			if ( 'found' === $lookup['state'] ) {
				self::store_remote_ids( $order, $lookup['data'], 'Matched Shiprocket order before cancellation' );
				$remote = (int) $order->get_meta( '_shiprocket_order_id' );
			} elseif ( 'empty' !== $lookup['state'] ) {
				return array( 'success' => false, 'needs_staff' => true, 'error' => 'Courier order identity could not be verified. Staff must confirm the exact channel order is cancelled before any refund.' );
			}
		}
		if ( $remote && self::configured() && self::cancel_order( $remote ) ) {
			self::record_cancellation_clearance( $order, 'api' );
			return array( 'success' => true );
		}
		$has_booking = false;
		foreach ( array( '_shiprocket_shipment_id', '_shiprocket_awb', 'awb' ) as $key ) {
			$has_booking = $has_booking || '' !== trim( (string) $order->get_meta( $key ) );
		}
		$uncertain = in_array( (string) $order->get_meta( '_veloria_sr_create_state' ), array( 'sending', 'ambiguous', 'confirmed' ), true );
		if ( $official || $remote || $has_booking || $uncertain ) {
			return array( 'success' => false, 'needs_staff' => true, 'error' => 'Cancellation requested. Staff must verify the Shiprocket order and confirm the courier is stopped before any refund. Fulfillment must remain on hold.' );
		}
		// No external importer or prior creation exists. The caller owns the same
		// lock as custom shipment creation, so this local-only order cannot race it.
		self::record_cancellation_clearance( $order, 'local-only' );
		return array( 'success' => true );
	}

	public static function has_verified_cancellation( \WC_Order $order ): bool {
		$confirmed = (string) $order->get_meta( '_veloria_sr_cancel_fingerprint' );
		return 'yes' === (string) $order->get_meta( '_veloria_cancel_requested' ) && 'confirmed' === (string) $order->get_meta( '_shiprocket_cancel_state' )
			&& '' !== $confirmed && hash_equals( self::cancellation_fingerprint( $order ), $confirmed );
	}

	private static function state_name( string $code ): string {
		$states = WC()->countries->get_states( 'IN' );
		return is_array( $states ) && isset( $states[ $code ] ) ? html_entity_decode( (string) $states[ $code ] ) : $code;
	}

	private static function log( string $message ): void {
		wc_get_logger()->warning( '[Shiprocket] ' . $message, array( 'source' => 'veloria-commerce' ) );
	}
}
