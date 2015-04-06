<?php

namespace Bolt\Extension\Bolt\ClientLogin;

use Bolt\BaseExtension;
use Bolt\Events\CronEvent;
use Bolt\Events\CronEvents;

/**
 * Login with OAuth1 or OAuth2
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
 * Based on the Bolt 1.5 extension 'Authenticate' by:
 * @author Lodewijk Evers
 * @author Tobias Dammers
 * @author Bob den Otter
 */
class Extension extends BaseExtension
{
    /** @var string Extension name */
    const NAME = 'ClientLogin';

    /** @var string Extension's container */
    const CONTAINER = 'extensions.ClientLogin';

    /** @var ClientLogin\Controller */
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
         * Register ourselves as a service
         */
        $this->app->register(new Provider\ClientLoginServiceProvider($this->app));

        /*
         * Backend
         */
        if ($this->app['config']->getWhichEnd() === 'backend' || $this->app['config']->getWhichEnd() === 'cli') {
            // Check & create database tables if required
            $this->app['clientlogin.records']->dbCheck();
        }

        /*
         * Frontend
         */
        if ($this->app['config']->getWhichEnd() === 'frontend') {
            // Twig functions
            $this->app['twig']->addExtension(new Twig\ClientLoginExtension($this->app));
        }

        /*
         * Set up controller routes
         */
        $this->app->mount('/' . $this->config['basepath'], new Controller\ClientLoginController());

        /*
         * Scheduled cron listener
         */
        $this->app['dispatcher']->addListener(CronEvents::CRON_DAILY, [$this, 'cronDaily']);
    }

    /**
     * Cron jobs
     *
     * @param \Bolt\Events\CronEvent $event
     */
    public function cronDaily(CronEvent $event)
    {
        $event->output->writeln("<comment>ClientLogin: Clearing old sessions</comment>");
        $record = new ClientRecords($this->app);
        $record->doRemoveExpiredSessions();
    }

    /**
     * Set up config and defaults
     *
     * This has evolved from HybridAuth configuration and we need to cope as such
     */
    private function setConfig()
    {
        // Handle old provider config
        $providersConfig = [];
        foreach ($this->config['providers'] as $provider => $values) {
            // This needs to match the provider class name for OAuth
            $name = ucwords(strtolower($provider));

            // On/off switch
            $providersConfig[$name]['enabled'] = $values['enabled'];

            // Keys
            $providersConfig[$name]['clientId']     = $values['clientId']     ? : $values['keys']['id'];
            $providersConfig[$name]['clientSecret'] = $values['clientSecret'] ? : $values['keys']['secret'];

            // Scopes
            if (isset($values['scopes'])) {
                $providersConfig[$name]['scopes'] = $values['scopes'];
            } elseif (isset($values['scope']) && !isset($values['scopes'])) {
                $providersConfig[$name]['scopes'] = explode(' ', $values['scope']);
            }
        }

        $providersConfig['Password']['form'] = $this->getPasswordFormFields();

        // Write it all back
        $this->config['providers'] = $providersConfig;
    }

    /**
     * Symfony/Bolt Forms fields for the password auth
     *
     * @return array
     */
    private function getPasswordFormFields()
    {
        return [
            'notification' => ['enabled' => false],
            'feedback'     => [
                'success' => '',
                'error'   => ''
            ],
            'fields'       => [
                'username' => [
                    'type'    => 'text',
                    'options' => [
                        'required'    => true,
                        'label'       => 'Username',
                        'constraints' => [
                            'NotBlank',
                            'Length' => [
                                'min' => 5,
                                'max' => 64
                            ]
                        ],
                        'attr'        => [
                            'placeholder' => 'Enter your username'
                        ]
                    ]
                ],
                'password' => [
                    'type' => 'password',
                    'options' => [
                        'required'    => true,
                        'label'       => 'Password',
                        'constraints' => [
                            'NotBlank',
                            'Length' => [
                                'min' => 6,
                                'max' => 64
                            ]
                        ],
                        'attr'        => [
                            'placeholder' => 'Enter your password'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Default config options
     *
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
            'providers' => [
                'Password' => [
                    'enabled' => false
                ],
                'Google' => [
                    'enabled' => false
                ],
                'Facebook' => [
                    'enabled' => false
                ],
                'Twitter' => [
                    'enabled' => false
                ],
                'GitHub' => [
                    'enabled' => false
                ]
            ],
            'basepath' => 'authenticate',
            'template' => [
                'profile'  => '_profile.twig',
                'button'   => '_button.twig',
                'password' => '_password.twig'
            ],
            'zocial'        => false,
            'login_expiry'  => 14,
            'debug_mode'    => false,
            'response_noun' => 'hauth.done'
        ];
    }
}
