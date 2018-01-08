<?php

namespace SetranMedia\WpPluginner\Debug;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Support\Arr;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

class ExceptionHandler extends SymfonyExceptionHandler implements ExceptionHandlerContract {

    protected $handlers = array();

    protected $internalDontReport = array(
        'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
        'Symfony\Component\Routing\Exception\RouteNotFoundException'
    );

    protected $dontReport = array();

    /**
    * Determine if the exception is in the "do not report" list.
    *
    * @param  \Exception  $e
    * @return bool
    */
    protected function shouldntReport(Exception $e)
    {
        $dontReport = array_merge($this->dontReport, $this->internalDontReport);
        return !is_null(Arr::first($dontReport, function ($type) use ($e) {
            return $e instanceof $type;
        }));
    }

    /**
    * Report or log an exception.
    * @param  \Exception $exception
    * @return void
    */
    public function report( Exception $exception ) {
        if ($this->shouldntReport($exception)) {
            return;
        }
    }

    /**
    * Render an exception into an HTTP response.
    * @param  \Illuminate\Http\Request $request
    * @param  \Exception               $e
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function render( $request, Exception $exception ) {
        if ($this->shouldntReport($exception)) {
            return;
        }
        $message = '<h2>' . $exception->getMessage() . '</h2>';
        $message .= '<h4>' . $exception->getFile() . ' ' . $exception->getLine() . '</h4>';
        $message .= '<pre style="overflow-x: auto">' . $exception->getTraceAsString() . '</pre>';
        $message .= '<p><strong>WP Pluginner</strong> - <em>Made with Laravel</em>';
        $message .= '<br/><a href="https://github.com/setranmedia/wp-pluginner" style="font-size: 12px;">https://github.com/setranmedia/wp-pluginner</a></p>';
        $message .= '<style type="text/css">#error-page{ max-width:90% !important;}</style>';
        wp_die($message);
    }

    /**
    * Render an exception to the console.
    * @param  \Symfony\Component\Console\Output\OutputInterface $output
    * @param  \Exception                                       $exception
    * @return void
    */
    public function renderForConsole( $output, Exception $exception ) {
        echo $exception->getMessage();
    }
}
