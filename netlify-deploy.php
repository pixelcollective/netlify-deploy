<?php
/**
 * Plugin Name:     Netlify Deploy
 * Plugin URI:      https://github.com/pixelcollective/netlify-deploy
 * Description:     Run netlify builds when site content is updated
 * Version:         0.1.0
 * Author:          Tiny Pixel Collective
 * Author URI:      https://tinypixel.dev
 * License:         MIT License
 * Text Domain:     netlify-deploy
 * Domain Path:     /resources/lang
 */

namespace TinyPixel;

class NetlifyDeploy
{
    /**
     * Construct
     */
    public function __construct()
    {
        // Plugin meta
        $this->plugin = (object) [
            'composer' => __DIR__ . '/vendor/autoload.php',
            'requires' => (object) [
                'php'  => '7.2',
                'wp'   => '5.2',
            ],
        ];

        // WP/server information
        $this->site = (object) [
            'env' => env('WP_ENV'),
            'php' => phpversion(),
            'wp'  => get_bloginfo('version'),
        ];

        // Netlify webhooks from .env
        $this->netlify = (object) [
            'hooks' => (object) [
                'development' => env('NETLIFY_WEBHOOK_DEVELOPMENT'),
                'staging'     => env('NETLIFY_WEBHOOK_STAGING'),
                'production'  => env('NETLIFY_WEBHOOK_PRODUCTION'),
            ],
        ];

        /**
         * Trigger build on these hooks
         */
        $this->hooks = [
            'publish_post',
            'publish_page',
        ];
    }

    /**
     * Plugin runtime
     */
    public function run()
    {
        // preflight compat checks
        $this->checkPHPVersion()
                ->checkWPVersion()
                ->checkComposer();

        // setup http client
        $this->clientInit();

        /**
         * @link https://codex.wordpress.org/Plugin_API/Action_Reference/publish_post
         */
        foreach ($this->hooks as $hook) {
            add_action($hook, [$this, 'onPublish'], 10, 2);
        }
    }

    /**
     * Initialize http client for webhook request
     */
    public function clientInit()
    {
        // load container dependencies
        require $this->plugin->composer;

        // instantiate guzzle for POST req
        $this->client = new \GuzzleHttp\Client(['headers' => [
            'Content-Type' => 'application/json',
        ]]);
    }

    /**
     * POST to appropriate webhook on publish actions
     */
    public function onPublish($ID, $post)
    {
        // set the netlify hook as specified for the current env
        switch ($this->site->env) :
            case 'development':
                $this->hook = $this->netlify->hooks->development;
                break;

            case 'staging':
                $this->hook = $this->netlify->hooks->staging;
                break;

            case 'production':
                $this->hook = $this->netlify->hooks->production;
                break;
        endswitch;

        // make the run
        if (isset($this->hook)) {
            $this->client->post($this->hook);
        }
    }

    /**
     * Checks for minimum PHP version compatibility
     */
    private function checkPHPVersion()
    {
        if (version_compare($this->plugin->requires->php, $this->site->php, '>')) {
            $this->error(
                __('You must be using PHP'. $this->plugin->requires->php .'or greater.', 'netlify-deploy'),
                __("Invalid PHP version ({$this->site->php})", 'netlify-deploy')
            );
        }

        return $this;
    }

    /**
     * Checks for minimum WordPress version compatibility
     */
    private function checkWPVersion()
    {
        if (version_compare($this->plugin->requires->wp, $this->site->wp, '>')) {
            $this->error(
                __('You must be using WordPress'. $this->plugin->requires->wp .'or greater.', 'netlify-deploy'),
                __("Invalid WordPress version ({$this->site->wp})", 'netlify-deploy')
            );
        }

        return $this;
    }

    /**
     * Checks for vendor dependencies
     */
    private function checkComposer()
    {
        if (!file_exists($this->plugin->composer)) {
            $this->error(
                __('You must run <code>composer install</code> from the "Netlify Deploy" plugin directory.', 'netlify-deploy'),
                __('Autoloader not found.', 'netlify-deploy')
            );
        }

        return $this;
    }

    /**
     * Displays error message
     */
    private function error($message, $subtitle = '', $title = '')
    {
        $title = $title ?: __('Netlify Deploy Runtime Error', 'netlify-deploy');
        $footer = '<a href="https://tinypixel.dev/plugins/netlify-deploy/">tinypixel.dev/plugins/netlify-deploy/</a>';
        $message = "<h1>{$title}<br><small>{$subtitle}</small></h1><p>{$message}</p><p>{$footer}</p>";

        wp_die($message, $title);
    }
}

(new NetlifyDeploy())->run();
