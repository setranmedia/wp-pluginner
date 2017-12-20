<?php

namespace SetranMedia\WpPluginner\Foundation\Http;

use SetranMedia\WpPluginner\Support\Str;

if ( ! defined( 'ABSPATH' ) ) exit;

class Request
{

  protected $posts = [];

  public function __construct()
  {
    $this->posts = $_REQUEST;
  }

  public function __get( $name )
  {
    $method = 'get' . Str::studly( $name ) . 'Attribute';
    if ( method_exists( $this, $method ) ) {
      return $this->{$method}();
    }
  }

  public function getMethodAttribute()
  {
    if ( isset( $_POST[ '_method' ] ) ) {
      return strtolower( $_POST[ '_method' ] );
    }

    return strtolower( $_SERVER[ 'REQUEST_METHOD' ] );
  }

  public function verifyNonce( $nonce )
  {
    return wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $nonce );
  }

  public function get( $key, $default = null )
  {
    if ( false !== strpos( $key, '.' ) ) {
      $key = str_replace( '.', '/', $key );
      if ( isset( $this->posts[ $key ] ) ) {
        return $this->posts[ $key ];
      }

      return $default;
    }

    if ( isset( $this->posts[ $key ] ) ) {
      return $this->posts[ $key ];
    }

    return $default;
  }

  public function getAsOptions()
  {
    $array = [];

    // create an array from $_POST
    foreach ( $this->posts as $key => $value ) {
      if ( false !== strpos( $key, '/' ) ) {
        $temp = &$array;
        foreach ( explode( '/', $key ) as $branchKey ) {
          if ( ! isset( $temp[ $branchKey ] ) ) {
            $temp[ $branchKey ] = '';
          }
          $temp = &$temp[ $branchKey ];
        };
        $temp = $value;
      }
    }

    return $array;
  }

  public function getIsAjaxAttribute()
  {
    if ( defined( 'DOING_AJAX' ) ) {
      return true;
    }
    if ( isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest'
    ) {
      return true;
    }
    else {
      return false;
    }
  }

  public static function isVerb( $verb )
  {
    $verb = strtolower( $verb );

    return ( $verb == strtolower( $_SERVER[ 'REQUEST_METHOD' ] ) );
  }

}
