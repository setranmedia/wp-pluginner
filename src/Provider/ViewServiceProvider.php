<?php

namespace SetranMedia\WpPluginner\Provider;

use SetranMedia\WpPluginner\Foundation\ServiceProvider;

use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

if ( ! defined( 'ABSPATH' ) ) exit;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerFactory();

        $this->registerViewFinder();

        $this->registerEngineResolver();
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->plugin->singleton('view', function ($plugin) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $plugin['view.engine.resolver'];

            $finder = $plugin['view.finder'];

            $env = new Factory($resolver, $finder, $plugin['events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $env->setContainer($plugin);

            $env->share('plugin', $plugin);

            return $env;
        });
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $this->plugin->bind('view.finder', function ($plugin) {
            return new FileViewFinder($plugin['files'], $plugin['config']['view.paths']);
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $this->plugin->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various view engines with the resolver so that the
            // environment will resolve the engines needed for various views based on the
            // extension of view file. We call a method for each of the view's engines.
            foreach (['file', 'php', 'blade'] as $engine) {
                $this->{'register'.ucfirst($engine).'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerFileEngine($resolver)
    {
        $resolver->register('file', function () {
            return new FileEngine;
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function () {
            return new PhpEngine;
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $this->plugin->singleton('blade.compiler', function () {
            return new BladeCompiler(
                $this->plugin['files'], $this->plugin['config']['view.compiled']
            );
        });

        $resolver->register('blade', function () {
            return new CompilerEngine($this->plugin['blade.compiler']);
        });
    }

}
