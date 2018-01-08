<?php

namespace SetranMedia\WpPluginner\Concern;

use Illuminate\Support\HtmlString;

trait ContainerTrait
{
    public function request()
    {
        return $this['request'];
    }

    public function response()
    {
        return $this['response'];
    }

    public function session()
    {
        return $this['session'];
    }

    public function config()
    {
        return $this['config'];
    }

    public function storage()
    {
        return $this['filesystem'];
    }

    public function cache()
    {
        if ($this->bound('cache')) {
            return $this['cache'];
        } else {
            throw new \Exception('Illuminate Cache Disabled');
        }

    }

    public function option()
    {
        return $this['plugin.option'];
    }

    /**
     * Access ViewFactory
     * @return \Illuminate\View\Factory
     */
    public function viewFactory(){
        return $this['view'];
    }

    /**
     * Access View
     * @return mixed
     */
    public function view($template, $parameters = array(), $status = 200)
    {
        //Setup Template Variables
        $data = array(
            'request' => $this->request(),
            'currentUserId' => get_current_user_id(),
        );
        if($this->bound('session')){
	        $data = array_merge($data, $this->session()->getOldInput());
        }
        //Loop & Combine User Template Variables
        foreach ($parameters as $parameter => $value) {
            $data[$parameter] = $value;
        }
	    //Bind Template Variables to ViewRendered
        $viewRendered = $this['view']->make($template, $data)->render();
	    return $this->respond($viewRendered);
    }

    /**
     * Send Response with CookieJar
     * @return mixed
     * end chain
     */
    public function respond($content = null, $status = 200)
    {
        if($this->bound('session')) {
            $this->session()->save();
        }
        print new HtmlString($content);
    }

    public function admin()
    {
        return $this['plugin.admin'];
    }
}
