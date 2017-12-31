<?php

namespace SetranMedia\WpPluginner\Foundation;

use SetranMedia\WpPluginner\Container\Container;
use SetranMedia\WpPluginner\Database\WordPressOption;
use SetranMedia\WpPluginner\View\View;
use SetranMedia\WpPluginner\Contracts\Foundation\Plugin as PluginContract;
use SetranMedia\WpPluginner\Foundation\Http\Request;
use SetranMedia\WpPluginner\Foundation\Config;
use SetranMedia\WpPluginner\Support\Str;

if ( ! defined( 'ABSPATH' ) ) exit;

class Plugin extends Container implements PluginContract
{

    protected static $instance;
    protected $file;
    protected $basePath;
    protected $baseUri;
    protected $configer;
    protected $viewer;

    protected $pluginData = [];
    protected $provides = [];
    private $_options = null;
    private $_request = null;
    public $slug = "";

    public function __construct( $basePath ){
        $this->basePath = rtrim( $basePath, '\/' );
        $this->bootPlugin();
        $this->bootWPIntegration();
    }

    public function __get( $name ){
        $method = 'get' . Str::studly( $name ) . 'Attribute';
        if ( method_exists( $this, $method ) ) {
            return $this->{$method}();
        }

        foreach ( $this->pluginData as $key => $value ) {
            if ( $name == $key ) {
                return $value;
            }
        }
    }

    public function bootPlugin()
    {
        $this->file = $this->basePath . '/plugin.php';

        $this->baseUri = rtrim( plugin_dir_url( $this->file ), '\/' );
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $this->pluginData = get_plugin_data( $this->file, false );

        $this->slug = str_replace( "-", "_", sanitize_title( $this->Name ) );

        static::$instance = $this;

        $this->configer = new Config();
        $this->configer->loadConfigurationFiles($this->configPath);

        $this->viewer = new View($this);
        return $this;

    }

    public function bootWPIntegration(){
        load_plugin_textdomain( $this->TextDomain, false, trailingslashit( basename( $this->basePath ) ) . $this->DomainPath );

        register_activation_hook( $this->file, [ $this, 'activation' ] );
        register_deactivation_hook( $this->file, [ $this, 'deactivation' ] );

        add_action( 'init', array( $this, 'init' ) );

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    public function getOptionsAttribute(){
        if ( is_null( $this->_options ) ) {
            $this->_options = new WordPressOption( $this );
        }

        return $this->_options;
    }

    public function getRequestAttribute(){
        if ( is_null( $this->_request ) ) {
            $this->_request = new Request();
        }

        return $this->_request;
    }

    public function getPluginBasenameAttribute(){
        return plugin_basename( $this->file );
    }

    public function getBasePath(){
        return $this->basePath;
    }

    public function getBasePathAttribute(){
        return $this->basePath;
    }

    public function getAppPathAttribute(){
        return $this->basePath.'/'.$this->config('path.app');
    }

    public function getConfigPathAttribute(){
        return $this->basePath.'/config';
    }

    public function getDatabasePathAttribute(){
        return $this->basePath.'/'.$this->config('path.database');
    }

    public function getLocalizationPathAttribute(){
        return $this->basePath.'/'.$this->config('path.localization');
    }

    public function getPublicPathAttribute(){
        return $this->basePath.'/'.$this->config('path.public');
    }

    public function getResourcePathAttribute(){
        return $this->basePath.'/'.$this->config('path.resources');
    }

    public function getStoragePathAttribute(){
        return $this->basePath.'/'.$this->config('path.storage');
    }

    public function getWpPropertiesPathAttribute(){
        return $this->basePath.'/'.$this->config('path.wp_properties');
    }

    public function getBaseUriAttribute(){
        return $this->baseUri;
    }

    public function getPublicUriAttribute(){
        return $this->baseUri.'/'.$this->config('path.public');;
    }

    public function getCssAttribute(){
        return $this->baseUri . '/public/css';
    }

    public function getJsAttribute(){
        return $this->baseUri . '/public/js';
    }

    public function getImagesAttribute(){
        return $this->baseUri . '/public/images';
    }

    public function vendor( $vendor = "setranmedia" ){
        return $this->baseUri . "/vendor/$vendor";
    }

    public function config($key=null,$default=null){
        if($key) return $this->configer->get($key,$default);
        return $this->configer;
    }

    public function env( $key, $default = null ){
        return wpbones_env( $key, $default );
    }

    public function view( $key = null, $data = null ){

        if($key) $this->viewer->setKey($key);
        if($data) $this->viewer->setData($data);

        return $this->viewer;

    }

    public function isAjax(){
        if ( defined( 'DOING_AJAX' ) ) {
            return true;
        }
        if ( isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest') {
            return true;
        }

        return false;
    }

    public function getPageUrl( $pageSlug ){
        return add_query_arg( array( 'page' => $pageSlug ), admin_url( 'admin.php' ) );
    }

    public function provider( $name ){

        foreach ( $this->provides as $key => $value ) {

            if ( $key == $name ) {
                return $value;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | WordPress actions & filter
    |--------------------------------------------------------------------------
    |
    | When a plugin starts we will use some useful actions and filters.
    |
    */

    public function init(){
        $init = $this->config()->get('plugin',false);

        if ( is_array( $init ) ) {

            // Here we are going to init Service Providers


            // Shortcodes Service Provider
            if ( isset( $init[ 'shortcodes' ] ) && ! empty( $init[ 'shortcodes' ] ) ) {
                foreach ( $init[ 'shortcodes' ] as $className ) {
                    $object = new $className;
                    $object->register();
                    $this->provides[ $className ] = $object;
                }
            }

            // Ajax Service Provider
            if ( $this->isAjax() ) {
                if ( isset( $init[ 'ajax' ] ) && ! empty( $init[ 'ajax' ] ) ) {
                    foreach ( $init[ 'ajax' ] as $className ) {
                        $object = new $className;
                        $object->register();
                        $this->provides[ $className ] = $object;
                    }
                }
            }

            // Custom service provider
            if ( isset( $init[ 'providers' ] ) && ! empty( $init[ 'providers' ] ) ) {
                foreach ( $init[ 'providers' ] as $className ) {
                    $object = new $className;
                    $object->register();
                    $this->provides[ $className ] = $object;
                }
            }
        }
    }

    /**
    * Fires before the administration menu loads in the admin.
    */
    public function admin_menu(){
        global $admin_page_hooks, $_registered_pages, $_parent_pages;

        $menus = $this->config()->get('menus',false);

        if ( ! empty( $menus ) && is_array( $menus ) ) {

            foreach ( $menus as $topLevelSlug => $menu ) {

                // sanitize array keys
                $menu[ 'position' ]   = isset( $menu[ 'position' ] ) ? $menu[ 'position' ] : null;
                $menu[ 'capability' ] = isset( $menu[ 'capability' ] ) ? $menu[ 'capability' ] : 'read';
                $menu[ 'icon' ]       = isset( $menu[ 'icon' ] ) ? $menu[ 'icon' ] : '';
                $menu[ 'page_title' ] = isset( $menu[ 'page_title' ] ) ? $menu[ 'page_title' ] : $menu[ 'menu_title' ];

                // icon
                $icon = $menu[ 'icon' ];
                if ( isset( $menu[ 'icon' ] ) && ! empty( $menu[ 'icon' ] ) && 'dashicons' != substr( $menu[ 'icon' ], 0, 9 ) ) {
                    $icon = $this->getImagesAttribute() . '/' . $menu[ 'icon' ];
                }

                $firstMenu = true;

                if ( substr( $topLevelSlug, 0, 8 ) !== 'edit.php' ) {
                    add_menu_page( $menu[ 'page_title' ], $menu[ 'menu_title' ], $menu[ 'capability' ], $topLevelSlug, '', $icon, $menu[ 'position' ] );
                }
                else {
                    $firstMenu = false;
                }

                foreach ( $menu[ 'items' ] as $key => $subMenu ) {

                    if ( is_null( $subMenu ) ) {
                        continue;
                    }

                    // index 0
                    if ( empty( $key ) ) {
                        $key = '0';
                    }

                    // sanitize array keys
                    $subMenu[ 'capability' ] = isset( $subMenu[ 'capability' ] ) ? $subMenu[ 'capability' ] : $menu[ 'capability' ];
                    $subMenu[ 'page_title' ] = isset( $subMenu[ 'page_title' ] ) ? $subMenu[ 'page_title' ] : $subMenu[ 'menu_title' ];

                    // key could be a number
                    $key = str_replace( '-', "_", sanitize_title( $key ) );

                    // submenu slug
                    $submenuSlug = "{$topLevelSlug}_{$key}";

                    if ( $firstMenu ) {
                        $firstMenu   = false;
                        $submenuSlug = $topLevelSlug;
                    }

                    // get hook
                    $hook = $this->getCallableHook( $subMenu[ 'route' ] );

                    $subMenuHook = add_submenu_page( $topLevelSlug, $subMenu[ 'page_title' ], $subMenu[ 'menu_title' ], $subMenu[ 'capability' ], $submenuSlug, $hook );

                    if ( isset( $subMenu[ 'route' ][ 'load' ] ) ) {
                        list( $controller, $method ) = explode( '@', $subMenu[ 'route' ][ 'load' ] );

                        $func = create_function( '', sprintf( '$instance = new %s; return $instance->%s();', $controller, $method ) );
                        add_action( "load-{$subMenuHook}", $func );
                    }

                    if ( isset( $subMenu[ 'route' ][ 'resource' ] ) ) {
                        $controller = $subMenu[ 'route' ][ 'resource' ];

                        $func = create_function( '', sprintf( '$instance = new %s; if( method_exists( $instance, "load" ) ) { return $instance->load(); }', $controller ) );
                        add_action( "load-{$subMenuHook}", $func );
                    }
                }
            }
        }

        // custom hidden pages
        $pages = $this->config()->get('routes',false);

        if ( ! empty( $pages ) && is_array( $pages ) ) {
            foreach ( $pages as $pageSlug => $page ) {

                $pageSlug                      = plugin_basename( $pageSlug );
                $admin_page_hooks[ $pageSlug ] = ! isset( $page[ 'title' ] ) ? : $page[ 'title' ];
                $hookName                      = get_plugin_page_hookname( $pageSlug, '' );

                if ( ! empty( $hookName ) ) {

                    add_action( $hookName, $this->getCallableHook( $page[ 'route' ] ) );

                    $_registered_pages[ $hookName ] = true;
                    $_parent_pages[ $pageSlug ]     = false;
                }
            }
        }
    }

    /**
    * Called when a plugin is activate; `register_activation_hook()`
    *
    */
    public function activation(){
        $this->options->delta();

        // include your own activation
        $activation = include_once $this->wpPropertiesPath . '/hooks/activation.php';

        // migrations
        foreach ( glob( $this->databasePath . '/migrations/*.php' ) as $filename ) {
            include $filename;
            foreach ( $this->getFileClasses( $filename ) as $className ) {
                $instance = new $className;
            }
        }

        // seeders
        foreach ( glob( $this->databasePath . '/seeds/*.php' ) as $filename ) {
            include $filename;
            foreach ( $this->getFileClasses( $filename ) as $className ) {
                $instance = new $className;
            }
        }
    }

    /**
    * Called when a plugin is deactivate; `register_deactivation_hook()`
    *
    */
    public function deactivation(){
        $deactivation = include_once $this->wpPropertiesPath . '/hooks/deactivation.php';
    }

    // -- private

    private function getCallableHook( $routes )
    {
        // get the http request verb
        $verb = $this->request->method;

        if ( isset( $routes[ 'resource' ] ) ) {
            $methods = [
                'get'    => 'index',
                'post'   => 'store',
                'put'    => 'update',
                'patch'  => 'update',
                'delete' => 'destroy',
            ];

            $controller = $routes[ 'resource' ];
            $method     = $methods[ $verb ];
        }
        // by single verb and controller@method
        else {

            if ( isset( $routes[ $verb ] ) ) {
                list( $controller, $method ) = explode( '@', $routes[ $verb ] );
            }
            // default "get"
            else {
                list( $controller, $method ) = explode( '@', $routes[ 'get' ] );
            }
        }

        $hook = create_function( '', sprintf( '$instance = new %s; return( $instance->render( "%s" ) );',
        $controller, $method ) );

        return $hook;
    }

    /**
    * Return the list of classes in a PHP file.
    *
    * @param string $filename A PHP Filename file.
    *
    * @return array|bool
    */
    private function getFileClasses( $filename )
    {
        $code = file_get_contents( $filename );

        if ( empty( $code ) ) {
            return false;
        }

        $classes = array();
        $tokens  = token_get_all( $code );
        $count   = count( $tokens );
        for ( $i = 2; $i < $count; $i++ ) {
            if ( $tokens[ $i - 2 ][ 0 ] == T_CLASS && $tokens[ $i - 1 ][ 0 ] == T_WHITESPACE && $tokens[ $i ][ 0 ] == T_STRING ) {
                $class_name = $tokens[ $i ][ 1 ];
                $classes[]  = $class_name;
            }
        }
        return $classes;
    }

}
