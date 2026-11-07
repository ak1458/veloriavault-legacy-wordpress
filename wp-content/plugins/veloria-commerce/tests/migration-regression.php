<?php
/** Isolated regression checks. No WordPress database, network or mail is used. */
declare(strict_types=1);

define('ABSPATH', '/tmp/veloria-migration-regression/');
define('VELORIA_COMMERCE_VERSION', '1.0.0');
define('VELORIA_COMMERCE_DIR', dirname(__DIR__) . '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('ARRAY_A', 'ARRAY_A');
define('WC_VERSION', '11.2.1');
class CliFailure extends RuntimeException {}
class WP_CLI {
    public static array $messages = [];
    public static function warning($message): void { self::$messages[] = $message; }
    public static function log($message): void { self::$messages[] = $message; }
    public static function success($message): void { self::$messages[] = $message; }
    public static function error($message, $exit = true): void { if ($exit) { throw new CliFailure($message); } }
}
class WooCommerce {}
class WP_REST_Request {
    public array $params = [];
    public function __construct(public string $method, public string $route) {}
    public function set_query_params(array $params): void { $this->params = $params; }
}
class FakeResponse {
    public function __construct(public $data, private bool $error = false, private array $headers = []) {}
    public function is_error(): bool { return $this->error; }
    public function get_headers(): array { return $this->headers; }
}
class FakeServer { public function response_to_data($response, $embed) { return $response->data; } }
class FakeDb {
    public string $prefix = 'test_';
    public bool $exists = false;
    public bool $fail = false;
    public function prepare($query, $value) { return str_replace('%s', "'" . $value . "'", $query); }
    public function esc_like($value) { return $value; }
    public function get_var($query) { return $this->exists ? $this->prefix . 'veloria_subscribers' : null; }
    public function get_charset_collate() { return ''; }
    public function get_results($query, $output) {
        if (str_starts_with($query, 'SHOW INDEX')) { return [['Key_name'=>'PRIMARY','Column_name'=>'id','Non_unique'=>0,'Seq_in_index'=>1],['Key_name'=>'email','Column_name'=>'email','Non_unique'=>0,'Seq_in_index'=>1]]; }
        $types = ['id'=>'bigint(20) unsigned','email'=>'varchar(190)','source'=>'varchar(40)','ip_hash'=>'char(64)','created_at'=>'datetime'];
        $out = [];
        foreach ($types as $name=>$type) { $out[]=['Field'=>$name,'Type'=>$type,'Null'=>'NO','Extra'=>$name==='id'?'auto_increment':'']; }
        return $out;
    }
}
$GLOBALS['wpdb'] = new FakeDb();
$GLOBALS['options'] = [];
$GLOBALS['pages'] = [];
$GLOBALS['meta'] = [];
$GLOBALS['scheduled'] = false;
$GLOBALS['api_pages'] = [];
$GLOBALS['writes'] = 0;
function get_option($key, $fallback = false) { return $GLOBALS['options'][$key] ?? $fallback; }
function update_option($key, $value, $autoload = null) { $GLOBALS['options'][$key] = $value; $GLOBALS['writes']++; return true; }
function add_option($key, $value, $deprecated = '', $autoload = null) { if (array_key_exists($key, $GLOBALS['options'])) return false; return update_option($key,$value); }
function delete_option($key) { unset($GLOBALS['options'][$key]); return true; }
function is_wp_error($value) { return false; }
function wp_next_scheduled($hook) { return $GLOBALS['scheduled']; }
function wp_schedule_event($time,$period,$hook,$args=[],$wp_error=false) { $GLOBALS['scheduled']=$time; return true; }
function wp_clear_scheduled_hook($hook) { $GLOBALS['scheduled']=false; }
function flush_rewrite_rules() {}
function wp_json_encode($value) { return json_encode($value); }
function get_transient($key) { return false; }
function set_transient($key,$value,$ttl) { return true; }
function rest_do_request($request) { return $GLOBALS['api_pages'][(int)($request->params['page']??1)] ?? new FakeResponse([],true); }
function rest_get_server() { return new FakeServer(); }
function apply_filters($name,$value,...$args) { return $value; }
function get_page_by_path($slug) { foreach ($GLOBALS['pages'] as $page) if ($page->post_name===$slug) return clone $page; return null; }
function get_post($id) { return isset($GLOBALS['pages'][$id]) ? clone $GLOBALS['pages'][$id] : null; }
function get_page_template_slug($page=null) { return $GLOBALS['meta'][$page->ID]['_wp_page_template']??''; }
function get_post_field($field,$id) { return $GLOBALS['pages'][$id]->$field??''; }
function metadata_exists($type,$id,$key) { return array_key_exists($key,$GLOBALS['meta'][$id]??[]); }
function add_post_meta($id,$key,$value,$unique=false) { if($unique && metadata_exists('post',$id,$key))return false; $GLOBALS['meta'][$id][$key]=$value; $GLOBALS['writes']++; return 1; }
function update_post_meta($id,$key,$value) { $GLOBALS['meta'][$id][$key]=$value; $GLOBALS['writes']++; return 1; }
function wp_insert_post($fields,$error=false) { $id=count($GLOBALS['pages'])+1;$fields+=['post_content'=>''];$fields['ID']=$id;$GLOBALS['pages'][$id]=(object)$fields;$GLOBALS['writes']++;return $id; }
function wp_update_post($fields,$error=false) { foreach($fields as $key=>$value)$GLOBALS['pages'][$fields['ID']]->$key=$value;$GLOBALS['writes']++;return $fields['ID']; }
function wp_slash($value) { return $value; }
function wp_strip_all_tags($value) { return strip_tags($value); }
function get_template() { return $GLOBALS['template'] ?? 'veloria'; }
function wp_get_environment_type() { return $GLOBALS['environment'] ?? 'production'; }
function wc_get_orders($args) { return []; }
function get_posts($args) { return []; }
function wc_get_shipping_method_count($enabled) { return 1; }
function is_multisite() { return false; }
function get_site_option($key, $fallback = false) { return $fallback; }
function wp_timezone() { return new DateTimeZone('Asia/Kolkata'); }
function add_rewrite_rule($pattern,$query,$position) { $GLOBALS['added_rules'][]=$pattern; }
function get_query_var($key) { return $key==='veloria_sitemap' ? 1 : null; }
function status_header($status) { $GLOBALS['status']=$status; }
function nocache_headers() {}
function doing_action($hook) { return $hook==='template_redirect'; }
function home_url($path='') { return 'http://localhost:8080'.$path; }

require VELORIA_COMMERCE_DIR . 'src/Settings.php';
require VELORIA_COMMERCE_DIR . 'src/Install.php';
require VELORIA_COMMERCE_DIR . 'src/Plugin.php';
require VELORIA_COMMERCE_DIR . 'src/Admin/Notices.php';
require VELORIA_COMMERCE_DIR . 'src/Shipping/ShiprocketSync.php';
require VELORIA_COMMERCE_DIR . 'src/Admin/HealthChecks.php';
require VELORIA_COMMERCE_DIR . 'src/Catalog/Catalog.php';
require VELORIA_COMMERCE_DIR . 'src/Pricing/DiscountEngine.php';
require VELORIA_COMMERCE_DIR . 'src/Cli/Commands.php';
require VELORIA_COMMERCE_DIR . 'src/Seo/Routing.php';
use Veloria\Commerce\Install;
use Veloria\Commerce\Settings;
use Veloria\Commerce\Catalog\Catalog;
use Veloria\Commerce\Cli\Commands;
use Veloria\Commerce\Seo\Routing;

$passed=0;
function expect(bool $ok,string $label):void { global $passed; if(!$ok)throw new RuntimeException($label);$passed++;echo "PASS $label\n"; }
function failure(callable $call,string $needle):void { try{$call();}catch(CliFailure $error){expect(str_contains($error->getMessage(),$needle),$needle);return;}throw new RuntimeException('Expected CLI failure: '.$needle); }
$cli=new Commands();
@mkdir(ABSPATH.'wp-admin/includes',0777,true);
file_put_contents(ABSPATH.'wp-admin/includes/upgrade.php','<?php function dbDelta($sql) { if (!$GLOBALS["wpdb"]->fail) $GLOBALS["wpdb"]->exists=true; }');

// A failed CREATE cannot advance the schema version; a retry can recover it.
$GLOBALS['wpdb']->fail=true;
expect(!Install::maybe_upgrade(),'schema failure is reported');
expect(!get_option('veloria_commerce_db_version'),'schema failure does not record success');
$GLOBALS['wpdb']->fail=false;
expect(Install::maybe_upgrade(),'schema retry succeeds');
expect(get_option('veloria_commerce_db_version')==='1.0.0','verified schema version recorded');
Install::deactivate();
expect(!$GLOBALS['scheduled'],'deactivation clears schedule');
expect(Install::maybe_upgrade() && (bool)$GLOBALS['scheduled'],'current-version reactivation restores schedule');
$GLOBALS['options']['veloria_commerce_db_version']='2.0.0';
expect(!Install::maybe_upgrade() && get_option('veloria_commerce_db_version')==='2.0.0','newer database version is not downgraded');
$GLOBALS['options']['veloria_commerce_db_version']='1.0.0';Install::maybe_upgrade();

// More than 100 objects survive; a failure/repeated page never becomes a partial list.
$GLOBALS['api_pages']=[1=>new FakeResponse(array_map(fn($id)=>['id'=>$id],range(1,100))),2=>new FakeResponse(array_map(fn($id)=>['id'=>$id],range(101,200))),3=>new FakeResponse([['id'=>201]])];
$catalog=new Catalog();
expect(count($catalog->store_all('/products'))===201,'201 catalog objects paginate completely');
$GLOBALS['api_pages']=[1=>new FakeResponse(array_map(fn($id)=>['id'=>$id],range(1,100)),false,['X-WP-TotalPages'=>1])];
expect(count((new Catalog())->store_all('/products'))===100,'exact 100-object page honors authoritative last page');
$GLOBALS['api_pages']=[1=>new FakeResponse(array_map(fn($id)=>['id'=>$id],range(1,100))),2=>new FakeResponse([],true)];
$GLOBALS['api_pages'][2]=new FakeResponse([],true);
try{(new Catalog())->store_all('/products');throw new Exception('expected failure');}catch(RuntimeException $e){expect(str_contains($e->getMessage(),'completely'),'failed second page does not return first page');}
$GLOBALS['api_pages'][2]=$GLOBALS['api_pages'][1];
try{(new Catalog())->store_all('/products');throw new Exception('expected failure');}catch(RuntimeException $e){expect(str_contains($e->getMessage(),'repeated'),'repeated pagination fails closed');}

// Input validation and all-field comparison, not just finalTotal.
$empty=ABSPATH.'empty.json';file_put_contents($empty,'[]');
failure(fn()=>$cli->parity([],['vectors'=>$empty]),'nonempty');
$invalid=ABSPATH.'invalid.json';file_put_contents($invalid,'{');
failure(fn()=>$cli->parity([],['vectors'=>$invalid]),'Invalid vectors JSON');
$vectors=json_decode(file_get_contents(__DIR__.'/parity/vectors.json'),true);
$one=ABSPATH.'one.json';file_put_contents($one,json_encode([$vectors[0]]));
$cli->parity([],['vectors'=>$one,'expected-count'=>'1']);expect(true,'single full-field parity vector passes');
failure(fn()=>$cli->parity([],['vectors'=>$one,'expected-count'=>'2']),'count');
$vectors[0]['output']['tierDiscount']++;
file_put_contents($one,json_encode([$vectors[0]]));
failure(fn()=>$cli->parity([],['vectors'=>$one]),'mismatches');
unset($vectors);
$cli->parity([],['vectors'=>__DIR__.'/parity/vectors.json','expected-count'=>'10000']);expect(true,'10000 original full-field parity vectors pass');

// Dry run does not mutate; apply publishes policies; rerun preserves the first originals.
$before=$GLOBALS['writes'];$cli->seed_pages([],[]);expect($GLOBALS['writes']===$before,'seed dry run performs no writes');
// Production with the headless frontend still live (theme inactive): --apply is refused before any write.
$GLOBALS['template']='headless-backend';$before=$GLOBALS['writes'];failure(fn()=>$cli->seed_pages([],['apply'=>true]),'Refusing --apply');expect($GLOBALS['writes']===$before,'seed apply refused on live headless production');
$GLOBALS['environment']='staging';$before=$GLOBALS['writes'];failure(fn()=>$cli->seed_pages([],['apply'=>true]),'Refusing --apply');expect($GLOBALS['writes']===$before,'environment type staging does not bypass the guard');unset($GLOBALS['template'],$GLOBALS['environment']);
$cli->seed_pages([],['apply'=>true]);
$privacy=get_page_by_path('privacy-policy');
expect($privacy && $privacy->post_status==='publish' && trim($privacy->post_content)===trim(file_get_contents(VELORIA_COMMERCE_DIR.'content/policies/privacy-policy.html')),'seed applies approved published policy');
$GLOBALS['pages'][$privacy->ID]->post_content='old approved policy';
$cli->seed_pages([],['apply'=>true]);
expect($GLOBALS['meta'][$privacy->ID]['_veloria_migration_original']['post_content']==='old approved policy','existing policy original backed up');
$GLOBALS['pages'][$privacy->ID]->post_content='later edit';$cli->seed_pages([],['apply'=>true]);
expect($GLOBALS['meta'][$privacy->ID]['_veloria_migration_original']['post_content']==='old approved policy','rerun does not overwrite rollback original');
$before=$GLOBALS['writes'];failure(fn()=>$cli->seed_pages([],['apply'=>true,'content-dir'=>ABSPATH.'missing']),'Missing or empty');expect($GLOBALS['writes']===$before,'missing policy fails before writes');

// Draft pages, wrong mappings and differing policies make preflight fail.
$GLOBALS['options']+=['active_plugins'=>['woo-razorpay/woo-razorpay.php'],'woocommerce_default_country'=>'IN:DL','woocommerce_currency'=>'INR'];
Settings::save(['shiprocket_mode'=>'custom']);
$GLOBALS['options']['veloria_sales_last_run']=time();
$GLOBALS['scheduled']=time()+3600;
$cli->check();expect(true,'valid seeded configuration passes local preflight');
// Shiprocket: exactly one sync path must run (official plugin OR Veloria sync).
$GLOBALS['options']['active_plugins'][]='shiprocket/class-shiprocket-woocommerce-shipping.php';
Settings::save(['shiprocket_mode'=>'off']);$cli->check();expect(true,'official Shiprocket plugin alone passes preflight');
Settings::save(['shiprocket_mode'=>'custom']);failure(fn()=>$cli->check(),'problem(s)');
array_pop($GLOBALS['options']['active_plugins']);
Settings::save(['shiprocket_mode'=>'off']);failure(fn()=>$cli->check(),'problem(s)');
Settings::save(['shiprocket_mode'=>'custom']);
$GLOBALS['pages'][$privacy->ID]->post_status='draft';failure(fn()=>$cli->check(),'problem(s)');$GLOBALS['pages'][$privacy->ID]->post_status='publish';
$GLOBALS['options']['woocommerce_cart_page_id']=999;failure(fn()=>$cli->check(),'problem(s)');

// A sitemap-owner toggle invalidates cached rewrites and native robots uses the native map.
$GLOBALS['options']['rewrite_rules']=['stale'];Routing::settings_changed(['serve_sitemap'=>true],['serve_sitemap'=>false]);
expect(!get_option('rewrite_rules'),'sitemap toggle invalidates rewrite cache');
Settings::save(['serve_sitemap'=>false]);$GLOBALS['added_rules']=[];Routing::add_rewrite_rules();
expect(!$GLOBALS['added_rules'],'disabled sitemap does not register its rewrite');
Routing::serve_sitemap();expect($GLOBALS['status']===404,'stale disabled sitemap route cannot publish XML');
expect(Routing::robots("Sitemap: http://localhost:8080/wp-sitemap.xml\n",true)==="Sitemap: http://localhost:8080/wp-sitemap.xml\n",'disabled custom sitemap preserves the native SEO owner robots output');
echo "RESULT {$passed} checks passed. No WordPress database, network or mail used.\n";
