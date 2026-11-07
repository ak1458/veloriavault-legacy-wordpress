<?php
// Owner business rules (hand-written expectations, independent of the parity vectors):
// tier 10% for 1 item / 15% for 2+, prepaid +5%, cap 35%, COD fee 149, free shipping, coupons paused, lucky draw off.
require dirname( __DIR__ ) . '/wp-content/plugins/veloria-commerce/src/Pricing/DiscountEngine.php';
use Veloria\Commerce\Pricing\DiscountEngine as E;

$e = new E( array() ); // production defaults: 10%/15%, +5% prepaid, cap 35%, COD 149, shipping 0, lucky draw off
$pass = 0; $fail = 0;
$ok = static function ( bool $c, string $m ) use ( &$pass, &$fail ) { if ( $c ) { $pass++; } else { $fail++; echo "FAIL $m\n"; } };
$r = static fn( float $v ) => floor( $v + 0.5 ); // ₹ half-up

// Hand-checked examples (₹).
$cases = array(
	// items, prepaid, tier, prepaidDisc, cod, total
	array( array( array( 1849, 1 ) ), true,  185, 92,  0,   1572 ),
	array( array( array( 1849, 1 ) ), false, 185, 0,   149, 1813 ),
	array( array( array( 1849, 2 ) ), true,  555, 185, 0,   2958 ),
	array( array( array( 1849, 2 ) ), false, 555, 0,   149, 3292 ),
	array( array( array( 2125, 1 ) ), true,  213, 106, 0,   1806 ), // 212.5 rounds up, 106.25 down
	array( array( array( 1249, 1 ), array( 2249, 1 ) ), true, 525, 175, 0, 2798 ), // mixed products count together
	array( array( array( 999, 3 ) ), false, 450, 0, 149, 2696 ), // 3+ items stay 15%
);
foreach ( $cases as $i => [ $items, $pre, $tier, $pd, $cod, $total ] ) {
	$c = $e->calculate( array_map( fn( $x ) => array( 'price' => $x[0], 'quantity' => $x[1] ), $items ), array(), $pre );
	$ok( $c['tierDiscount'] == $tier && $c['prepaidDiscount'] == $pd && $c['codFee'] == $cod && $c['finalTotal'] == $total && $c['shippingCost'] == 0,
		"case $i got tier={$c['tierDiscount']} pre={$c['prepaidDiscount']} cod={$c['codFee']} total={$c['finalTotal']}" );
}

// Exhaustive grid: every qty 1..12, many prices, both payment types.
$prices = array( 1, 99, 499, 999, 1249, 1499, 1849, 1999, 2125, 2249, 2999, 3149, 4999, 9999, 1234.5 );
foreach ( $prices as $p ) {
	for ( $q = 1; $q <= 12; $q++ ) {
		foreach ( array( true, false ) as $pre ) {
			$s = $p * $q;
			$c = $e->calculate( array( array( 'price' => $p, 'quantity' => $q ) ), array(), $pre );
			$tierPct = 1 === $q ? 10 : 15;
			$expTier = $r( $s * $tierPct / 100 );
			$expPre  = $pre ? $r( $s * 5 / 100 ) : 0;
			$exp     = $s - $expTier - $expPre + ( $pre ? 0 : 149 );
			$ok( ! $c['isCapped'], "never capped without coupons p=$p q=$q" );
			$ok( abs( $c['finalTotal'] - $exp ) < 0.001, "grid p=$p q=$q pre=" . (int) $pre . " got {$c['finalTotal']} exp $exp" );
			$ok( ( $c['tierDiscount'] + $c['prepaidDiscount'] ) <= floor( $s * 0.35 ), "under 35% cap p=$p q=$q" );
			$ok( $pre ? 0 == $c['codFee'] : 149 == $c['codFee'], "COD fee only on COD p=$p q=$q" );
		}
	}
}

// Split cart (two lines of 1) must equal one line of qty 2.
$a = $e->calculate( array( array( 'price' => 1849, 'quantity' => 1 ), array( 'price' => 1849, 'quantity' => 1 ) ), array(), true );
$b = $e->calculate( array( array( 'price' => 1849, 'quantity' => 2 ) ), array(), true );
$ok( $a['finalTotal'] == $b['finalTotal'], 'two lines == qty 2' );

// 35% cap: a big stackable coupon gets scaled down so total discount never exceeds 35%.
$big = array( array( 'code' => 'BIG', 'type' => 'percentage', 'amount' => 30, 'isActive' => true, 'stackable' => true, 'category' => 'promo' ) );
$c = $e->calculate( array( array( 'price' => 2000, 'quantity' => 2 ) ), array( 'BIG' ), true, $big );
$ok( $c['isCapped'] && ( $c['tierDiscount'] + $c['prepaidDiscount'] + $c['manualCouponDiscount'] ) <= 1400, 'cap 35% enforced with coupon: ' . ( $c['tierDiscount'] + $c['prepaidDiscount'] + $c['manualCouponDiscount'] ) );

// Coupons paused: unknown codes give nothing.
$c = $e->calculate( array( array( 'price' => 1849, 'quantity' => 1 ) ), array( 'WELCOME10', 'FREE' ), true, array() );
$ok( 0 == $c['manualCouponDiscount'] && 1572 == $c['finalTotal'], 'unknown/paused coupon codes ignored' );
// Inactive coupon rejected.
$off = array( array( 'code' => 'OFF', 'type' => 'percentage', 'amount' => 10, 'isActive' => false, 'stackable' => true, 'category' => 'promo' ) );
$c = $e->calculate( array( array( 'price' => 1849, 'quantity' => 1 ) ), array( 'OFF' ), true, $off );
$ok( 0 == $c['manualCouponDiscount'], 'inactive coupon ignored' );

// Lucky draw off: LUCKYDRAW code gives nothing even when a discount is passed.
$c = $e->calculate( array( array( 'price' => 1849, 'quantity' => 1 ) ), array( 'LUCKYDRAW' ), true, array(), 20 );
$ok( 0 == $c['manualCouponDiscount'], 'lucky draw disabled' );

// Empty cart.
$c = $e->calculate( array(), array(), false );
$ok( 0 == $c['finalTotal'] && 0 == $c['codFee'], 'empty cart = 0, no COD fee' );

echo $fail ? "$fail business-rule check(s) FAILED\n" : "$pass business-rule checks passed\n";
exit( $fail ? 1 : 0 );
