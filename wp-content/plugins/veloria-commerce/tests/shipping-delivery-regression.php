<?php
/** Offline safety regressions. No WordPress database, mail or provider transport. */
declare(strict_types=1);
define('ABSPATH','/tmp/veloria-shipping-tests/');
define('MINUTE_IN_SECONDS',60);define('DAY_IN_SECONDS',86400);
define('VELORIA_SHIPROCKET_EMAIL','fixture@example.test');define('VELORIA_SHIPROCKET_PASSWORD','offline');
$GLOBALS['opts']=[];$GLOBALS['trans']=[];$GLOBALS['orders']=[];$GLOBALS['scheduled']=[];$GLOBALS['requests']=[];$GLOBALS['caps']=true;$GLOBALS['nonce']=true;
class WC_DateTime extends DateTimeImmutable { public function date($format){return $this->format($format);} }
class WC_Order {
 public array $meta=[];public array $notes=[];public int $saves=0;
 public function __construct(public int $id,public string $status='processing'){}
 public function get_id(){return $this->id;} public function get_meta($k){return $this->meta[$k]??'';}
 public function update_meta_data($k,$v){$this->meta[$k]=$v;}public function delete_meta_data($k){unset($this->meta[$k]);}
 public function add_order_note($n){$this->notes[]=$n;}public function save(){++$this->saves;return $this->id;}
 public function get_status(){return $this->status;}public function get_payment_method(){return 'cod';}public function is_paid(){return false;}
 public function get_date_created(){return new WC_DateTime('-5 days');}public function get_items(){return [new WC_Order_Item_Product()];}
 public function get_shipping_total(){return '0';}public function get_shipping_tax(){return '0';}public function get_subtotal(){return '1100';}public function get_total(){return '1049';}public function get_fees(){return [];}
 public function get_billing_email(){return 'fixture@example.test';}public function get_billing_state(){return 'DL';}
 public function __call($n,$args){if(str_starts_with($n,'get_billing_'))return 'Fixture';throw new RuntimeException($n);}
}
class WC_Order_Refund extends WC_Order{}
class WC_Order_Item_Product {
 public function get_product(){return null;}public function get_quantity(){return 1;}public function get_subtotal(){return 1100;}public function get_subtotal_tax(){return 0;}public function get_name(){return 'Fixture';}public function get_product_id(){return 1;}
}
class WP_Error{}
function current_user_can(...$a){return $GLOBALS['caps'];}function get_current_user_id(){return 123;}
function wp_verify_nonce(...$a){return $GLOBALS['nonce'];}function sanitize_text_field($s){return strip_tags($s);}function sanitize_textarea_field($s){return strip_tags($s);}function sanitize_key($s){return preg_replace('/[^a-z_]/','',$s);}function wp_unslash($s){return $s;}
function wp_timezone(){return new DateTimeZone('Asia/Kolkata');}function wp_date($f,$t){return (new DateTimeImmutable('@'.$t))->setTimezone(wp_timezone())->format($f);}
function get_option($k,$d=false){return $GLOBALS['opts'][$k]??$d;}function get_site_option($k,$d=false){return $GLOBALS['opts'][$k]??$d;}
function update_option($k,$v,...$a){$GLOBALS['opts'][$k]=$v;return true;}function add_option($k,$v,...$a){if(isset($GLOBALS['opts'][$k]))return false;$GLOBALS['opts'][$k]=$v;return true;}function delete_option($k){unset($GLOBALS['opts'][$k]);return true;}
function get_transient($k){return $k==='veloria_shiprocket_token'?'offline-token':($GLOBALS['trans'][$k]??false);}function set_transient($k,$v,$ttl){$GLOBALS['trans'][$k]=$v;return true;}function delete_transient($k){unset($GLOBALS['trans'][$k]);return true;}
function wp_json_encode($v){return json_encode($v);}function is_wp_error($v){return $v instanceof WP_Error;}
function wp_remote_request($url,$args){$GLOBALS['requests'][]=[$url,$args];return ($GLOBALS['transport'])($url,$args);}function wp_remote_post($u,$a){return wp_remote_request($u,$a);}
function wp_remote_retrieve_response_code($r){return $r['code'];}function wp_remote_retrieve_body($r){return json_encode($r['data']);}
function response($data,$code=200){return ['code'=>$code,'data'=>$data];}
function wc_get_order($id){return $GLOBALS['orders'][$id]??null;}function wc_get_logger(){return new class {function warning(...$a){}};}
function WC(){return (object)['countries'=>new class{function get_states($c){return ['DL'=>'Delhi'];}}];}
function apply_filters($k,$v,...$a){return $v;}function add_action(...$a){}
function is_email($value){return false!==filter_var($value,FILTER_VALIDATE_EMAIL);}
function as_has_scheduled_action($h,$a,$g){foreach($GLOBALS['scheduled'] as $x)if($x['args']===$a)return true;return false;}
function as_schedule_single_action($t,$h,$args,$g,$unique=true){$GLOBALS['scheduled'][]=['at'=>$t,'args'=>$args];return count($GLOBALS['scheduled']);}
require dirname(__DIR__).'/src/Settings.php';
require dirname(__DIR__).'/src/Orders/OrderService.php';
require dirname(__DIR__).'/src/Admin/OrderTools.php';
require dirname(__DIR__).'/src/Shipping/Shiprocket.php';
require dirname(__DIR__).'/src/Shipping/ShiprocketSync.php';
use Veloria\Commerce\Admin\OrderTools;
use Veloria\Commerce\Shipping\Shiprocket;
use Veloria\Commerce\Shipping\ShiprocketSync;
use Veloria\Commerce\Orders\OrderService;
use Veloria\Commerce\Settings;
$passed=0;
function check($name,$ok){global $passed;if(!$ok)throw new RuntimeException('FAIL: '.$name);++$passed;echo 'PASS: '.$name.PHP_EOL;}
function fixture($id){$o=new WC_Order($id);$GLOBALS['orders'][$id]=$o;return $o;}
Settings::save(['shiprocket_mode'=>'custom']);
$o=fixture(1);$valid=time()-3600;
check('delivery rejects future timestamp',!OrderTools::record_delivery($o,time()+10,'staff',123,'proof'));
check('delivery rejects before placement',!OrderTools::record_delivery($o,time()-10*86400,'staff',123,'proof'));
check('delivery rejects unknown evidence source',!OrderTools::record_delivery($o,$valid,'completed',123,'proof'));
check('strict date rejects calendar rollover',OrderTools::parse_delivery_date('2026-02-30T10:00')===null);
check('staff verified delivery persists evidence',OrderTools::record_delivery($o,$valid,'staff',123,'Carrier proof checked')&&OrderService::delivery_timestamp($o)===$valid&&$o->get_meta('_veloria_delivery_verified_by')===123);
check('carrier polling respects staff correction',OrderTools::record_delivery($o,$valid-10,'shiprocket')&&OrderService::delivery_timestamp($o)===$valid);
check('return allowed within7days verified delivery',OrderService::is_returnable($o)['ok']);
$o=fixture(2);check('completed is not delivery evidence',!OrderService::is_returnable(new WC_Order(8,'completed'))['ok']);
$_POST=['veloria_order_tools_nonce'=>'fixture','veloria_delivery_verified'=>'yes','veloria_delivered_at'=>wp_date('Y-m-d\TH:i',$valid),'veloria_delivery_reason'=>'Checked signed carrier receipt'];
$GLOBALS['caps']=false;OrderTools::save(2);check('staff save rejects missing capability',$o->saves===0);
$GLOBALS['caps']=true;$GLOBALS['nonce']=false;OrderTools::save(2);check('staff save rejects invalidnonce',$o->saves===0);
$GLOBALS['nonce']=true;OrderTools::save(2);check('staffsave accepts authorizednonce and proof',OrderService::delivery_timestamp($o)!==null);
$before=$o->saves;$_POST['veloria_delivered_at']='2099-01-01T10:00';OrderTools::save(2);check('staffsave rejects futuredate',$o->saves===$before);
check('carrier parser rejects RTO delivery',Shiprocket::delivery_from_tracking(['current_status'=>'RTO Delivered','delivered_date'=>'2026-10-20 10:00:00'])===null);
check('carrier parser rejects no actual deliverydate',Shiprocket::delivery_from_tracking(['current_status'=>'Delivered','edd'=>'2026-10-20 10:00:00'])===null);
$o=fixture(3);$o->update_meta_data('_shiprocket_shipment_id','333');$GLOBALS['transport']=fn()=>response(['tracking_data'=>['shipment_track'=>[['id'=>333,'current_status'=>'Delivered','delivered_date'=>wp_date('Y-m-d H:i:s',$valid)]]]]);
Shiprocket::track_shipment('333',$o,true);check('authenticated tracking explicitshipment/status/date ingests delivery',OrderService::delivery_timestamp($o)===$valid);
$o=fixture(4);$o->update_meta_data('_shiprocket_shipment_id','444');Shiprocket::track_shipment('444',$o,true);check('tracking rejects mismatched shipmentidentity',OrderService::delivery_timestamp($o)===null);
// Lost response: exactly one POST, every later attempt only searches.
$o=fixture(5);$GLOBALS['requests']=[];$GLOBALS['transport']=fn($u,$a)=>$a['method']==='GET'?response(['data'=>[]]):new WP_Error();
$r=Shiprocket::sync_order($o);$r2=Shiprocket::sync_order($o);
check('lostPOSTresponse preserves uncertainmarker',$o->get_meta('_veloria_sr_create_state')==='ambiguous'&&$r['state']==='retry');
check('emptylookup never repeats ambiguousPOST',count(array_filter($GLOBALS['requests'],fn($r)=>$r[1]['method']==='POST'))===1&&$r2['state']==='retry');
$GLOBALS['transport']=fn()=>response(['data'=>[['id'=>999,'channel_order_id'=>'5','base_channel_code'=>'CS','customer_email'=>'fixture@example.test','total'=>'1049','shipments'=>[['id'=>555]]]]]);
check('exactlookup reconciles uncertaincreation',Shiprocket::sync_order($o)['state']==='synced'&&$o->get_meta('_shiprocket_order_id')==='999'&&$o->get_meta('_shiprocket_shipment_id')==='555');
$o=fixture(6);$GLOBALS['transport']=fn()=>response(['data'=>[['id'=>999,'channel_order_id'=>'6','base_channel_code'=>'CS','customer_email'=>'other@example.test','total'=>'1049']]]);
check('remote identitycollision requires review',Shiprocket::sync_order($o)['state']==='review'&&!$o->get_meta('_shiprocket_order_id'));
// Bounded scheduling and concurrent event safety.
$o=fixture(7);$GLOBALS['transport']=fn()=>new WP_Error();$GLOBALS['scheduled']=[];ShiprocketSync::queue(7);ShiprocketSync::queue(7);
check('duplicateevents schedule onejob',count($GLOBALS['scheduled'])===1);
$GLOBALS['opts']['veloria_sr_lock_7']=time();ShiprocketSync::run(7);check('atomicworkerlock prevents duplicateexecution',(int)$o->get_meta('_veloria_sr_sync_attempts')===0);unset($GLOBALS['opts']['veloria_sr_lock_7']);
$o->delete_meta_data('_veloria_sr_sync_next_at');ShiprocketSync::run(7);check('firstfailure gets boundedbackoff',(int)$o->get_meta('_veloria_sr_sync_attempts')===1&&(int)$o->get_meta('_veloria_sr_sync_next_at')>=time()+59);
ShiprocketSync::run(7);check('duplicateearlyworker respectsbackoff',(int)$o->get_meta('_veloria_sr_sync_attempts')===1);
for($i=0;$i<8;$i++){$o->delete_meta_data('_veloria_sr_sync_next_at');ShiprocketSync::run(7);}
check('retrybudget exhausted to visibleoperatorreview',(int)$o->get_meta('_veloria_sr_sync_attempts')===5&&$o->get_meta('_veloria_sr_sync_state')==='review');
$o=fixture(9);$GLOBALS['opts']['active_plugins']=['shiprocket/class-shiprocket-woocommerce-shipping.php'];ShiprocketSync::queue(9);
check('officialsync blocks customduplicates',$o->get_meta('_veloria_sr_sync_state')==='review'&&(int)$o->get_meta('_veloria_sr_sync_attempts')===0);
// Official channel was registered on the api host: Shiprocket webhooks present that source.
function wp_parse_url($u,$c=-1){return parse_url($u,$c);}
$args=['headers'=>['X-WC-Webhook-Source'=>'https://www.veloriavault.com/','X-WC-Webhook-Topic'=>'order.created','X-WC-Webhook-Signature'=>'sig']];
$out=ShiprocketSync::webhook_source($args,'https://sr-go.shiprocket.in/wc/order/10749746');
check('shiprocket webhook source uses registered channel url',$out['headers']['X-WC-Webhook-Source']==='https://api.veloriavault.com/'&&$out['headers']['X-WC-Webhook-Signature']==='sig');
check('other webhook endpoints keep the real source',ShiprocketSync::webhook_source($args,'https://hooks.example.test/x')===$args);
$plain=['headers'=>['Authorization'=>'Bearer x']];
check('non-webhook shiprocket API calls are untouched',ShiprocketSync::webhook_source($plain,'https://apiv2.shiprocket.in/v1/external/orders')===$plain);
Settings::save(['shiprocket_webhook_source'=>'']);
check('cleared setting disables the source bridge',ShiprocketSync::webhook_source($args,'https://sr-go.shiprocket.in/wc/order/1')===$args);
// 204 alone is not a courier stop; the exact remote forward order must be final.
foreach (array(array('CANCELED',5,true),array('CANCELED BEFORE DISPATCHED',54,true),array('CANCELLATION REQUESTED',18,false),array('RETURN CANCELLATION REQUESTED',29,false),array('RETURN CANCELLED',27,false),array('CANCELED',18,false)) as [$status,$code,$expected]) {
 $GLOBALS['transport']=fn($url,$args)=>'POST'===$args['method']?response(null,204):response(['data'=>['id'=>123,'status'=>$status,'status_code'=>$code]]);
 check('courier terminal state '.$status.' / '.$code,Shiprocket::cancel_order(123)===$expected);
}
$GLOBALS['transport']=fn($url,$args)=>'POST'===$args['method']?response(null,204):response(['data'=>['id'=>999,'status'=>'CANCELED','status_code'=>5]]);
check('courier readback must identify the exact order',!Shiprocket::cancel_order(123));
echo json_encode(['passed'=>$passed,'network'=>'fully mocked','database'=>'none']).PHP_EOL;
