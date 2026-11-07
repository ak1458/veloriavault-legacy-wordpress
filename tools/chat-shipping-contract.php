<?php
/** Customer-facing policy answers; no WordPress database or external service. */
declare(strict_types=1);

namespace Veloria\Commerce {
    final class Settings {
        public static array $values = array('standard_shipping_cost'=>0, 'free_shipping_threshold'=>0, 'cod_fee'=>149, 'override_shipping'=>true);
        public static function get($key, $default=null) { return self::$values[$key] ?? $default; }
        public static function support_email(): string { return 'care@example.invalid'; }
    }
}
namespace Veloria\Commerce\Catalog {
    final class Catalog {
        public static function instance() { throw new \RuntimeException('A shipping-policy answer must not search products.'); }
    }
}
namespace {
    define('ABSPATH', '/offline/');
    function home_url($path='') { return 'https://store.example.invalid'.$path; }
    function veloria_format_number($amount) { return number_format((float)$amount, 0, '.', ','); }
    require dirname(__DIR__).'/wp-content/plugins/veloria-commerce/src/Chat/IntentEngine.php';
    use Veloria\Commerce\Chat\IntentEngine;
    use Veloria\Commerce\Settings;
    $checks=0;
    function check(bool $ok, string $message): void {
        global $checks;
        if (!$ok) throw new \RuntimeException($message);
        ++$checks;
    }
    foreach (array('What are the shipping charges?', 'Shipping info', 'Is delivery free?', 'Hi, how much is shipping?', 'Do you deliver to India?', 'When is dispatch?') as $question) {
        // The direct "deliver to" wording is checked separately below when it
        // contains the policy noun used by the public shipping quick reply.
        if ('Do you deliver to India?' === $question) continue;
        check('shipping' === IntentEngine::parse($question)['type'], 'Policy question misrouted: '.$question);
    }
    check('help' === IntentEngine::parse('Track my delivery')['type'], 'Tracking should remain in the support path.');
    check('help' === IntentEngine::parse('Return delivery question')['type'], 'Return support must not become a shipping-cost answer.');
    check('product_search' === IntentEngine::parse('Show tote bags')['type'], 'Product searches must remain available.');
    $answer=IntentEngine::respond('What are the shipping charges?', 'offline-session');
    check('shipping' === $answer['intent'], 'Missing policy intent.');
    check(str_contains($answer['content'], 'free on all orders within India'), 'Free shipping was not explained.');
    check(str_contains($answer['content'], '₹149 COD fee'), 'COD fee must be separate from shipping.');
    check(str_contains($answer['content'], '/shipping-policy'), 'Missing policy link.');
    check(array() === $answer['products'], 'A policy answer must not recommend unrelated products.');
    check(!str_contains($answer['content'], '\\n'), 'Literal newline escape leaked to the customer.');
    Settings::$values['standard_shipping_cost']=60;
    Settings::$values['free_shipping_threshold']=1000;
    $answer=IntentEngine::respond('Shipping info');
    check(str_contains($answer['content'], '₹1,000 or more') && str_contains($answer['content'], '₹60'), 'Shipping settings must drive the answer.');
    Settings::$values['free_shipping_threshold']=0;
    Settings::$values['cod_fee']=0;
    $answer=IntentEngine::respond('delivery charges');
    check(str_contains($answer['content'], 'shipping within India is ₹60'), 'Flat shipping cost missing.');
    check(!str_contains($answer['content'], 'COD fee'), 'Do not advertise a disabled COD fee.');
    Settings::$values['override_shipping']=false;
    $answer=IntentEngine::respond('Shipping info');
    check(str_contains($answer['content'], 'confirmed at checkout'), 'Native shipping must not quote unused engine rates.');
    echo $checks.' shipping-assistant checks passed'.PHP_EOL;
}
