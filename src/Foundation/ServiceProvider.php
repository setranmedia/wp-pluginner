<?php

namespace SetranMedia\WpPluginner\Foundation;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Str;

class ServiceProvider extends BaseServiceProvider
{
    public function getPluginAttribute()
    {
        return $this->app;
    }

    public function __get( $name )
    {
        $method = 'get' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
    }
}
