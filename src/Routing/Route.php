<?php

namespace SetranMedia\WpPluginner\Routing;

if ( ! defined( 'ABSPATH' ) ) exit;

class Route {

  static $menu = [];

  public static function get( $path )
  {
    self::$menu[] = $path;
  }

}
