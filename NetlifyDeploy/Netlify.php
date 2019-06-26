<?php

namespace TinyPixel\NetlifyDeploy;

use \GuzzleHttp\Client;

class Netlify
{
    /**
     * Construct
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Initializes http client for webhook request
     * @return self $this
     */
    public function setupClient()
    {
        // instantiate guzzle for POST req
        $this->client = new Client(['headers' => [
            'Content-Type' => 'application/json',
        ]]);

        return $this;
    }

    /**
     * Return hook for a given environment
     */
    public static function getNetlifyHook($environment)
    {
        return (object) [
            'key'   => "NETLIFY_WEBHOOK_{$environment}",
            'value' => env("NETLIFY_WEBHOOK_{$environment}")
        ];
    }

    /**
     * Sets webhook URLs for POSTing
     * @return self $this
     */
    public function setWebhooks()
    {
        $this->hooks = (object) [
            'development' => self::getNetlifyHook('DEVELOPMENT')->value,
            'staging'     => self::getNetlifyHook('STAGING')->value,
            'production'  => self::getNetlifyHook('PRODUCTION')->value,
        ];

        return $this;
    }

    public function filterWebhooks()
    {
        has_filter('netlify_webhooks') &&
            apply_filters('netlify_webhooks', $this->hooks);

        return $this;
    }

    /**
     * Hooks into WordPress lifecycle
     */
    public function addActions()
    {
        // mile high
        foreach ($this->postTypes as $type) {
            add_action("publish_{$type}", [
                $this, 'onPublish'
            ], 10, 2);
        }

        return $this;
    }

    /**
     * Fields PostTypes that are set to trigger webhook
     * @param array postTypes
     * @return self $this
     */
    public function usePostTypes()
    {
        has_filter('netlify_posttypes') &&
            apply_filters('netlify_posttypes', $this->plugin->postTypes);

        $this->postTypes = $this->plugin->postTypes;

        return $this;
    }

    /**
     * POSTs to appropriate webhook on publish actions
     */
    public function onPublish()
    {
        // set the netlify hook based on envvar
        switch ($this->plugin->runtime->env) :
            case 'DEVELOPMENT':
                $this->hook = $this->hooks->development;
                break;

            case 'STAGING':
                $this->hook = $this->hooks->staging;
                break;

            case 'PRODUCTION':
                $this->hook = $this->hooks->production;
                break;
        endswitch;

        // make the run
        $this->hook &&
            $this->client->post($this->hook);
    }
}
