<?php

namespace SetranMedia\WpPluginner\View;

use SetranMedia\WpPluginner\Container\Container;

if ( ! defined( 'ABSPATH' ) ) exit;

class View
{

    /**
    * A plugin instance container.
    *
    * @var $container
    */
    protected $container;
    protected $key;
    protected $data;

    /**
    * List of styles and script to enqueue in admin area.
    *
    * @var array
    */
    protected $adminStyles  = [];
    protected $adminScripts = [];

    /**
    * List of styles and script to enqueue in frontend.
    *
    * @var array
    */
    protected $styles  = [];
    protected $scripts = [];

    /**
    * Create a new View.
    *
    * @param mixed $container Usually a container/plugin.
    * @param null  $key       Optional. This is the path of view.
    * @param null  $data      Optional. Any data to pass to view.
    */
    public function __construct( $container, $key = null, $data = null )
    {
        $this->container = $container;
        $this->key       = $key;
        $this->data      = $data;
        $adminStyles = $this->container->config('enqueue.admin_enqueue_styles',[]);
        if($adminStyles && is_array($adminStyles) && !empty($adminStyles)){
            foreach($adminStyles as $style){

            }
        }
    }

    /**
    * Get the filename.
    *
    * @return string
    */
    protected function filename()
    {
        $filename = str_replace( '.', '/', $this->key ) . '.php';

        return $filename;
    }

    protected function admin_print_styles()
    {
        if ( ! empty( $this->adminStyles ) ) {
            foreach ( $this->adminStyles as $style ) {
                $deps = isset($style[2]) ? $style[2] : array();
                $ver = isset($style[3]) ? $style[3] : false;
                $media = isset($style[4]) ? $style[4] : 'all';
                wp_enqueue_style( $style[0], $style[1], $deps, $ver, $media );
            }
        }
    }

    protected function admin_enqueue_scripts()
    {
        if ( ! empty( $this->adminScripts ) ) {
            foreach ( $this->adminScripts as $script ) {
                $deps = isset($script[2]) ? $script[2] : array();
                $ver = isset($script[3]) ? $script[3] : false;
                $in_footer = isset($script[4]) ? $script[4] : false;
                wp_enqueue_script( $script[ 0 ], $script[1], $deps, $ver, $in_footer );
            }
        }
    }

    protected function wp_print_styles()
    {
        if ( ! empty( $this->styles ) ) {
            foreach ( $this->styles as $style ) {
                $deps = isset($style[2]) ? $style[2] : array();
                $ver = isset($style[3]) ? $style[3] : false;
                $media = isset($style[4]) ? $style[4] : 'all';
                wp_enqueue_style( $style[0], $style[1], $deps, $ver, $media );
            }
        }
    }

    protected function wp_enqueue_scripts()
    {
        if ( ! empty( $this->scripts ) ) {
            foreach ( $this->scripts as $script ) {
                $deps = isset($script[2]) ? $script[2] : array();
                $ver = isset($script[3]) ? $script[3] : false;
                $in_footer = isset($script[4]) ? $script[4] : false;
                wp_enqueue_script( $script[ 0 ], $script[1], $deps, $ver, $in_footer );
            }
        }
    }

    /**
    * Get the string rappresentation of a view.
    *
    * @return string
    */
    public function __toString()
    {
        return (string) $this->render();
    }

    public function render()
    {

        if ( ! $this->container->isAjax() ) {
            $this->admin_enqueue_scripts();
            $this->admin_print_styles();
            $this->wp_enqueue_scripts();
            $this->wp_print_styles();
        }


        $func = function () {

            // make available plugin instance
            $plugin = $this->container;
            $blade = new Blade($plugin->getBasePath() . '/resources/views', $plugin->getBasePath() . '/storage/framework/views');
            if ( ! is_null( $this->data ) && is_array( $this->data ) ) {
                echo $blade->make($this->key, $this->data)->with('plugin',$plugin);
            }
            echo $blade->view()->make($this->key)->with('plugin',$plugin);
        };


        if ( $this->container->isAjax() ) {
            ob_start();
            $func();
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }

        return $func();
    }

    /**
    * Load a new css resource in admin area.
    *
    * @param string $name Name of style.
    * @param array  $deps Optional. Array of slug deps
    * @param array  $ver  Optional. Version.
    *
    * @return $this
    */
    public function withAdminStyles( $handle, $src = '', $deps = [], $ver = false, $media = 'all' )
    {
        $this->adminStyles[] = [ $handle, $src, $deps, $ver, $media ];

        return $this;
    }

    /**
    * Load a new css resource in admin area.
    *
    * @param string $name Name of script.
    * @param array  $deps Optional. Array of slug deps
    * @param array  $ver  Optional. Version.
    *
    * @return $this
    */
    public function withAdminScripts( $handle, $src = '', $deps = [], $ver = false, $in_footer = false )
    {
        $this->adminScripts[] = [ $handle, $src, $deps, $ver, $in_footer ];

        return $this;
    }

    /**
    * Load a new css resource in frontend.
    *
    * @param string $name Name of style.
    * @param array  $deps Optional. Array of slug deps
    * @param array  $ver  Optional. Version.
    *
    * @return $this
    */
    public function withStyles( $name, $deps = [], $ver = [] )
    {
        $this->styles[] = [ $name, $deps, $ver ];

        return $this;
    }

    /**
    * Load a new css resource in fonrend.
    *
    * @param string $name Name of script.
    * @param array  $deps Optional. Array of slug deps
    * @param array  $ver  Optional. Version.
    *
    * @return $this
    */
    public function withScripts( $name, $deps = [], $ver = [] )
    {
        $this->scripts[] = [ $name, $deps, $ver ];

        return $this;
    }

    /**
    * Data to pass to the view.
    *
    * @param mixed $data Array or single data.
    *
    * @example     $instance->with( 'foo', 'bar' )
    * @example     $instance->with( [ 'foo' => 'bar' ] )
    *
    * @return $this
    */
    public function with( $data )
    {
        if ( is_array( $data ) ) {
            $this->data[] = $data;
        }
        elseif ( func_num_args() > 1 ) {
            $this->data[] = [ $data => func_get_arg( 1 ) ];
        }

        return $this;
    }

}
