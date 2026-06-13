<?php

namespace ElementorPro\Modules\EditorOne;

use Elementor\Core\Admin\EditorOneMenu\Menu\Editor_One_Custom_Elements_Menu;
use Elementor\Core\Base\Module as BaseModule;
use Elementor\Modules\EditorOne\Classes\Menu_Config;
use Elementor\Modules\EditorOne\Classes\Menu_Data_Provider;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Module extends BaseModule {

	public function get_name(): string {
		return 'editor-one';
	}

	public static function is_active(): bool {
		return (bool) Plugin::instance()->modules_manager->get_modules( 'editor-one' );
	}

	public function __construct() {
		parent::__construct();

		add_filter( 'elementor/modules/editor-one/is_pro_module_enabled', '__return_true' );
		add_filter( 'elementor/editor-one/menu/legacy_pro_mapping', [ $this, 'add_legacy_pro_mapping' ] );

		add_action( 'elementor/editor-one/menu/register', function ( Menu_Data_Provider $menu_data_provider ) {
			$menu_data_provider->register_menu( new Editor_One_Custom_Elements_Menu() );
		} );
	}

	public function add_legacy_pro_mapping( array $mapping ): array {
		$mapping[ Menu_Config::ELEMENTOR_MENU_SLUG ] = [ 'group' => Menu_Config::EDITOR_GROUP_ID ];

		return $mapping;
	}
}
