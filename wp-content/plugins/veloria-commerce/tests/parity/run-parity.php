<?php
/**
 * Compares the PHP DiscountEngine against golden vectors produced by the
 * original TypeScript engine (generate-vectors.mjs). Runs without WordPress.
 *
 *   php run-parity.php vectors.json
 *
 * Exit code 0 = every vector matches to the paisa.
 */

declare(strict_types=1);

require __DIR__ . '/../../src/Pricing/DiscountEngine.php';

use Veloria\Commerce\Pricing\DiscountEngine;

$file = $argv[1] ?? __DIR__ . '/vectors.json';
$vectors = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
$coupons = json_decode((string) file_get_contents(__DIR__ . '/coupons.fixture.json'), true, 512, JSON_THROW_ON_ERROR);

$engine = new DiscountEngine(array());

$fields = array('originalSubtotal', 'itemCount', 'tierDiscount', 'prepaidDiscount', 'manualCouponDiscount', 'codFee', 'shippingCost', 'finalTotal');
$failures = 0;

foreach ($vectors as $i => $vector) {
	$in  = $vector['input'];
	$exp = $vector['output'];
	$got = $engine->calculate($in['items'], $in['codes'], (bool) $in['isPrepaid'], $coupons, 10.0);

	$problems = array();
	foreach ($fields as $field) {
		if ((float) $exp[$field] !== (float) $got[$field]) {
			$problems[] = sprintf('%s: ts=%s php=%s', $field, json_encode($exp[$field]), json_encode($got[$field]));
		}
	}
	if ((bool) $exp['isCapped'] !== (bool) $got['isCapped']) {
		$problems[] = 'isCapped differs';
	}
	$expSavings = array_map(static fn($s) => $s['label'] . '=' . (float) $s['amount'], $exp['savingsBreakdown']);
	$gotSavings = array_map(static fn($s) => $s['label'] . '=' . (float) $s['amount'], $got['savingsBreakdown']);
	if ($expSavings !== $gotSavings) {
		$problems[] = 'savings ts=' . json_encode($expSavings) . ' php=' . json_encode($gotSavings);
	}
	$expCodes = array_map(static fn($c) => $c['coupon']['code'] . '=' . (float) $c['discountAmount'], $exp['appliedCoupons']);
	$gotCodes = array_map(static fn($c) => $c['coupon']['code'] . '=' . (float) $c['discountAmount'], $got['appliedCoupons']);
	if ($expCodes !== $gotCodes) {
		$problems[] = 'coupons ts=' . json_encode($expCodes) . ' php=' . json_encode($gotCodes);
	}

	$v = $vector['validate'];
	$val = $engine->validate_coupon($v['code'], (float) $v['subtotal'], (int) $v['qty'], $coupons, $v['existing'], 10.0);
	if ($val['valid'] !== $v['result']['valid'] || ($val['error'] ?? null) !== $v['result']['error']) {
		$problems[] = 'validate ' . $v['code'] . ' ts=' . json_encode($v['result']) . ' php=' . json_encode(array('valid' => $val['valid'], 'error' => $val['error'] ?? null));
	}

	if ($problems) {
		$failures++;
		if ($failures <= 20) {
			fwrite(STDERR, "#$i " . json_encode($in) . "\n  " . implode("\n  ", $problems) . "\n");
		}
	}
}

printf("%d vectors, %d mismatches\n", count($vectors), $failures);
exit($failures === 0 ? 0 : 1);
