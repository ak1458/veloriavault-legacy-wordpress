<?php
/** Bounded background shipping retries; uncertain creations are lookup-only. */
namespace Veloria\Commerce\Shipping;
use Veloria\Commerce\Settings;
defined( 'ABSPATH' ) || exit;

final class ShiprocketSync {
    public const ACTION = 'veloria_shiprocket_sync';
    public const MAX_ATTEMPTS = 5;
    private const DELAYS = array( 60, 300, 900, 3600, 21600 );

    public static function register(): void {
        add_action( self::ACTION, array( self::class, 'run' ) );
        add_action( 'admin_notices', array( self::class, 'notice' ) );
        add_action( 'woocommerce_checkout_order_processed', array( self::class, 'on_order_placed' ), 20, 3 );
        add_action( 'woocommerce_payment_complete', array( self::class, 'queue' ) );
        // WC_Webhook::deliver() sets the source header after its own args filter,
        // so the override must run at the HTTP layer.
        add_filter( 'http_request_args', array( self::class, 'webhook_source' ), 10, 2 );
    }
    /**
     * The official Shiprocket channel was connected while the store answered on
     * api.veloriavault.com and identifies the store by that URL. After the move to
     * www, deliveries were acknowledged (HTTP 200) but not imported. Present the
     * registered store URL on WooCommerce webhooks to Shiprocket only; the HMAC
     * signature covers the body, not this header. Clear the setting once the
     * channel uses www.
     */
    public static function webhook_source( $args, $url = '' ) {
        $source = trim( (string) Settings::get( 'shiprocket_webhook_source', '' ) );
        $host = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
        if ( '' === $source || ! is_array( $args ) || ! isset( $args['headers']['X-WC-Webhook-Topic'] ) || ! preg_match( '/(^|\.)shiprocket\.in$/', $host ) || ! preg_match( '#^https://[a-z0-9.-]+/$#', $source ) ) return $args;
        $args['headers']['X-WC-Webhook-Source'] = $source;
        return $args;
    }
    public static function on_order_placed( $id, $posted, $order ): void {
        if ( $order instanceof \WC_Order && (string) Settings::get( 'cod_gateway', 'cod' ) === $order->get_payment_method() ) self::queue( (int) $id );
    }
    public static function official_sync_active(): bool {
        $plugins = array_merge( (array) get_option( 'active_plugins', array() ), array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
        foreach ( $plugins as $plugin ) if ( str_starts_with( (string) $plugin, 'shiprocket/' ) ) return true;
        // Channel webhooks survive plugin deactivation. They must still exclude
        // the custom importer and require courier clearance before cancellation.
        if ( class_exists( '\\WC_Data_Store' ) && function_exists( 'wc_get_webhook' ) ) {
            try {
                $store = \WC_Data_Store::load( 'webhook' );
                foreach ( (array) $store->get_webhooks_ids( 'active' ) as $id ) {
                    $webhook = wc_get_webhook( (int) $id );
                    if ( ! $webhook || ! str_starts_with( (string) $webhook->get_topic(), 'order.' ) ) continue;
                    $host = strtolower( (string) parse_url( (string) $webhook->get_delivery_url(), PHP_URL_HOST ) );
                    if ( preg_match( '/(^|\.)shiprocket\.in$/', $host ) ) return true;
                }
            } catch ( \Throwable $error ) {
                // Failure to inspect the importer is not clearance to ship twice.
                return true;
            }
        }
        return false;
    }
    /** Scheduling is asynchronous even without Action Scheduler. */
    private static function schedule( \WC_Order $order, int $delay, bool $unique = true ): void {
        $at = time() + max( 0, $delay );
        $order->update_meta_data( '_veloria_sr_sync_next_at', $at );
        $order->save();
        $args = array( 'order_id' => $order->get_id() );
        $scheduled = function_exists( 'as_schedule_single_action' )
            ? as_schedule_single_action( $at, self::ACTION, $args, 'veloria', $unique )
            : wp_schedule_single_event( $at, self::ACTION, $args, true );
        if ( ! $scheduled || is_wp_error( $scheduled ) ) self::record( $order, 'review', 'Shipping job could not be scheduled. Check Action Scheduler / cron and retry from the order screen.' );
    }
    public static function queue( int $id ): void {
        if ( 'custom' !== Settings::get( 'shiprocket_mode', 'off' ) ) return;
        $order = wc_get_order( $id );
        if ( ! $order instanceof \WC_Order || $order->get_meta( '_shiprocket_order_id' ) || (int) $order->get_meta( '_veloria_sr_sync_attempts' ) >= self::MAX_ATTEMPTS || in_array( (string) $order->get_meta( '_veloria_sr_sync_state' ), array( 'review', 'blocked' ), true ) ) return;
        if ( self::official_sync_active() ) {
            self::record( $order, 'review', 'The official Shiprocket plugin is active. Choose one sync path before retrying to avoid duplicate shipments.' );
            return;
        }
        $args = array( 'order_id' => $id );
        if ( function_exists( 'as_has_scheduled_action' ) ? as_has_scheduled_action( self::ACTION, $args, 'veloria' ) : wp_next_scheduled( self::ACTION, $args ) ) return;
        $order->update_meta_data( '_veloria_sr_sync_state', 'queued' );
        $order->save();
        self::schedule( $order, max( 0, (int) $order->get_meta( '_veloria_sr_sync_next_at' ) - time() ) );
    }
    public static function run( int $id ): void {
        if ( 'custom' !== Settings::get( 'shiprocket_mode', 'off' ) ) return;
        $lock = 'veloria_sr_lock_' . $id;
        // Never steal a stale lock automatically: its POST may still be in flight.
        // A customer cancellation also holds this lock; check again after it finishes.
        if ( ! add_option( $lock, time(), '', false ) ) {
            $order = wc_get_order( $id );
            if ( $order instanceof \WC_Order && get_option( 'veloria_cancel_lock_' . $id ) ) self::schedule( $order, 300, false );
            return;
        }
        try {
            $order = wc_get_order( $id );
            if ( ! $order instanceof \WC_Order || $order instanceof \WC_Order_Refund ) return;
            if ( self::official_sync_active() ) {
                self::record( $order, 'review', 'The official Shiprocket plugin is active. Choose one sync path before retrying to avoid duplicate shipments.' );
                return;
            }
            if ( $order->get_meta( '_shiprocket_order_id' ) ) { self::record( $order, 'synced', '' ); return; }
            $attempts = (int) $order->get_meta( '_veloria_sr_sync_attempts' );
            if ( $attempts >= self::MAX_ATTEMPTS || in_array( (string) $order->get_meta( '_veloria_sr_sync_state' ), array( 'review', 'blocked' ), true ) || (int) $order->get_meta( '_veloria_sr_sync_next_at' ) > time() + 2 ) return;
            $order->update_meta_data( '_veloria_sr_sync_attempts', ++$attempts );
            $order->update_meta_data( '_veloria_sr_sync_last_at', time() );
            $order->update_meta_data( '_veloria_sr_sync_state', 'running' );
            $order->delete_meta_data( '_veloria_sr_sync_next_at' );
            $order->save();
            $result = Shiprocket::sync_order( $order );
            $state = $result['state'];
            if ( 'retry' === $state && $attempts >= self::MAX_ATTEMPTS ) {
                $state = 'review';
                $result['error'] .= ' Automatic retry limit reached. Resolve the cause and use Retry / reconcile shipment.';
            }
            self::record( $order, $state, $result['error'] );
            // Current running action must not suppress its own future retry.
            if ( 'retry' === $state ) self::schedule( $order, self::DELAYS[ min( $attempts - 1, count( self::DELAYS ) - 1 ) ], false );
        } catch ( \Throwable $error ) {
            if ( isset( $order ) && $order instanceof \WC_Order ) self::record( $order, 'review', 'Shipping worker stopped unexpectedly. Review Shiprocket by channel order ID before retrying; uncertain creations are never repeated.' );
        } finally { delete_option( $lock ); }
    }
    /** Staff retry resets the budget, never the durable uncertain-POST marker. */
    public static function retry( int $id ): bool {
        if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $id ) || 'custom' !== Settings::get( 'shiprocket_mode', 'off' ) || self::official_sync_active() ) return false;
        $order = wc_get_order( $id );
        if ( ! $order instanceof \WC_Order ) return false;
        $lock = 'veloria_sr_lock_' . $id;
        $since = (int) get_option( $lock, 0 );
        if ( $since ) {
            if ( $since > time() - 15 * MINUTE_IN_SECONDS ) return false;
            delete_option( $lock ); // Only explicit staff action releases an abandoned worker.
        }
        $order->update_meta_data( '_veloria_sr_sync_attempts', 0 );
        $order->update_meta_data( '_veloria_sr_sync_state', 'queued' );
        $order->delete_meta_data( '_veloria_sr_sync_next_at' );
        $order->add_order_note( 'Shiprocket retry/reconciliation requested by user #' . get_current_user_id() . '. Any uncertain creation remains lookup-only.' );
        $order->save();
        self::queue( $id );
        return true;
    }
    private static function record( \WC_Order $order, string $state, string $error ): void {
        $changed = $state !== $order->get_meta( '_veloria_sr_sync_state' ) || $error !== $order->get_meta( '_veloria_sr_sync_error' );
        $order->update_meta_data( '_veloria_sr_sync_state', $state );
        $order->update_meta_data( '_veloria_sr_sync_error', $error );
        if ( $changed && $error ) $order->add_order_note( 'Shiprocket ' . $state . ': ' . $error );
        $order->save();
        if ( 'review' === $state ) update_option( 'veloria_sr_attention', array( 'order_id' => $order->get_id(), 'at' => time() ), false );
    }
    public static function notice(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;
        $attention = get_option( 'veloria_sr_attention', array() );
        $order = ! empty( $attention['order_id'] ) ? wc_get_order( (int) $attention['order_id'] ) : null;
        if ( $order instanceof \WC_Order && 'review' === $order->get_meta( '_veloria_sr_sync_state' ) ) echo '<div class="notice notice-warning"><p>Veloria shipment needs review: <a href="' . esc_url( $order->get_edit_order_url() ) . '">order #' . esc_html( (string) $order->get_order_number() ) . '</a>. Check the delivery and shipping panel before retrying.</p></div>';
    }
}
