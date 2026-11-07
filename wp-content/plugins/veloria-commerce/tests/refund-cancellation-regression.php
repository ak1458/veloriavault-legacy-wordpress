<?php
/** Offline refund/cancellation regressions. Every provider call is mocked; no database, network or customer data. */
declare(strict_types=1);
define('ABSPATH','/tmp/veloria-refund-tests/');
define('MINUTE_IN_SECONDS',60);define('HOUR_IN_SECONDS',3600);define('DAY_IN_SECONDS',86400);
define('VELORIA_RAZORPAY_KEY_ID','offline_fixture_key');define('VELORIA_RAZORPAY_KEY_SECRET','offline_fixture_secret');
$GLOBALS['opts']=[];$GLOBALS['orders']=[];$GLOBALS['calls']=[];$GLOBALS['events']=[];$GLOBALS['ledger']=[];$GLOBALS['ledger_fail']=false;
$GLOBALS['provider']=[];$GLOBALS['rid']=0;

class WC_Order {
 public array $meta=[];public array $notes=[];public array $refunds=[];public string $status='processing';public string $created='-1 hour';public string $method='razorpay';public bool $paid=true;
 public function __construct(public int $id){}
 public function get_id(){return $this->id;}public function get_order_number(){return (string)$this->id;}
 public function get_meta($k){return $this->meta[$k]??'';}public function update_meta_data($k,$v){$this->meta[$k]=$v;}public function delete_meta_data($k){unset($this->meta[$k]);}
 public function get_status(){return $this->status;}public function get_date_created(){return ''===$this->created?null:new DateTimeImmutable($this->created);}public function get_date_paid(){return $this->paid?new DateTimeImmutable($this->created):null;}
 public function get_payment_method(){return $this->method;}public function get_transaction_id(){return 'razorpay'===$this->method?'pay_Fixture'.$this->id:'';}public function get_currency(){return 'INR';}
 public function get_total(){return '1274.00';}public function get_total_refunded(){return array_sum(array_map(fn($r)=>$r->amount,$this->refunds));}public function get_refunds(){return $this->refunds;}public function is_paid(){return $this->paid;}
 public array $items=[];public function get_items($types=null){return $this->items;}public function get_payment_method_title(){return 'razorpay'===$this->method?'Razorpay':'Cash on delivery';}
 public function update_status($s,$n=''){$this->status=$s;$this->notes[]=$n;}public function add_order_note($n,$c=false){$this->notes[]=$n;}public function save(){return $this->id;}
}
class WC_Order_Refund extends WC_Order { public float $amount=0;public string $reason='';public function get_reason(){return $this->reason;} }
class WP_Error { public function __construct(private string $code='', private string $message='error'){} public function get_error_message(){return $this->message;} }

function get_option($k,$d=false){return $GLOBALS['opts'][$k]??$d;}function update_option($k,$v,...$a){$GLOBALS['opts'][$k]=$v;return true;}
function get_site_option($k,$d=false){return $d;}
function get_current_user_id(){return $GLOBALS['actor']??7;}function current_user_can(...$args){$caps=$GLOBALS['caps']??true;return is_array($caps)?($caps[$args[0]]??false):$caps;}
function sanitize_text_field($v){return strip_tags($v);}
function sanitize_textarea_field($v){return strip_tags($v);}
function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower($v));}
function wp_unslash($v){return stripslashes($v);}function wp_verify_nonce($v,$action){return 'fixture-nonce'===$v&&(str_starts_with($action,'veloria_order_tools_')||str_starts_with($action,'veloria_full_source_refund_'));}
function wp_date($format,$at){return gmdate($format,$at);}function set_transient($k,$v,$duration){$GLOBALS['transients'][$k]=$v;return true;}
function add_option($k,$v,...$a){if(array_key_exists($k,$GLOBALS['opts']))return false;$GLOBALS['opts'][$k]=$v;return true;}function delete_option($k){unset($GLOBALS['opts'][$k]);return true;}
function apply_filters($k,$v,...$a){return $v;}function is_email($v){return false!==filter_var($v,FILTER_VALIDATE_EMAIL);}
function wc_get_order($id){return $GLOBALS['orders'][$id]??null;}
function wc_get_logger(){return new class{function error(...$a){}function warning(...$a){}};}
function is_wp_error($v){return $v instanceof WP_Error;}function wp_json_encode($v){return json_encode($v);}
function wp_remote_retrieve_response_code($r){return $r['code'];}function wp_remote_retrieve_body($r){return json_encode($r['data']);}
function wp_remote_get($u,$a){return transport('GET',$u,$a);}function wp_remote_post($u,$a){return transport('POST',$u,$a);}
function wp_next_scheduled($h,$args){foreach($GLOBALS['events'] as $e)if($e['hook']===$h&&$e['args']===$args)return $e['at'];return false;}
function wp_schedule_single_event($t,$h,$args,...$extra){if(!empty($GLOBALS['schedule_fail']))return new WP_Error('schedule','failed');$GLOBALS['events'][]=['at'=>$t,'hook'=>$h,'args'=>$args];return true;}
function wp_clear_scheduled_hook($hook,$args=[]){$before=count($GLOBALS['events']);$GLOBALS['events']=array_values(array_filter($GLOBALS['events'],fn($e)=>$e['hook']!==$hook||$e['args']!==$args));return $before-count($GLOBALS['events']);}
function wc_format_decimal($v,$precision=2){return number_format((float)$v,$precision,'.','');}
function wc_create_refund($args){if($GLOBALS['ledger_fail'])return new WP_Error('x','ledger');$o=$GLOBALS['orders'][$args['order_id']];$r=new WC_Order_Refund(1000+count($GLOBALS['ledger']));$r->amount=(float)$args['amount'];$r->reason=$args['reason'];$o->refunds[]=$r;$GLOBALS['ledger'][]=$args;return $r;}

/** Provider fixture: $GLOBALS['provider'][paymentId] = ['refunds'=>[...], 'post'=>callable]. */
function transport(string $method,string $url,array $args){
 $path=parse_url($url,PHP_URL_PATH);$GLOBALS['calls'][]=['method'=>$method,'path'=>$path,'key'=>$args['headers']['X-Refund-Idempotency']??null,'body'=>$args['body']??null];
 if(preg_match('#^/v1/payments/(pay_\w+)$#',$path,$m)&&'GET'===$method){$p=&$GLOBALS['provider'][$m[1]];$refunded=array_sum(array_map(fn($r)=>'failed'===$r['status']?0:$r['amount'],$p['refunds']));return ['code'=>200,'data'=>['id'=>$m[1],'entity'=>'payment','status'=>'captured','captured'=>true,'currency'=>'INR','amount'=>127400,'amount_refunded'=>$refunded]];}
 if(preg_match('#^/v1/payments/(pay_\w+)/refunds$#',$path,$m)){$p=$GLOBALS['provider'][$m[1]];if(!empty($p['list_down']))return new WP_Error('x','down');$items=array_map(fn($r)=>$r+['entity'=>'refund','payment_id'=>$m[1],'currency'=>'INR'],$p['refunds']);return ['code'=>200,'data'=>['entity'=>'collection','count'=>count($items),'items'=>$items]];}
 if(preg_match('#^/v1/payments/(pay_\w+)/refund$#',$path,$m)&&'POST'===$method){return ($GLOBALS['provider'][$m[1]]['post'])($m[1],$args);}
 if(preg_match('#^/v1/refunds/(rfnd_\w+)$#',$path,$m)){foreach($GLOBALS['provider'] as $pid=>$p)foreach($p['refunds'] as $r)if($r['id']===$m[1])return ['code'=>200,'data'=>$r+['entity'=>'refund','payment_id'=>$pid,'currency'=>'INR']];return ['code'=>404,'data'=>[]];}
 throw new RuntimeException('Unexpected HTTP '.$method.' '.$path);
}
function create_refund(string $pid,string $status='processed'):array{$r=['id'=>'rfnd_Fx'.(++$GLOBALS['rid']),'amount'=>127400,'status'=>$status];$GLOBALS['provider'][$pid]['refunds'][]=$r;return ['code'=>200,'data'=>$r+['entity'=>'refund','payment_id'=>$pid,'currency'=>'INR']];}
$insufficient=fn($pid,$a)=>['code'=>400,'data'=>['error'=>['code'=>'BAD_REQUEST_ERROR','description'=>'Your account does not have enough balance to carry out the refund operation.']]];
$processed=fn($pid,$a)=>create_refund($pid);
$pending=fn($pid,$a)=>create_refund($pid,'pending');

require dirname(__DIR__).'/src/Settings.php';
require dirname(__DIR__).'/src/Payments/Razorpay.php';
require dirname(__DIR__).'/src/Orders/OrderService.php';
require dirname(__DIR__).'/src/Shipping/Shiprocket.php';
require dirname(__DIR__).'/src/Shipping/ShiprocketSync.php';
require dirname(__DIR__).'/src/Checkout/CheckoutHandler.php';
require dirname(__DIR__).'/src/Admin/OrderTools.php';
require dirname(__DIR__).'/src/Admin/SimpleRefund.php';
use Veloria\Commerce\Orders\OrderService;

$passed=0;
function check($name,$ok){global $passed,$r,$o;if(!$ok){var_export([$r??null,isset($o)?[$o->status,$o->meta]:null]);echo PHP_EOL;}if(!$ok)throw new RuntimeException('FAIL: '.$name);++$passed;echo 'PASS: '.$name.PHP_EOL;}
function fixture(int $id,$post):WC_Order{$o=new WC_Order($id);$o->meta=['_veloria_native_checkout'=>'yes','_payment_status'=>'completed'];$GLOBALS['orders'][$id]=$o;$GLOBALS['provider']['pay_Fixture'.$id]=['refunds'=>[],'post'=>$post];return $o;}
function posts(int $id):array{return array_values(array_filter($GLOBALS['calls'],fn($c)=>'POST'===$c['method']&&str_contains($c['path'],'pay_Fixture'.$id.'/')));}

// 1. Insufficient balance: definite rejection, order held, no automatic retry.
$o=fixture(1,$insufficient);$r=OrderService::cancel($o);
check('balance rejection is not reported as success',!$r['success']&&409===$r['status']);
check('balance rejection records rejected state, not a stuck requesting marker','rejected'===$o->get_meta('_veloria_refund_state')&&''===$o->get_meta('_veloria_refund_key'));
check('rejected cancellation keeps order out of fulfillment','on-hold'===$o->status&&'yes'===$o->get_meta('_veloria_cancel_requested'));
check('refund POST carried an idempotency key',1===count(posts(1))&&str_starts_with((string)posts(1)[0]['key'],'vv1-'));
check('rejection flags staff attention',isset(get_option('veloria_refund_attention')[1]));
check('definite rejection schedules no background replay',!wp_next_scheduled(OrderService::CHECK_ACTION,[1]));
OrderService::scheduled_check(1);
check('background check never starts a new attempt after rejection',1===count(posts(1)));
// Merchant adds balance, customer/staff retries: new keyed attempt, one ledger entry, cancelled.
$GLOBALS['provider']['pay_Fixture1']['post']=$processed;$GLOBALS['ledger']=[];
$r=OrderService::staff_retry($o);
check('retry after funding refunds and cancels',$r['success']&&$r['refunded']&&'cancelled'===$o->status);
check('retry used a fresh idempotency key',2===count(posts(1))&&posts(1)[0]['key']!==posts(1)[1]['key']);
check('exactly one WooCommerce ledger entry',1===count($GLOBALS['ledger'])&&1274.0===(float)$GLOBALS['ledger'][0]['amount']);
check('attention cleared after completion',!isset(get_option('veloria_refund_attention')[1]));

// 2. Legacy state from #9553: requesting without key → review, no POST; staff confirmation unlocks.
$o=fixture(2,$processed);$o->meta+=['_veloria_refund_state'=>'requesting','_veloria_cancel_requested'=>'yes'];
$r=OrderService::cancel($o);
check('legacy unkeyed attempt is never replayed',!$r['success']&&0===count(posts(2))&&'review'===$o->get_meta('_veloria_refund_state'));
check('legacy review holds fulfillment','on-hold'===$o->status);
check('staff confirmation needs provider proof of no refund',OrderService::confirm_refund_rejected($o,7)&&'rejected'===$o->get_meta('_veloria_refund_state'));
$r=OrderService::staff_retry($o);
check('confirmed legacy order refunds once',$r['success']&&1===count(posts(2))&&'cancelled'===$o->status);
$o=fixture(3,$processed);$o->meta+=['_veloria_refund_state'=>'requesting','_veloria_cancel_requested'=>'yes'];create_refund('pay_Fixture3');
$r=OrderService::cancel($o);
check('legacy attempt that actually refunded is adopted, not repeated',$r['success']&&0===count(posts(3))&&str_starts_with($o->get_meta('_razorpay_refund_id'),'rfnd_'));
$o=fixture(4,$processed);$o->meta+=['_veloria_refund_state'=>'requesting','_veloria_cancel_requested'=>'yes'];create_refund('pay_Fixture4');
check('confirmation refused while provider shows a refund',!OrderService::confirm_refund_rejected($o,7));

// 3. Timeout after provider acceptance: idempotent replay with the same key, or adoption.
$o=fixture(5,fn($pid,$a)=>new WP_Error('timeout','cURL error 28'));$r=OrderService::cancel($o);
check('timeout keeps keyed requesting state','requesting'===$o->get_meta('_veloria_refund_state')&&''!==$o->get_meta('_veloria_refund_key')&&!$r['success']);
check('timeout schedules background verification',false!==wp_next_scheduled(OrderService::CHECK_ACTION,[5]));
$GLOBALS['provider']['pay_Fixture5']['post']=$processed;OrderService::scheduled_check(5);
check('replay reuses the original key and body',2===count(posts(5))&&posts(5)[0]['key']===posts(5)[1]['key']&&posts(5)[0]['body']===posts(5)[1]['body']);
check('background check completes the cancellation','cancelled'===$o->status);
$o=fixture(6,function($pid,$a){create_refund($pid);return new WP_Error('timeout','lost');});OrderService::cancel($o);OrderService::scheduled_check(6);
check('lost response with provider refund is adopted without a second POST',1===count(posts(6))&&'cancelled'===$o->status&&1===count($o->refunds));
$o=fixture(7,fn($pid,$a)=>['code'=>409,'data'=>['error'=>['code'=>'BAD_REQUEST_ERROR','description'=>'Request in progress']]]);OrderService::cancel($o);
check('409 in flight is unknown, not rejected','requesting'===$o->get_meta('_veloria_refund_state')&&''!==$o->get_meta('_veloria_refund_key'));
$o=fixture(8,$processed);$o->meta+=['_veloria_refund_state'=>'requesting','_veloria_refund_key'=>'vv8-x','_veloria_refund_amount'=>127400,'_veloria_cancel_requested'=>'yes'];
create_refund('pay_Fixture8');create_refund('pay_Fixture8');OrderService::cancel($o);
check('multiple provider refunds escalate to review',0===count(posts(8))&&'review'===$o->get_meta('_veloria_refund_state'));
$o=fixture(9,fn($pid,$a)=>['code'=>200,'data'=>['id'=>'rfnd_Bad','entity'=>'refund','payment_id'=>$pid,'amount'=>100,'currency'=>'INR','status'=>'processed']]);OrderService::cancel($o);
check('mismatched refund amount is unknown, never success','requesting'===$o->get_meta('_veloria_refund_state')&&''===$o->get_meta('_razorpay_refund_id'));

// 4. Pending → processed after the 48-hour window; pending → failed.
$o=fixture(10,$pending);OrderService::cancel($o);
check('pending refund is recorded and held','pending'===$o->get_meta('_veloria_refund_state')&&'on-hold'===$o->status&&false!==wp_next_scheduled(OrderService::CHECK_ACTION,[10]));
check('customer sees pending progress',str_contains((string)OrderService::refund_progress($o),'being processed'));
$o->created='-60 hours';$o->meta['_veloria_cancel_accepted_at']=time()-59*3600;
foreach($GLOBALS['provider']['pay_Fixture10']['refunds'] as &$x)$x['status']='processed';unset($x);
OrderService::scheduled_check(10);
check('accepted cancellation finishes after 48 hours','cancelled'===$o->status&&1===count($o->refunds));
$late=fixture(11,$processed);$late->created='-60 hours';$late_result=OrderService::cancel($late);
check('new request after 48 hours queues review without refund',$late_result['success']&&$late_result['pendingReview']&&!$late_result['refunded']&&0===count(posts(11)));
$o=fixture(12,$pending);OrderService::cancel($o);
foreach($GLOBALS['provider']['pay_Fixture12']['refunds'] as &$x)$x['status']='failed';unset($x);
OrderService::scheduled_check(12);
check('provider failed refund is represented as failed','failed'===$o->get_meta('_veloria_refund_state')&&''===$o->get_meta('_razorpay_refund_id'));
$before=count(posts(12));OrderService::scheduled_check(12);
check('failed refund is not retried automatically',$before===count(posts(12)));
$GLOBALS['provider']['pay_Fixture12']['post']=$processed;$r=OrderService::staff_retry($o);
check('staff retry after failure refunds once',$r['success']&&$before+1===count(posts(12)));

// 5. Ledger failure after provider success, then recovery without a second refund.
$o=fixture(13,$processed);$GLOBALS['ledger_fail']=true;$r=OrderService::cancel($o);
check('ledger failure reports processed refund needing record',!$r['success']&&502===$r['status']&&'processed'===$o->get_meta('_veloria_refund_state'));
$GLOBALS['ledger_fail']=false;$r=OrderService::staff_retry($o);
check('ledger recovery reads refund status and records once',$r['success']&&1===count(posts(13))&&1===count($o->refunds));

// 6. Concurrency and shipment guards.
$o=fixture(14,$processed);$GLOBALS['opts']['veloria_cancel_lock_14']=time();
check('concurrent cancellation is rejected without provider calls',409===OrderService::cancel($o)['status']&&0===count(posts(14)));
unset($GLOBALS['opts']['veloria_cancel_lock_14']);$GLOBALS['opts']['veloria_sr_lock_14']=time();
check('cancellation waits for in-flight shipment creation',409===OrderService::cancel($o)['status']&&0===count(posts(14))&&!isset($GLOBALS['opts']['veloria_cancel_lock_14']));
unset($GLOBALS['opts']['veloria_sr_lock_14']);
$o=fixture(15,$processed);$o->meta['awb']='AWB123';
$r=OrderService::cancel($o);
check('booked AWB without remote order ID queues review before refund','requested'===$o->get_meta('_veloria_cancel_review_state')&&$r['success']&&$r['pendingReview']&&0===count(posts(15)));
$o=fixture(16,$processed);$o->meta['_shiprocket_shipment_id']='999';
check('shipment ID without remote order ID requires cancellation review','review'===OrderService::is_cancellable($o)['mode']);
$o=fixture(17,$processed);$o->meta+=['_veloria_delivery_verified'=>'yes','_veloria_delivered_at'=>(string)(time()-60)];
check('delivered order is not cancellable',!OrderService::is_cancellable($o)['ok']);

// The staff legacy action must never erase keyed/in-flight identities.
$o=fixture(20,fn($pid,$a)=>new WP_Error('timeout','lost'));OrderService::cancel($o);
$key=$o->get_meta('_veloria_refund_key');$call_count=count($GLOBALS['calls']);
check('keyed unknown refund cannot be declared legacy rejected',!OrderService::confirm_refund_rejected($o,7)&&$key===$o->get_meta('_veloria_refund_key')&&$call_count===count($GLOBALS['calls']));
$o=fixture(21,$processed);$o->meta+=['_veloria_refund_state'=>'requesting','_veloria_cancel_requested'=>'yes'];
$GLOBALS['opts']['veloria_cancel_lock_21']=time();
check('legacy confirmation respects cancellation lock',!OrderService::confirm_refund_rejected($o,7)&&isset($GLOBALS['opts']['veloria_cancel_lock_21']));
unset($GLOBALS['opts']['veloria_cancel_lock_21']);$GLOBALS['opts']['veloria_sr_lock_21']=time();
check('legacy confirmation respects shipping lock and releases own lock',!OrderService::confirm_refund_rejected($o,7)&&!isset($GLOBALS['opts']['veloria_cancel_lock_21']));
unset($GLOBALS['opts']['veloria_sr_lock_21']);$GLOBALS['caps']=false;
check('legacy confirmation requires staff capability',!OrderService::confirm_refund_rejected($o,7));$GLOBALS['caps']=true;
check('legacy confirmation binds current actor',!OrderService::confirm_refund_rejected($o,8));

// Old protected cancellation markers continue a request, never start a late one.
$o=fixture(22,$processed);$o->created='-60 hours';$o->meta+=['_veloria_refund_state'=>'requesting','_veloria_cancel_requested'=>'yes'];
$r=OrderService::staff_retry($o);
check('aged legacy attempt reaches lookup but never blindly posts',!$r['success']&&'review'===$o->get_meta('_veloria_refund_state')&&0===count(posts(22)));
check('aged legacy confirmation and deliberate retry complete once',OrderService::confirm_refund_rejected($o,7)&&OrderService::staff_retry($o)['success']&&1===count(posts(22)));
$o=fixture(23,$processed);$o->created='-60 hours';$o->meta['_veloria_cancel_requested']='yes';
check('a bare cancellation marker does not authorize a late new refund',!OrderService::staff_retry($o)['success']&&0===count(posts(23)));

// WordPress removes one-shot events before callbacks; contention must restore it.
$o=fixture(24,$pending);OrderService::cancel($o);$GLOBALS['events']=[];
$before=(int)$o->get_meta('_veloria_refund_checks');$GLOBALS['opts']['veloria_cancel_lock_24']=time();
OrderService::scheduled_check(24);
check('busy callback reschedules without spending provider budget',wp_next_scheduled(OrderService::CHECK_ACTION,[24])&&(int)$o->get_meta('_veloria_refund_checks')===$before);
unset($GLOBALS['opts']['veloria_cancel_lock_24']);$GLOBALS['events']=[];$GLOBALS['schedule_fail']=true;$GLOBALS['opts']['veloria_sr_lock_24']=time();
OrderService::scheduled_check(24);
check('scheduling failure raises visible attention',isset(get_option('veloria_refund_attention')[24]));
unset($GLOBALS['opts']['veloria_sr_lock_24']);$GLOBALS['schedule_fail']=false;

// Official imports may exist without any custom local mapping. Never skip them.
$GLOBALS['opts']['active_plugins']=['shiprocket/shiprocket.php'];
$o=fixture(25,$processed);$r=OrderService::cancel($o);
check('official channel without mapping holds cancellation before refund',!$r['success']&&'on-hold'===$o->status&&0===count(posts(25)));
check('explicit staff verified courier reference permits one refund',OrderService::staff_retry($o,'Channel 10749746 order 25 cancelled, support SR123')['success']&&1===count(posts(25)));
check('one-use courier proof is consumed and actor recorded',''===$o->get_meta('_veloria_sr_staff_clearance')&&7===$o->get_meta('_veloria_sr_cancel_staff_actor'));
$o=fixture(26,$pending);OrderService::cancel($o);$r=OrderService::staff_retry($o,'Channel order26 cancelled, ref SR126');
check('verified pending refund keeps courier evidence',!$r['success']&&'pending'===$o->get_meta('_veloria_refund_state'));
$GLOBALS['provider']['pay_Fixture26']['refunds'][0]['status']='processed';$GLOBALS['events']=[];OrderService::scheduled_check(26);
check('background finishes verified courier stop without another staff action','cancelled'===$o->status&&1===count(posts(26)));
$o=fixture(27,$processed);OrderService::cancel($o);$o->meta['awb']='new-awb';
check('new booking invalidates previously verified stop',!\Veloria\Commerce\Shipping\Shiprocket::has_verified_cancellation($o));
$GLOBALS['opts']['active_plugins']=[];

$o=fixture(28,$processed);$o->meta+=['_return_requested'=>'yes','_return_requested_at'=>gmdate('c'),'_return_reason'=>'Wrong item','_veloria_return_decision'=>'approved'];
$dto=OrderService::dto($o);
check('order DTO exposes actual return state without allowing a duplicate request',$dto['returnRequested']&&'approved'===$dto['returnState']&&'Wrong item'===$dto['returnRequestReason']&&!$dto['canReturn']);

// 7. The automatic window is 48 hours, including an install with the old saved 24.
check('new installs use the 48-hour cancellation policy',48===\Veloria\Commerce\Settings::get('cancel_window_hours'));
$GLOBALS['opts'][\Veloria\Commerce\Settings::OPTION]=['cancel_window_hours'=>24];\Veloria\Commerce\Settings::flush();
$now=1800000000;$o=fixture(30,$processed);$o->created='@'.($now-48*HOUR_IN_SECONDS);
check('persisted legacy 24-hour setting upgrades without rewriting it',48===\Veloria\Commerce\Settings::get('cancel_window_hours')&&24===$GLOBALS['opts'][\Veloria\Commerce\Settings::OPTION]['cancel_window_hours']);
check('exactly 48 hours remains eligible for automatic cancellation',OrderService::is_cancellable($o,$now)['ok']);
check('one second after 48 hours needs review','review'===OrderService::is_cancellable($o,$now+1)['mode']);
$o->created='-30 hours';$actions=OrderService::actions($o);
check('orders between the old and new cutoff expose automatic action',$actions['canCancel']&&'automatic'===$actions['cancelMode']&&null===$actions['cancelReviewState']);
\Veloria\Commerce\Settings::save(['cancel_window_hours'=>24]);$o->created='@'.($now-24*HOUR_IN_SECONDS);
check('an explicitly saved current-policy window is respected',24===\Veloria\Commerce\Settings::get('cancel_window_hours')&&'automatic'===OrderService::is_cancellable($o,$now)['mode']&&'review'===OrderService::is_cancellable($o,$now+1)['mode']);
\Veloria\Commerce\Settings::save(['cancel_window_hours'=>48]);

// 8. A late request queues one review and holds fulfillment without touching providers.
$GLOBALS['opts']['active_plugins']=['shiprocket/shiprocket.php'];
$o=fixture(31,$processed);$o->created='-60 hours';$o->meta['_shiprocket_order_id']='3100';$actions=OrderService::actions($o);
check('eligible late order offers a cancellation review',$actions['canCancel']&&'review'===$actions['cancelMode']&&null===$actions['cancelReviewState']);
$before=count($GLOBALS['calls']);$r=OrderService::cancel($o);
check('late request returns a truthful pending-review result',$r['success']&&$r['pendingReview']&&!$r['refunded']&&''!==($r['message']??''));
check('late review records request without accepted automatic timestamp','requested'===$o->get_meta('_veloria_cancel_review_state')&&(int)$o->get_meta('_veloria_cancel_review_requested_at')>0&&'yes'===$o->get_meta('_veloria_cancel_requested')&&''===$o->get_meta('_veloria_cancel_accepted_at')&&'on-hold'===$o->status);
check('late review makes no courier or payment calls',$before===count($GLOBALS['calls'])&&''===$o->get_meta('_shiprocket_cancel_state')&&''===$o->get_meta('_veloria_refund_state'));
$requested=$o->get_meta('_veloria_cancel_review_requested_at');$notes=count($o->notes);$r=OrderService::cancel($o);$actions=OrderService::actions($o);
check('duplicate late requests preserve one review',$r['success']&&$r['pendingReview']&&$requested===$o->get_meta('_veloria_cancel_review_requested_at')&&$notes===count($o->notes)&&$before===count($GLOBALS['calls']));
check('pending review disables duplicate customer action',!$actions['canCancel']&&'review'===$actions['cancelMode']&&'requested'===$actions['cancelReviewState']&&str_contains(strtolower((string)$actions['refundProgress']),'review'));
$GLOBALS['events']=[];OrderService::scheduled_check(31);$retry=OrderService::staff_retry($o);
check('worker and generic retry cannot approve a queued review',!$retry['success']&&'requested'===$o->get_meta('_veloria_cancel_review_state')&&$before===count($GLOBALS['calls'])&&false===wp_next_scheduled(OrderService::CHECK_ACTION,[31]));

// 9. Staff review decisions require both capabilities, the current actor and locks.
$GLOBALS['caps']=['manage_woocommerce'=>false,'edit_shop_order'=>true];$r=OrderService::decide_cancel_review($o,'approved',7,'Customer requested cancellation.');
check('review approval requires manage WooCommerce capability',!$r['success']&&403===$r['status']&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
$GLOBALS['caps']=['manage_woocommerce'=>true,'edit_shop_order'=>false];$r=OrderService::decide_cancel_review($o,'declined',7,'Courier is already in transit.');
check('review decline requires order editing capability',!$r['success']&&403===$r['status']&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
$GLOBALS['caps']=true;$r=OrderService::decide_cancel_review($o,'approved',8,'Customer requested cancellation.');
check('review decision binds the authenticated actor',!$r['success']&&403===$r['status']&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
$r=OrderService::decide_cancel_review($o,'approved',7,'   ');
check('review decision requires a recorded reason',!$r['success']&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
$GLOBALS['opts']['veloria_cancel_lock_31']=time();$r=OrderService::decide_cancel_review($o,'approved',7,'Customer requested cancellation.');
check('review approval respects an existing cancellation lock',!$r['success']&&409===$r['status']&&isset($GLOBALS['opts']['veloria_cancel_lock_31'])&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
unset($GLOBALS['opts']['veloria_cancel_lock_31']);$GLOBALS['opts']['veloria_sr_lock_31']=time();$r=OrderService::decide_cancel_review($o,'declined',7,'Courier is already in transit.');
check('review decline respects shipping lock and releases own lock',!$r['success']&&409===$r['status']&&!isset($GLOBALS['opts']['veloria_cancel_lock_31'])&&isset($GLOBALS['opts']['veloria_sr_lock_31'])&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
unset($GLOBALS['opts']['veloria_sr_lock_31']);
$GLOBALS['opts']['veloria_cancel_lock_31']=time();$r=OrderService::decide_cancel_review($o,'declined',7,'Courier is already in transit.');
check('review decline also respects cancellation lock',!$r['success']&&409===$r['status']&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
unset($GLOBALS['opts']['veloria_cancel_lock_31']);$GLOBALS['opts']['veloria_sr_lock_31']=time();$r=OrderService::decide_cancel_review($o,'approved',7,'Customer requested cancellation.');
check('review approval also respects shipping lock',!$r['success']&&409===$r['status']&&!isset($GLOBALS['opts']['veloria_cancel_lock_31'])&&'requested'===$o->get_meta('_veloria_cancel_review_state'));
unset($GLOBALS['opts']['veloria_sr_lock_31']);

// 10. Approved late cancellation still needs a courier stop before a source refund.
$r=OrderService::decide_cancel_review($o,'approved',7,'Customer asked before dispatch.');
check('late approval cannot bypass courier confirmation',!$r['success']&&'approved'===$o->get_meta('_veloria_cancel_review_state')&&'on-hold'===$o->status&&0===count(posts(31)));
check('approval records staff attribution without inventing an in-window acceptance',7===$o->get_meta('_veloria_cancel_review_decided_by')&&(int)$o->get_meta('_veloria_cancel_review_decided_at')>0&&''!==$o->get_meta('_veloria_cancel_review_reason')&&''===$o->get_meta('_veloria_cancel_accepted_at'));
$r=OrderService::staff_retry($o,'Channel order 3100 cancelled before dispatch, ref SR3100');
check('verified approved late request refunds its original payment once',$r['success']&&$r['refunded']&&'cancelled'===$o->status&&1===count(posts(31))&&1===count($o->refunds)&&str_contains(posts(31)[0]['path'],'pay_Fixture31/refund'));
$r=OrderService::cancel($o);$duplicate=OrderService::decide_cancel_review($o,'approved',7,'Duplicate request.');
check('completed approved cancellation cannot create a second refund',$r['success']&&1===count(posts(31))&&1===count($o->refunds));

// 11. A declined review leaves the order held for staff and sends no money.
$o=fixture(32,$processed);$o->created='-60 hours';OrderService::cancel($o);$before=count($GLOBALS['calls']);
$r=OrderService::decide_cancel_review($o,'declined',7,'<b>Courier already in transit.</b>');$notes=count($o->notes);
check('declined review records a sanitized decision and keeps fulfillment held',$r['success']&&'declined'===$o->get_meta('_veloria_cancel_review_state')&&'on-hold'===$o->status&&''===$o->get_meta('_veloria_cancel_requested')&&'Courier already in transit.'===$o->get_meta('_veloria_cancel_review_reason'));
$r=OrderService::decide_cancel_review($o,'declined',7,'Duplicate decline.');$actions=OrderService::actions($o);OrderService::scheduled_check(32);$retry=OrderService::cancel($o);
check('decline is idempotent and cannot reopen through customer or worker',!$retry['success']&&$notes===count($o->notes)&&!$actions['canCancel']&&null===$actions['cancelMode']&&'declined'===$actions['cancelReviewState']&&$before===count($GLOBALS['calls']));
$r=OrderService::decide_cancel_review($o,'approved',7,'Attempt to overwrite decision.');
check('declined decision cannot be overwritten by a later approval',!$r['success']&&'declined'===$o->get_meta('_veloria_cancel_review_state')&&$before===count($GLOBALS['calls']));

// 12. Approved late pending/rejected/unknown refunds keep existing recovery semantics.
$GLOBALS['opts']['active_plugins']=[];
$o=fixture(33,$pending);$o->created='-60 hours';OrderService::cancel($o);$r=OrderService::decide_cancel_review($o,'approved',7,'Customer requested before dispatch.');
check('approved late refund pending at provider is never marked completed',!$r['success']&&'pending'===$o->get_meta('_veloria_refund_state')&&'on-hold'===$o->status&&1===count(posts(33)));
$GLOBALS['provider']['pay_Fixture33']['refunds'][0]['status']='processed';$o->created='-90 hours';$GLOBALS['events']=[];OrderService::scheduled_check(33);
check('approved late refund finishes later without a second approval or payment','cancelled'===$o->status&&1===count(posts(33))&&1===count($o->refunds)&&''===$o->get_meta('_veloria_cancel_accepted_at'));
$o=fixture(34,$insufficient);$o->created='-60 hours';OrderService::cancel($o);OrderService::decide_cancel_review($o,'approved',7,'Customer requested before dispatch.');$key=$o->get_meta('_veloria_refund_key');$before=count(posts(34));OrderService::scheduled_check(34);
check('approved late definite rejection is held without automatic replay','rejected'===$o->get_meta('_veloria_refund_state')&&'approved'===$o->get_meta('_veloria_cancel_review_state')&&'on-hold'===$o->status&&$before===count(posts(34)));
$r=OrderService::decide_cancel_review($o,'approved',7,'Repeated approval after payment rejection.');
check('repeated approval cannot reset a rejected provider attempt','rejected'===$o->get_meta('_veloria_refund_state')&&$key===$o->get_meta('_veloria_refund_key')&&$before===count(posts(34)));
$GLOBALS['provider']['pay_Fixture34']['post']=$processed;$r=OrderService::staff_retry($o);
check('deliberate funded retry completes approved late refund once',$r['success']&&$r['refunded']&&$before+1===count(posts(34))&&1===count($o->refunds));
$o=fixture(35,fn($pid,$a)=>new WP_Error('timeout','lost'));$o->created='-60 hours';OrderService::cancel($o);OrderService::decide_cancel_review($o,'approved',7,'Customer requested before dispatch.');$key=$o->get_meta('_veloria_refund_key');
check('approved late unknown outcome preserves the idempotency identity','requesting'===$o->get_meta('_veloria_refund_state')&&''!==$key&&'on-hold'===$o->status&&1===count(posts(35)));
$r=OrderService::decide_cancel_review($o,'approved',7,'Repeated approval while response is unknown.');
check('repeated approval cannot erase an unknown provider identity','requesting'===$o->get_meta('_veloria_refund_state')&&$key===$o->get_meta('_veloria_refund_key')&&1===count(posts(35)));
$GLOBALS['provider']['pay_Fixture35']['post']=$processed;OrderService::scheduled_check(35);
check('approved unknown continuation reuses the same source refund identity','cancelled'===$o->status&&2===count(posts(35))&&$key===posts(35)[1]['key']&&posts(35)[0]['body']===posts(35)[1]['body']&&1===count($o->refunds));

// 13. Delivery/return safeguards survive the policy change; unpaid COD moves no money.
foreach (['delivered','returned'] as $kind) {
 $o=fixture('delivered'===$kind?36:37,$processed);$o->created='-60 hours';
 if ('delivered'===$kind) $o->meta+=['_veloria_delivery_verified'=>'yes','_veloria_delivered_at'=>(string)(time()-60)]; else $o->meta['_return_requested']='yes';
 $before=count($GLOBALS['calls']);$r=OrderService::cancel($o);$actions=OrderService::actions($o);
 check($kind.' order cannot enter instant cancellation or late review',!$r['success']&&!$actions['canCancel']&&null===$actions['cancelMode']&&''===$o->get_meta('_veloria_cancel_review_state')&&$before===count($GLOBALS['calls']));
}
$o=fixture(38,$processed);$o->method='cod';$o->paid=false;$o->meta['_payment_status']='pending';$before=count($GLOBALS['calls']);$r=OrderService::cancel($o);
check('unpaid COD cancellation never creates a paid refund',$r['success']&&!$r['refunded']&&'cancelled'===$o->status&&0===count($o->refunds)&&$before===count($GLOBALS['calls']));
$o=fixture(39,$processed);$o->created='-60 hours';$o->method='cod';$o->paid=false;$o->meta['_payment_status']='pending';OrderService::cancel($o);$before=count($GLOBALS['calls']);$r=OrderService::decide_cancel_review($o,'approved',7,'Unshipped COD order cancelled.');
check('approved late unpaid COD cancellation creates no payment refund',$r['success']&&!$r['refunded']&&'cancelled'===$o->status&&0===count($o->refunds)&&$before===count($GLOBALS['calls']));
$o=fixture(40,$processed);$o->created='';$r=OrderService::cancel($o);$actions=OrderService::actions($o);
check('missing order date never creates an automatic refund or late review',!$r['success']&&!$actions['canCancel']&&null===$actions['cancelMode']&&0===count(posts(40)));
$o=fixture(41,$processed);$o->created='+1 hour';$r=OrderService::cancel($o);$actions=OrderService::actions($o);
check('future order date never creates an automatic refund or late review',!$r['success']&&!$actions['canCancel']&&null===$actions['cancelMode']&&0===count(posts(41)));
$o=fixture(42,$processed);$o->meta['awb']='BOOKED42';$actions=OrderService::actions($o);$before=count($GLOBALS['calls']);$r=OrderService::cancel($o);
check('early booked order offers review and queues without moving money',$actions['canCancel']&&'review'===$actions['cancelMode']&&$r['success']&&$r['pendingReview']&&!$r['refunded']&&'requested'===$o->get_meta('_veloria_cancel_review_state')&&$before===count($GLOBALS['calls']));
$o=fixture(43,$processed);$o->method='cod';$before=count($GLOBALS['calls']);$r=OrderService::cancel($o);
check('COD marked paid requires staff refund handling without a source POST',!$r['success']&&'cancelled'!==$o->status&&0===count($o->refunds)&&$before===count($GLOBALS['calls']));

// 14. Newly recorded delivery or return evidence blocks an already queued approval.
foreach (['delivered','returned'] as $kind) {
 $o=fixture('delivered'===$kind?44:45,$processed);$o->created='-60 hours';OrderService::cancel($o);
 if ('delivered'===$kind) $o->meta+=['_veloria_delivery_verified'=>'yes','_veloria_delivered_at'=>(string)(time()-60)]; else $o->meta['_return_requested']='yes';
 $before=count($GLOBALS['calls']);$r=OrderService::decide_cancel_review($o,'approved',7,'Customer requested before dispatch.');
 check('new '.$kind.' evidence blocks queued review approval without payment',!$r['success']&&'requested'===$o->get_meta('_veloria_cancel_review_state')&&$before===count($GLOBALS['calls']));
}

// 15. A delayed payment callback cannot resume a held cancellation or resurrect it.
foreach (['requested','approved','declined'] as $review) {
 $id=46+array_search($review,['requested','approved','declined'],true);$o=fixture($id,$processed);$o->status='on-hold';$o->meta['_veloria_cancel_review_state']=$review;
 if ('declined'!==$review) $o->meta['_veloria_cancel_requested']='yes';
 check('late payment keeps '.$review.' cancellation on hold','on-hold'===\Veloria\Commerce\Checkout\CheckoutHandler::payment_complete_status('processing',$id,$o));
}
foreach (['cancelled','refunded'] as $terminal) {
 $id='cancelled'===$terminal?49:50;$o=fixture($id,$processed);$o->status=$terminal;$o->meta+=['_veloria_cancel_requested'=>'yes','_veloria_cancel_review_state'=>'approved','_veloria_refund_state'=>'processed'];
 check('late payment preserves '.$terminal.' cancellation status',$terminal===\Veloria\Commerce\Checkout\CheckoutHandler::payment_complete_status('processing',$id,$o));
}
$o=fixture(51,$processed);
check('ordinary payment completion status is unchanged','completed'===\Veloria\Commerce\Checkout\CheckoutHandler::payment_complete_status('completed',51,$o));
$o->meta['_veloria_cancel_requested']='yes';
check('payment guard loads the order when WooCommerce omits its object','on-hold'===\Veloria\Commerce\Checkout\CheckoutHandler::payment_complete_status('processing',51));
check('missing payment order leaves supplied status unchanged','processing'===\Veloria\Commerce\Checkout\CheckoutHandler::payment_complete_status('processing',99999));
foreach (['cancelled','refunded'] as $terminal) {
 $id='cancelled'===$terminal?52:53;$o=fixture($id,$processed);$o->status=$terminal;$o->meta['_veloria_cancel_review_state']='declined';
 check('late payment preserves staff '.$terminal.' order after declined review',$terminal===\Veloria\Commerce\Checkout\CheckoutHandler::payment_complete_status('processing',$id,$o));
}
$o=fixture(54,$processed);$stale=clone $o;$o->status='on-hold';$o->meta+=['_veloria_cancel_requested'=>'yes','_veloria_cancel_review_state'=>'requested'];
check('late payment reloads review markers missing from a stale callback object','on-hold'===\Veloria\Commerce\Checkout\CheckoutHandler::payment_complete_status('processing',54,$stale));

// 16. A staff decision must not ignore changed delivery evidence in the same form.
$o=fixture(55,$processed);$o->created='-60 hours';OrderService::cancel($o);$before=count($GLOBALS['calls']);
$_POST=['veloria_order_tools_nonce'=>'fixture-nonce','veloria_cancel_review_decision'=>'approved','veloria_cancel_review_reason'=>'Customer requested before dispatch.','veloria_delivery_verified'=>'yes','veloria_delivered_at'=>gmdate('Y-m-d\TH:i',time()-120)];
\Veloria\Commerce\Admin\OrderTools::save(55);
check('same-form new delivery evidence blocks approval before any refund','requested'===$o->get_meta('_veloria_cancel_review_state')&&$before===count($GLOBALS['calls'])&&str_contains($GLOBALS['transients']['veloria_order_tools_error_7']??'','Save delivery evidence'));
$_POST['veloria_delivered_at']='';unset($_POST['veloria_delivery_verified']);\Veloria\Commerce\Admin\OrderTools::save(55);
check('unchanged full order form allows authorized review approval','approved'===$o->get_meta('_veloria_cancel_review_state')&&'cancelled'===$o->status&&1===count(posts(55))&&1===count($o->refunds));
$_POST=[];
// Easy refund: real paid amount, signed fees/taxes, explicit confirmation, provider reconciliation.
function refund_items():array {
 $make=fn($total,$tax)=>new class($total,$tax){function __construct(private float $total,private float $tax){}function get_total(){return $this->total;}function get_quantity(){return 1;}function get_taxes(){return ['total'=>[1=>$this->tax,2=>$this->tax]];}};
 return [101=>$make(1270.338983,114.3305085),102=>$make(-63.559322,-5.720339),103=>$make(-127.118644,-11.440678),104=>$make(0,0)];
}
function return_fixture($id,$post):WC_Order{$o=fixture($id,$post);$o->items=refund_items();$o->status='completed';$o->meta+=['_return_requested'=>'yes','_veloria_return_decision'=>'approved','_veloria_delivery_verified'=>'yes','_veloria_delivered_at'=>(string)(time()-3600)];return $o;}
$input=['nonce'=>'fixture-nonce','confirm'=>'yes','reason'=>'Inspected return approved','expected'=>'127400'];
$o=return_fixture(60,$processed);$lines=OrderService::full_refund_lines($o);
check('full ledger includes negative prepaid/tier fees and both GST components',4===count($lines)&&$lines[102]['refund_total']<0&&$lines[103]['refund_tax'][1]<0&&$lines[101]['qty']===1);
$sum=0;foreach($lines as $line)$sum+=$line['refund_total']+array_sum($line['refund_tax']);
check('1499 gross less discounts allocates exactly 1274 paid amount',127400===(int)round($sum*100));
$o->items=[];check('legacy line mismatch refuses an inaccurate tax ledger',is_wp_error(OrderService::full_refund_lines($o)));$o->items=refund_items();
$GLOBALS['caps']=false;$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,$input);$GLOBALS['caps']=true;
check('easy refund rejects unauthorized staff without provider calls',!$r['success']&&0===count(posts(60)));
$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,array_replace($input,['nonce'=>'bad']));
check('easy refund rejects invalid nonce',!$r['success']&&0===count(posts(60)));
$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,array_replace($input,['expected'=>'149900']));
check('browser cannot request gross 1499 instead of paid 1274',!$r['success']&&0===count(posts(60)));
$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,array_replace($input,['confirm'=>'no']));
check('easy refund requires explicit amount confirmation',!$r['success']&&0===count(posts(60)));
$o->meta['_veloria_return_decision']='';$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,$input);
check('return refund requires separately saved inspection approval',!$r['success']&&0===count(posts(60)));
$o->meta['_veloria_return_decision']='approved';$GLOBALS['opts']['veloria_sr_lock_60']=time();$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,$input);unset($GLOBALS['opts']['veloria_sr_lock_60']);
check('easy return refund respects courier lock',!$r['success']&&0===count(posts(60))&&!isset($GLOBALS['opts']['veloria_cancel_lock_60']));
$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,$input);$ledger=end($GLOBALS['ledger']);
check('approved return refunds captured 1274 once with all signed tax lines',$r['success']&&$r['refunded']&&1===count(posts(60))&&1274.0===(float)$ledger['amount']&&4===count($ledger['line_items'])&&!$ledger['refund_payment']&&!$ledger['restock_items']);
$r=\Veloria\Commerce\Admin\SimpleRefund::process(60,$input);
check('duplicate easy submit never issues another refund',!$r['success']&&1===count(posts(60))&&1===count($o->refunds));
$o=return_fixture(61,$pending);$r=\Veloria\Commerce\Admin\SimpleRefund::process(61,$input);
check('pending return never reports completed or creates premature ledger',!$r['success']&&0===count($o->refunds)&&'pending'===$o->get_meta('_veloria_refund_state'));
foreach($GLOBALS['provider']['pay_Fixture61']['refunds'] as &$x)$x['status']='processed';unset($x);
$r=\Veloria\Commerce\Admin\SimpleRefund::process(61,$input);
check('return check adopts processed reference without second provider POST',$r['success']&&1===count(posts(61))&&1===count($o->refunds));
$o=return_fixture(62,function($pid,$a){create_refund($pid);return new WP_Error('timeout','lost');});\Veloria\Commerce\Admin\SimpleRefund::process(62,$input);$key=$o->get_meta('_veloria_refund_key');$r=\Veloria\Commerce\Admin\SimpleRefund::process(62,$input);
check('uncertain return adopts provider refund without changing identity',$r['success']&&1===count(posts(62))&&$key===$o->get_meta('_veloria_refund_key')&&1===count($o->refunds));
$o=fixture(63,$processed);$o->items=refund_items();$o->created='-60 hours';$r=\Veloria\Commerce\Admin\SimpleRefund::process(63,$input);
check('new late staff cancellation queues review before moving money',$r['success']&&$r['pendingReview']&&0===count(posts(63)));
$r=\Veloria\Commerce\Admin\SimpleRefund::process(63,$input);
check('separate staff approval completes late source refund safely',$r['success']&&$r['refunded']&&1===count(posts(63)));
$o=return_fixture(64,$processed);$GLOBALS['ledger_fail']=true;$r=\Veloria\Commerce\Admin\SimpleRefund::process(64,$input);$GLOBALS['ledger_fail']=false;$r=\Veloria\Commerce\Admin\SimpleRefund::process(64,$input);
check('ledger repair after provider success never repeats money movement',$r['success']&&1===count(posts(64))&&1===count($o->refunds));
$o=return_fixture(65,$insufficient);$r=\Veloria\Commerce\Admin\SimpleRefund::process(65,$input);
check('return balance rejection leaves no refund ledger and releases locks',!$r['success']&&'rejected'===$o->get_meta('_veloria_refund_state')&&0===count($o->refunds)&&!isset($GLOBALS['opts']['veloria_cancel_lock_65'])&&!isset($GLOBALS['opts']['veloria_sr_lock_65']));

echo "{$passed} refund/cancellation regressions passed".PHP_EOL;
