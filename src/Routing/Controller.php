<?php

namespace SetranMedia\WpPluginner\Routing;

use SetranMedia\WpPluginner\View\View;
use SetranMedia\WpPluginner\Foundation\Http\Request;
use Illuminate\Support\Str;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Controller
{

  private $_request = null;

  /**
   * Ths method is executed by add_action( 'load-{}' )
   *
   */
  public function load() {}

  /**
   * Get a method/attribute if exists.
   *
   * @param $name
   *
   * @return mixed
   */
  public function __get( $name )
  {
    $method = 'get' . Str::studly( $name ) . 'Attribute';
    if ( method_exists( $this, $method ) ) {
      return $this->{$method}();
    }
  }

  /**
   * Redirect the browser to a localtion. If the header has been sent, then a Javascript and meta refresh will
   * inserted into the page.
   *
   * @param string $location
   */
  public function redirect( $location = '' )
  {
    $args = array_filter( array_keys( $_REQUEST ), function ( $e ) {
      return ( $e !== 'page' );
    } );

    if ( empty( $location ) ) {
      $location = remove_query_arg( $args );
    }

    if ( headers_sent() ) {

      echo '<script type="text/javascript">';
      echo 'window.location.href="' . $location . '";';
      echo '</script>';
      echo '<noscript>';
      echo '<meta http-equiv="refresh" content="0;url=' . $location . '" />';
      echo '</noscript>';
      exit();

    }

    wp_redirect( $location );
    exit();
  }

  /**
   * Used to display a view from a menu. The method is usually `index` or `store`. These can return a view instance.
   *
   * @param $method
   */
  public function render( $method )
  {
    $view = $this->{$method}();

    if ( $view instanceof View ) {
      return $view->render();
    }
  }

  /**
   * Get the Request instance.
   *
   * @return \SetranMedia\WpPluginner\Foundation\Http\Request
   */
  public function getRequestAttribute()
  {
    if( is_null( $this->_request ) ) {
      $this->_request = new Request();
    }
    return $this->_request;
  }

}
