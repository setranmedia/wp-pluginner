<?php

namespace SetranMedia\WpPluginner;

use Illuminate\Container\Container as IlluminateContainer;

class Container extends IlluminateContainer
{
    private $plugin_directory;
    public function __construct($pluginFile)
        {
    	    $this->plugin_directory = trailingslashit(plugin_dir_path($pluginFile));
        }

        public function pluginPath()
	{
		return $this->plugin_directory;
	}
}
