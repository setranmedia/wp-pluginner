<?php

namespace SetranMedia\WpPluginner;

use SetranMedia\WpPluginner\Container;
use SetranMedia\WpPluginner\Provider\ConfigServiceProvider;
use SetranMedia\WpPluginner\Provider\ViewServiceProvider;
use SetranMedia\WpPluginner\Provider\DatabaseServiceProvider;
use SetranMedia\WpPluginner\Provider\PluginOptionsServiceProvider;
use SetranMedia\WpPluginner\Provider\WpServiceProvider;
use SetranMedia\WpPluginner\Provider\DeveloperModeProvider;

use SetranMedia\WpPluginner\Database\WordPressOption;
use SetranMedia\WpPluginner\Support\View;

use Illuminate\Support\Str;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\Debug\Exception\FatalThrowableError;

if ( ! defined( 'ABSPATH' ) ) exit;

class Loader
{

    protected static $instances = [];
    protected $file;

    public $plugin;

    public function __construct( $pluginFile )
    {
        $this->file = $pluginFile;
        $this->bootFramework();

        return $this;
    }

    protected function bootFramework()
    {

        $this->plugin = new WpPluginner($this->file);
        with(new ConfigServiceProvider($this->plugin))->register();

        with(new FilesystemServiceProvider($this->plugin))->register();
        with(new EventServiceProvider($this->plugin))->register();

        if($this->plugin['config']->get('plugin.cache_enabled')){
            with(new CacheServiceProvider($this->plugin))->register();
        }
        if($this->plugin['config']->get('plugin.session_enabled')){
            with(new SessionServiceProvider($this->plugin))->register();
        }

        with(new DatabaseServiceProvider($this->plugin))->register();
        with(new PluginOptionsServiceProvider($this->plugin))->register();
        with(new ViewServiceProvider($this->plugin))->register();
        with(new WpServiceProvider($this->plugin))->register();


        $this->plugin->instance('request', Request::capture());
        $this->plugin->instance('response', new Response());
        $this->setInstance();
    }

    private function setInstance()
    {
        self::$instances[($this->plugin['config']->get('plugin.namespace'))] = $this->plugin;
    }

    public static function getInstance( $namespace )
    {
        return self::$instances[$namespace];
    }

    public function bootPlugin()
    {
        try {
		    $this->plugin->registerConfiguredProviders();

            register_activation_hook( $this->file, [ $this, 'pluginActivationHook' ] );
            register_deactivation_hook( $this->file, [ $this, 'pluginDeactivationHook' ] );

	    } catch (\Exception $e) {
		    $this->plugin->reportException($e);
		    $this->plugin->renderException($this->plugin['request'], $e);
	    } catch (\Throwable $e) {
		    $this->plugin->reportException($e = new FatalThrowableError($e));
		    $this->plugin->renderException($this->plugin['request'], $e);
	    }


        add_action( 'init', array( $this, 'pluginInitAction' ) );

        $this->setInstance();
    }

    public function pluginActivationHook()
    {
        $this->plugin->options->delta();
        include_once $this->plugin->wp_property_path . '/hook/activation.php';

    }

    public function pluginDeactivationHook()
    {
         include_once $this->plugin->wp_property_path . '/hook/deactivation.php';
    }

    public function pluginInitAction()
    {
        try {
            if ($slug = $this->plugin['config']->get('plugin.development',false)) {
                with(new DeveloperModeProvider($this->plugin))->register();
            }
    		if ($this->plugin['config']->get('plugin.route_enabled') && !is_admin()) {
                $this->plugin->loadRoutes();
                
    			add_action('template_include', function ($template) {
    				//Save Plugin Instance
    				$this->setInstance();
    				if ($this->plugin['config']->get('routes.loading') == 'eager') {
    					$this->plugin->routeRequest();
    				}elseif(is_404()){
    					$this->plugin->routeRequest();
    				}
    				return $template;
    			});
    		}

            if ($slug = $this->plugin['config']->get('plugin.slug',false)) {
                do_action($slug . '_action_init_after', $this->plugin);
            }

	    }catch (\Exception $e) {
		    $this->plugin->reportException($e);
		    $this->plugin->renderException($this->plugin['request'], $e);
	    } catch (\Throwable $e) {
		    $this->plugin->reportException($e = new FatalThrowableError($e));
		    $this->plugin->renderException($this->plugin['request'], $e);
	    }
    }
}
