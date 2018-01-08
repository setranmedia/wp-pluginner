<?php

namespace SetranMedia\WpPluginner\Controller;

use SetranMedia\WpPluginner\WpPluginner;
use SetranMedia\WpPluginner\Foundation\Controller;

class DevController extends Controller
{

    public $data, $attributes, $parameters;
    protected $this;

    /**
     * AdminController constructor.
     * @param $attributes (PanelAttributes)
     *
     * $this->attributes['pageTitle'],
     * $this->attributes['menuTitle'],
     * $this->attributes['capability'],
     * $this->attributes['slug'],
     */
    public function __construct($attributes = array(), $namespace = null)
    {
        parent::__construct($attributes, $namespace);

        $this->attributes['config'] = $this->plugin->config();
        $this->attributes['request'] = $this->plugin->request();

        if($this->plugin->bound('router')){
           $this->attributes['routes'] = $this->plugin['router'];
       }

        $this->attributes['plugin'] = $this->plugin;

        $this->data();
        $this->process();
    }

    /**
     * Load Data
     * @return mixed
     */
    public function data()
    {

        if ($this->plugin->bound('session')) {
            $this->attributes['paths']['sessions'] = $this->plugin->config()->get('session.files');
            $this->attributes['sizes']['sessions'] = $this->calcDiskSize($this->attributes['paths']['sessions']);
        }
        if ($this->plugin->bound('router') && $this->plugin->config()->get('routes.cache')) {
            $this->attributes['paths']['routes'] = $this->plugin->config()->get('routes.compiled');
            $this->attributes['sizes']['routes'] = $this->calcDiskSize($this->attributes['paths']['routes']);
        }

        if ($this->plugin->bound('cache')) {
            $this->attributes['paths']['objects'] = $this->plugin->config()->get('cache.stores.file.path');
            $this->attributes['sizes']['objects'] = $this->calcDiskSize($this->attributes['paths']['objects']);
        }
        $this->attributes['paths']['views'] = $this->plugin->config()->get('view.compiled');
        $this->attributes['sizes']['views'] = $this->calcDiskSize($this->attributes['paths']['views']);
    }

    /**
     * Handle Request
     * @return mixed
     */
    public function process()
    {

        if ($this->plugin->request()->has('_flush')) {

            if ($this->flushCache($this->plugin->request()->get('_flush'))) {

                $this->attributes['alertClass'] = 'success';
                $this->attributes['messages'] = array(
                    '&#10004; ' . $this->plugin->config()->get('plugin.namespace') . ' ' . ucwords($this->plugin->request()->get('_flush')) . ' flushed successfully.'
                );
                //Refresh Data
            } else {

                $this->attributes['alertClass'] = 'error';
                $this->attributes['messages'] = array(
                    '&#9888; ' . $this->plugin->config()->get('plugin.namespace') . ' ' . ucwords($this->plugin->request()->get('_flush'))
                    .' could not be flushed.  The file(s) may not exist; If they exist, check directory structure, permissions and config paths.'
                );
            }

            $this->data();
        }

    }

    /**
     * Build Template
     * @return mixed
     */
    public function show()
    {

        if ($this->plugin['request']->ajax()) {
            $this->plugin['response']->setContent(html_entity_decode($this->attributes['messages'][0]))->send();
            exit;
        } else {
            $this->plugin->view('admin.framework.settings', $this->attributes);
        }
    }

    /**
     * Format File Sizes
     * @return string
     */
    public function calcDiskSize($path, $bytes = 0)
    {
        if ($this->plugin['files']->exists($path)) {
            if ($this->plugin['files']->isDirectory($path)) {
                foreach ($this->plugin['files']->glob($path) as $file) {
                    $bytes += $this->plugin['files']->size($file);
                }
            } else {
                $bytes += $this->plugin['files']->size($path);
            }
        }
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        $size = round($bytes, 2);
        return ($size == 0 ? '-' : $size . ' ' . $units[$i]);
    }

    /**
     * Flush Cache
     * @param $path
     * @return boolean
     */
    public function flushCache($path)
    {
        if ($path == 'sessions' && $this->plugin->bound('session')) {
            return true;
        } elseif (isset($this->attributes['paths'][$path])) {
            return $this->plugin['files']->cleanDirectory($this->attributes['paths'][$path]);
        }
        return false;
    }
}
