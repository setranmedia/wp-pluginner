<?php

namespace SetranMedia\WpPluginner\Provider;

use SetranMedia\WpPluginner\Foundation\ServiceProvider;
use SetranMedia\WpPluginner\Support\PluginOptions;

class PluginOptionsServiceProvider extends ServiceProvider
{
    public function register(){
        $this->plugin->singleton('plugin.option', function ($plugin) {
            $option = new PluginOptions($plugin['config']->get('options',[]));
            return $option;
        });
    }
}
