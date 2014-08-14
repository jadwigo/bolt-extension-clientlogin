<?php

namespace SocialLogin;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Authentication controller
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Controller
{
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @var Extension config array
     */
    private $config;

    public function __construct(Silex\Application $app, $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     *
     * @param \Silex\Application $app
     * @return multitype:
     */
    public function getAuthenticationLogin(\Silex\Application $app, Request $request)
    {
        $auth = new Session($this->app, $this->config);

        if ($auth->isLoggedIn()) {
            // User is already logged in, return them... somewhere
            $this->doRedirect();
        } else {
            $provider = $request->query->get('provider');

            if ($provider) {
                //
                $result = $auth->doLogin($provider);

                if ($result['result']) {
                    // Login done, redirect
                    $this->doRedirect();
                } else {
                    return $result['error'];
                }

            } else {
            // This shouldn't happen, just die here
                return '<pre>Provider not given</pre>';
            }
        }

        return $this->page($title, $markup);
    }

    /**
     *
     * @param \Silex\Application $app
     * @return multitype:
     */
    public function getAuthenticationLogout(\Silex\Application $app, Request $request)
    {
        return array();
    }

    /**
     *
     * @param \Silex\Application $app
     * @return multitype:
     */
    public function getAuthenticationEndpoint(\Silex\Application $app, Request $request)
    {
        return array();
    }

    /**
     * Do the best redirect we can
     *
     * @param \Silex\Application $app
     */
    private function doRedirect(\Silex\Application $app)
    {
        $returnpage = $app['request']->headers->get('referer');
        $returnpage = str_replace($app['paths']['hosturl'], '', $returnpage);

        if($returnpage) {
            simpleredirect($returnpage);
            exit;
        } else {
            return redirect('homepage');
        }
    }
}