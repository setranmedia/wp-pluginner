<?php

namespace SetranMedia\WpPluginner;

use SetranMedia\WpPluginner\Debug\ExceptionHandler;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Illuminate\Contracts\Debug\ExceptionHandler as IlluminateExceptionHandler;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class WpPluginner extends Container
{
    use Concern\ContainerTrait,
        Concern\PluginPath,
        Concern\PluginUri;

    private $pluginFile;
    private $pluginPath;
    private $pluginUri;
    private $providers = [];
    private $metaData = [];

    public function __construct( $pluginFile )
    {
        $this->pluginFile = $pluginFile;
        $this->pluginPath = trailingslashit(plugin_dir_path($pluginFile));
        $this->pluginUri = trailingslashit(plugin_dir_url($pluginFile));
        $this->bindExceptions();
    }

    /**
	 * Register all of the configured providers.
	 *
	 * @return void
	 */
	public function registerConfiguredProviders(){
			//Count Providers Array in Config
		if (is_array($this['config']->get('plugin.providers'))) {
			foreach ($this['config']->get('plugin.providers') as $index => $namespace) {
				$this->register($namespace);
			}
		}
	}

	/**
	 * Register a service provider with the application.
	 *
	 * @param  \Illuminate\Support\ServiceProvider|string  $provider
	 * @param  array  $options
	 * @param  bool   $force
	 * @return \Illuminate\Support\ServiceProvider
	 */
	public function register($provider, $options = [], $force = false){
		if(!isset($this->providers[$provider]) || $force){
			$this->providers[$provider] = new $provider($this,$options);
			with($this->providers[$provider])->register();
		}
	}

    /**
	 * Bind Exceptions Class to Container
	 */
	private function bindExceptions()
	{
		$this->singleton(
			'Illuminate\Contracts\Debug\ExceptionHandler',
			'SetranMedia\WpPluginner\Debug\ExceptionHandler'
		);
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function reportException(\Exception $e)
	{
		$this['SetranMedia\WpPluginner\Debug\ExceptionHandler']->report($e);
	}

	/**
	 * Render the exception to a response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function renderException($request, \Exception $e)
	{
		$this['SetranMedia\WpPluginner\Debug\ExceptionHandler']->render($request, $e);
	}

    /**
	 * Load Routes into Router
	 */
	public function loadRoutes()
	{
		if ($this->bound('router')) {
			/**
			 * Use Cached Routes if Available
			 */
			if ($this['config']->get('routes.cache') && $this['files']->exists($this['config']->get('routes.compiled'))) {
				$contents = $this['files']->get($this['config']->get('routes.compiled'));
				if (!empty($contents)) {
					//Get Cached Routes & Set Them
					$this['router']->setRoutes(unserialize(base64_decode($contents)));
				}
			} else {
				//Assign Router to Simple Variable for Include
				$route = $this['router'];
				$response = $this['response'];
				//Include Routes
				if($this['files']->exists($this->base_path . 'app/Http/routes.php')){
					require_once $this->base_path . 'app/Http/routes.php';
				}
			}
			/**
			 * Store Cached Routes
			 */
			if ($this['config']->get('routes.cache') && !$this['files']->exists($this['config']->get('routes.compiled'))) {
				try {
					//Set Routes Cache
					if (!$this['files']->exists($this['config']->get('routes.compiled'))) {
						$allRoutes = $this['router']->getRoutes();
						//If Routes then Serialize
						if (count($allRoutes) > 0) {
							foreach ($allRoutes as $routeObject) {
								$routeObject->prepareForSerialization();
							}
						}
						//Store Routes in Cache
						$this['files']->put($this['config']->get('routes.compiled'), base64_encode(serialize($allRoutes)));
					}
				} catch (\Exception $exception) {
					if (!empty($exception->getMessage())) {
						add_action('admin_notices', function () use ($exception) {
							?>
							<div class="error notice">
								<p>&#9888; <?php echo $exception->getMessage(); ?></p>
								<p><em><? echo 'Route caching cannot serialize closures'; ?>.</em></p>
							</div>
							<?php
						});
					}
				}
			}
		}
	}
	/**
	 * Try Routing Requests
	 */
	public function routeRequest()
	{
		if($this->bound('router')){
			try {
				$pluginRequest = $this['request'];
				$this['router']->matched(function ($event) {
					global $wp_query;
					$wp_query->is_404 = false;
					$this->route_dispatched = true;
				});
				$response = $this['router']->dispatch($pluginRequest);
				if ($route = $pluginRequest->route()) {
					foreach ($route->computedMiddleware as $middleware) {
						$instance = $this->make($middleware);
						if (method_exists($instance, 'terminate')) {
							$instance->terminate($pluginRequest, $response);
						}
					}
				}
				if($this->bound('session')) {
					$this['session']->save();
				}
				$response->send();
				exit;
			}catch (\Exception $e) {
				$this->reportException($e);
				$this->renderException($pluginRequest, $e);
			} catch (\Throwable $e) {
				$this->reportException($e = new FatalThrowableError($e));
				$this->renderException($pluginRequest, $e);
			}
		}
	}

    protected function getPluginMetaData(){
        if (!function_exists('get_plugin_data')) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $this->metaData = get_plugin_data( $this->pluginFile );
    }

    public function __get( $name )
    {
        $method = 'get' . Str::studly($name) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
    }
}
