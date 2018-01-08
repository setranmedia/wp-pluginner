<?php

namespace SetranMedia\WpPluginner\Factory;

class AdminFactory
{

    protected $this;
    private $plugin;


    /**
     * Constructor
     * Add Plugin Container
     * @param $plugin \SetranMedia\WpPluginner\WpPluginner
     * @return void
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * addMenu
     * @return void
     */
    public function addMenu( $attributes = [], $priority = 1 )
    {
        $defaultAttributes = [
            'page_title' => $this->plugin['config']->get('plugin.namespace','WP Pluginner'),
            'menu_title' => $this->plugin['config']->get('plugin.namespace','WP Pluginner'),
            'capability' => 'read',
            'menu_slug' => $this->plugin['config']->get('plugin.slug','wp-pluginner'),
            'icon_url' => '',
            'position' => null,
            'controller' => null,
        ];

        $attributes = array_merge($defaultAttributes,$attributes);

        add_action('admin_menu', function () use ($attributes) {
            // Add shortcode support for widgets
            add_menu_page(
                $attributes['page_title'],
                $attributes['menu_title'],
                $attributes['capability'],
                $attributes['menu_slug'],
                function () use ( $attributes ){

                    if (!$attributes['controller']) {
                        return '';
                    }

                    $plugin = $this->plugin; //needed for dev controller
                    list( $controller, $method ) = explode( '@', $attributes['controller'] );
                    $this->plugin->when($controller)
                        ->needs('SetranMedia\WpPluginner\WpPluginner')
                        ->give(function(){
                            return $this->plugin;
                        });

                    $this->plugin->when($controller)
                        ->needs('$attributes')
                        ->give($attributes);

                    $instance = $this->plugin->make($controller);
                    return $instance->$method();
                },
                $attributes['icon_url'],
                $attributes['position']
            );
        }, $priority);
    }

    /**
     * addSubMenu
     * @return void
     */
    public function addSubMenu( $attributes = [], $priority = 10 )
    {
        $defaultAttributes = [
            'page_title' => $this->plugin['config']->get('plugin.namespace','WP Pluginner'),
            'menu_title' => $this->plugin['config']->get('plugin.namespace','WP Pluginner'),
            'capability' => 'read',
            'menu_slug' => $this->plugin['config']->get('plugin.slug','wp-pluginner'),
            'parent_slug' => '',
            'controller' => '',
        ];

        $attributes = array_merge($defaultAttributes,$attributes);

        add_action('admin_menu', function () use ($attributes) {
            // Add shortcode support for widgets
            add_submenu_page(
                $attributes['parent_slug'],
                $attributes['page_title'],
                $attributes['menu_title'],
                $attributes['capability'],
                $attributes['menu_slug'],
                function () use ( $attributes ){

                    $plugin = $this->plugin; //needed for dev controller
                    list( $controller, $method ) = explode( '@', $attributes['controller'] );
                    $this->plugin->when($controller)
                        ->needs('SetranMedia\WpPluginner\WpPluginner')
                        ->give(function(){
                            return $this->plugin;
                        });

                    $this->plugin->when($controller)
                        ->needs('$attributes')
                        ->give($attributes);

                    $instance = $this->plugin->make($controller);
                    return $instance->$method();
                }
            );
        }, $priority);
    }


    /**
     * addBarNode
     * @return void
     */
    public function addBarNode(
        $id,
        $title,
        $parent,
        $href,
        $group = null,
        $meta = null,
        $priority = 100
    )
    {
        add_action('admin_bar_menu', function ($wpAdminBar) use (
            $id,
            $title,
            $parent,
            $href,
            $group,
            $meta,
            $priority
        ) {

            $wpAdminBar->add_node(array(
                'id' => $id,
                'title' => $title,
                'parent' => $parent,
                'href' => $href,
                'group' => $group,
                'meta' => $meta
            ));

        }, $priority);
    }

    /**
     * addWidget
     * @return void
     */
    public function addWidget(
        $id,
        $name,
        $controllerClass
    )
    {
        add_action('wp_dashboard_setup', function () use (
            $id,
            $name,
            $controllerClass
        ) {

            wp_add_dashboard_widget(
                $id,
                $name,
                function () use ($id, $name, $controllerClass) {
                    $plugin = $this->plugin;
                    $this->plugin->when($controllerClass)
                        ->needs('$attributes')
                        ->give(compact('id', 'name', 'plugin'));

                    return $this->plugin->make($controllerClass);
                }
            );
        });
    }
}
