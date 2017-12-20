<?php

namespace SetranMedia\WpPluginner\Foundation;

use SetranMedia\WpPluginner\Support\ServiceProvider;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class WordPressShortcodesServiceProvider extends ServiceProvider {

  /**
   * List of registered shortcodes. Here you will used a methods list.
   *
   * @var array
   */
  protected $shortcodes = [ ];

  /**
   * Init the registred shortcodes.
   *
   */
  public function register()
  {
    // you can override this method to set the properties
    $this->boot();

    foreach ( $this->shortcodes as $shortcode => $method ) {
      add_shortcode( $shortcode, array( $this, $method ) );
    }
  }

  /**
   * You may override this method in order to register your own actions and filters.
   *
   */
  public function boot()
  {
    // You may override this method
  }
}
