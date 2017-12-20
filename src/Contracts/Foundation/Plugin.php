<?php

namespace SetranMedia\WpPluginner\Contracts\Foundation;

use SetranMedia\WpPluginner\Contracts\Container\Container;

interface Plugin extends Container {

  /**
   * Get the base path of the Plugin installation.
   *
   * @return string
   */
  public function getBasePath();
}
