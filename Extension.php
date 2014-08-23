<?php

namespace Bolt\Extension\ClientLogin;

use Bolt\CronEvents;

/**
 * Login with OAuth via HybridAuth
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Extension extends \Bolt\BaseExtension
{
    /**
     * @var string Extension name
     */
    const NAME = 'ClientLogin';

    /**
     * @var ClientLogin\Controller
     */
    private $controller;

    public function getName()
    {
        return Extension::NAME;
    }

    public function initialize()
    {
        /*
         * Config
         */
        $this->setConfig();

        /*
         * Backend
         */
        if ($this->app['config']->getWhichEnd() == 'backend') {
            // Check & create database tables if required
            $records = new ClientRecords($this->app);
            $records->dbCheck();
        }

        /*
         * Frontend
         */
        if ($this->app['config']->getWhichEnd() == 'frontend') {
            // If debug is set, also set the path for the debug log.
            if ($this->config['debug_mode']) {
                $this->config['debug_file'] = $this->app['resources']->getPath('cache') . "/authenticate.log";
                @touch($this->config['debug_file']);
            }

            // Create and store session
            $this->app['extensions.' . Extension::NAME]->session = new Session($this->app);

            // Set up routes
            $this->setController();

            // Twig functions
            $this->app['twig']->addExtension(new ClientLoginTwigExtensions($this->app));
        }

        /*
         * Scheduled cron listener
         */
        $this->app['dispatcher']->addListener(CronEvents::CRON_DAILY, array($this, 'cronDaily'));
    }

    /**
     * Cron jobs
     */
    public function cronDaily()
    {
        $record = new ClientRecords($this->app);
        $record->doRemoveSessionsOld();
    }

    /**
     * Create controller and define routes
     */
    private function setController()
    {
        // Create controller object
        $this->controller = new Controller($this->app);

        // Member login
        $this->app->match("{$this->config['basepath']}/login", array($this->controller, 'getAuthenticationLogin'))
                  ->bind('getAuthenticationLogin')
                  ->method('GET');

        // Member logout
        $this->app->match("{$this->config['basepath']}/logout", array($this->controller, 'getAuthenticationLogout'))
                  ->bind('getAuthenticationLogout')
                  ->method('GET');

        // OAuth callback URI
        $this->app->match("{$this->config['basepath']}/endpoint", array($this->controller, 'getAuthenticationEndpoint'))
                  ->bind('getAuthenticationEndpoint')
                  ->method('GET|POST');
    }

    /**
     * Set up config and defaults
     */
    private function setConfig()
    {
        // Sane defaults
        if (empty($this->config['basepath'])) {
            $this->config['basepath'] = "authenticate";
        }
        if (empty($this->config['template']['profile'])) {
            $this->config['template']['profile'] = "_profile.twig";
        }
        if (empty($this->config['template']['button'])) {
            $this->config['template']['button'] = "_button.twig";
        }

        /*
         * Set HybridAuth
         */

        // Pass the base endpoint URL to HybridAuth
        $this->config['auth']['hybridauth']['base_url'] = $this->app['paths']['rooturl'] . $this->config['basepath'] . '/endpoint';

        $this->config['auth']['hybridauth']['providers'] = $this->config['providers'];
        unset($this->config['auth']['hybridauth']['providers']['Password']);

        // Apparently "A set of identifiers that identify a setting in the listing". Ok, whatever, HybridAuth.
        $this->config['auth']['hybridauth']['identifier'] = "key";

        // If debug is set, also set the path for the debug log.
        $this->config['auth']['hybridauth']['debug_mode'] = $this->config['debug_mode'];
        if ($this->config['debug_mode']) {
            $this->config['auth']['hybridauth']['debug_file'] = $this->app['resources']->getPath('cache') . "/authenticate.log";
            @touch($this->config['auth']['hybridauth']['debug_file']);
        }

        /*
         * Password auth
         */
        $this->config['auth']['password'] = $this->config['providers']['Password'];
    }
}
