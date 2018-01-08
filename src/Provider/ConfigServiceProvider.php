<?php

namespace SetranMedia\WpPluginner\Provider;

use SetranMedia\WpPluginner\Foundation\ServiceProvider;

use Illuminate\Config\Repository;
use Symfony\Component\Finder\Finder;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(){
        $pluginPath = $this->plugin->base_path;
        $this->plugin->singleton('config', function () use ($pluginPath){
            $config = new Repository;
            $phpFiles = Finder::create()->files()->name('*.php')->in($pluginPath . '/config')->depth(0);

            foreach ($phpFiles as $file) {
                $realPath = $file->getRealPath();
                $config->set(basename($realPath, '.php'), require $realPath);
            }
            return $config;
        });
        $this->setViewConfiguration();
        $this->setCacheConfiguration();
        $this->setSessionConfiguration();
    }

    protected function setViewConfiguration(){
        if (!$this->plugin['config']->get('view',false)) {
            $this->plugin['config']->set('view', [
                'paths' => [$this->plugin->base_path . '/resource/view'],
                'compiled' => $this->plugin->base_path . '/storage/plugin/view'
            ]);
        }
    }

    protected function setCacheConfiguration(){
        if (
            $this->plugin['config']->get('plugin.cache_enabled') &&
            !$this->plugin['config']->get('cache')
        ) {
            $this->plugin['config']->set('cache', [
                'enabled' => true,
                'default' => 'file',
                'stores' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => $this->plugin->base_path . '/storage/plugin/cache',
                    ],
                ]
            ]);
        }
    }

    protected function setSessionConfiguration(){
        if (
            $this->plugin['config']->get('plugin.session_enabled') &&
            !$this->plugin['config']->get('session')
        ) {
            $this->plugin['config']->set('session', [
                'driver' => 'file',
                'lottery' => [2, 100],
                'lifetime' => 120,
                'expire_on_close' => true,
                'encrypt' => false, //Requires Encryption Enabled
                'files' => $this->plugin->base_path . '/storage/plugin/session',
                'cookie' => $this->plugin['config']->get('plugin.namespace','WpPluginner'),
                'path' => '/',
                'domain' => '.'.parse_url(get_bloginfo('url'))['host'], //Evaluates to .domain.com
                'secure' => is_ssl(),
                'http_only' => false,
            ]);
        }
    }
}
