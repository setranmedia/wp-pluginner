<?php

namespace SetranMedia\WpPluginner\View;

use SetranMedia\WpPluginner\Container\Container;

if ( ! defined( 'ABSPATH' ) ) exit;

class View
{
    protected $container;
    protected $key;
    protected $data;

    protected $adminStyles  = [];
    protected $adminScripts = [];
    protected $adminLocalizes = [];

    protected $styles  = [];
    protected $scripts = [];

    public function __construct( $container )
    {
        $this->container = $container;
    }

    protected function filename(){
        $filename = str_replace( '.', '/', $this->key ) . '.php';

        return $filename;
    }

    protected function admin_print_styles(){
        if ( ! empty( $this->adminStyles ) ) {
            foreach ( $this->adminStyles as $style ) {
                $deps = isset($style[2]) ? $style[2] : array();
                $ver = isset($style[3]) ? $style[3] : false;
                $media = isset($style[4]) ? $style[4] : 'all';
                wp_enqueue_style( $style[0], $style[1], $deps, $ver, $media );
            }
        }
    }

    protected function admin_enqueue_scripts(){
        if ( ! empty( $this->adminScripts ) ) {
            foreach ( $this->adminScripts as $script ) {
                $deps = isset($script[2]) ? $script[2] : array();
                $ver = isset($script[3]) ? $script[3] : false;
                $in_footer = isset($script[4]) ? $script[4] : false;
                wp_enqueue_script( $script[ 0 ], $script[1], $deps, $ver, $in_footer );
            }
        }
    }

    protected function admin_localize_scripts(){
        if ( ! empty( $this->adminLocalizes ) ) {
            foreach ( $this->adminLocalizes as $script ) {
                wp_localize_script( $script[ 0 ], $script[1], $script[2] );
            }
        }
    }

    protected function wp_print_styles(){
        if ( ! empty( $this->styles ) ) {
            foreach ( $this->styles as $style ) {
                $deps = isset($style[2]) ? $style[2] : array();
                $ver = isset($style[3]) ? $style[3] : false;
                $media = isset($style[4]) ? $style[4] : 'all';
                wp_enqueue_style( $style[0], $style[1], $deps, $ver, $media );
            }
        }
    }

    protected function wp_enqueue_scripts(){
        if ( ! empty( $this->scripts ) ) {
            foreach ( $this->scripts as $script ) {
                $deps = isset($script[2]) ? $script[2] : array();
                $ver = isset($script[3]) ? $script[3] : false;
                $in_footer = isset($script[4]) ? $script[4] : false;
                wp_enqueue_script( $script[ 0 ], $script[1], $deps, $ver, $in_footer );
            }
        }
    }

    public function __toString(){
        return (string) $this->render();
    }

    public function render($key = null, $data = null){
        if($key) $this->key = $key;
        if($data) $this->data = $data;

        if ( ! $this->container->isAjax() ) {
            $this->admin_enqueue_scripts();
            $this->admin_localize_scripts();
            $this->admin_print_styles();
            $this->wp_enqueue_scripts();
            $this->wp_print_styles();
        }


        $func = function () {

            // make available plugin instance
            $plugin = $this->container;
            $blade = new Blade($plugin->resource_path . '/views', $plugin->storage_path . '/plugin/views');
            if ( ! is_null( $this->data ) && is_array( $this->data ) ) {
                echo $blade->view()->make($this->key, $this->data)->with('plugin',$plugin);
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

    public function withAdminStyles( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ){
        $this->adminStyles[] = [ $handle, $src, $deps, $ver, $media ];

        return $this;
    }

    public function withAdminScripts( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ){
        $this->adminScripts[] = [ $handle, $src, $deps, $ver, $in_footer ];

        return $this;
    }

    public function withAdminLocalizeScripts( $handle, $name, $data = null ){
        $this->adminLocalizes[] = [ $handle, $name, $data ];

        return $this;
    }

    public function withStyles( $name, $deps = [], $ver = [] ){
        $this->styles[] = [ $name, $deps, $ver ];

        return $this;
    }

    public function withScripts( $name, $deps = [], $ver = [] ){
        $this->scripts[] = [ $name, $deps, $ver ];

        return $this;
    }

    public function with( $data ){
        if ( is_array( $data ) ) {
            $this->data[] = $data;
        }
        elseif ( func_num_args() > 1 ) {
            $this->data[] = [ $data => func_get_arg( 1 ) ];
        }

        return $this;
    }

    public function setKey($key){
        $this->key = $key;
    }

    public function setData($data){
        $this->data = $data;
    }

}
