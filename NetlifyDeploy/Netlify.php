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

        /**
         * Post Status Transitions
         */
        $this->transitions = [
            'draft_to_publish',
            'publish_to_draft',
            'publish_to_trash',
            'publish_to_private',
            'private_to_public',
            'new_to_publish',
        ];


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
     * Hooks into WordPress lifecycle
     * @return self $this
     */
    public function addActions()
    {
        foreach ($this->transitions as $transition) {
            add_action($transition, [
                $this, 'onStatusTransition'
            ], 10, 3);
        }

        foreach ($this->postTypes as $postType) {
            add_action("publish_{$postType}", [
                $this, 'postToWebhook',
            ], 10, 2);
        }

        return $this;
    }

    /**
     * On Status Transition
     * @param WP_Post $post
     */
    public function onStatusTransition($post)
    {
        if (in_array($post->post_type, $this->postTypes)) {
            $this->postToWebhook();
        }
    }

    /**
     * POSTs to appropriate webhook on publish actions
     */
    public function postToWebhook()
    {
        // make the run
        $this->targetHook &&
            $this->client->post($this->targetHook);
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

    /**
     * Return hook for a given environment
     * @param string $environment
     * @return object
     */
    public static function getNetlifyHook($environment)
    {
        return (object) [
            'key'   => "NETLIFY_WEBHOOK_{$environment}",
            'value' => env("NETLIFY_WEBHOOK_{$environment}"),
        ];
    }

    /**
     * Filters webhooks at runtime
     * @return self $this
     */
    public function filterWebhooks()
    {
        has_filter('netlify_webhooks') &&
            apply_filters('netlify_webhooks', $this->hooks);

        return $this;
    }

    /**
     * Filters envvar overrides
     * @return self $this
     */
    public function filterOverrides()
    {
        $this->override = '';

        has_filter('netlify_env_override') &&
            apply_filters('netlify_env_override', $this->override);

        return $this;
    }

    /**
     * Fields PostTypes that are set to trigger webhook
     * @param array postTypes
     * @return self $this
     */
    public function filterPostTypes()
    {
        has_filter('netlify_posttypes') &&
            apply_filters('netlify_posttypes', $this->plugin->postTypes);

        $this->postTypes = $this->plugin->postTypes;

        return $this;
    }

    /**
     * Post status transitions
     * @var array
     */
    public function filterTransitions()
    {
        has_filter('netlify_transitions') &&
            apply_filters('netlify_transitions', $this->transitions);

        return $this;
    }

    /**
     * Set target hook, accounting for overrides
     * @return self $this
     */
    public function setTargetHook()
    {
        if ($this->override !== '') {
            $this->targetHook = $this->override;
        } elseif ($this->plugin->runtime->env) {
            $this->targetHook = self::getNetlifyHook(
                $this->plugin->runtime->env
            )->value;
        }

        return $this;
    }
}
