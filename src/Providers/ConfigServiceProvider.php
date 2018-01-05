<?php

namespace SetranMedia\WpPluginner\Providers;

use SetranMedia\WpPluginner\Support\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Config\Repository;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(){
        $pluginPath = $this->app->pluginPath();
        $this->app->bind('config', function () use ($pluginPath){
            $config = new Config;
            $config->loadConfigurationFiles($pluginPath . '/config');
            return $config;
        }, true);
        $this->setViewConfiguration();
        $this->setCacheConfiguration();
    }

    protected function setViewConfiguration(){
        if (!$this->app['config']->get('view',false)) {
            $this->app['config']->set('view', [
                'paths' => [$this->app->pluginPath() . '/resources/views'],
                'compiled' => $this->app->pluginPath() . '/storage/plugin/views'
            ]);
        }
    }

    protected function setCacheConfiguration(){
        if (!$this->app['config']->get('cache',false)) {
            $this->app['config']->set('cache', [
                'default' => 'file',
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => $this->app->pluginPath() . '/storage/plugin/cache',
                    ],
                ]
            ]);
        }
    }
}
