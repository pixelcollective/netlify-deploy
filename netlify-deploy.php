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
 * Domain Path:     /languages
 */

namespace TinyPixel;

(new class {

    /**
     * Base string for envvars
     * @var string
     */
    public $netlifyEnv = 'NETLIFY_WEBHOOK_';

    /**
     * Composer autoload location
     * @var string
     */
    public $composer = __DIR__ . '/vendor/autoload.php';

    /**
     * PostTypes that result in called webhook
     * @var array
     */
    public $postTypes = [
        'post',
        'page',
    ];

    /**
     * Setup class parameters
     */
    public function __construct()
    {
        /**
         * Environmental variables
         * @var string
         */
        $this->currentEnv = strtoupper(env('WP_ENV'));

        /**
         * Server PHP profile
         * @var string
         */
        $this->php = phpversion();

        /**
         * Plugin profile & references
         * @var object
         */
        $this->plugin = (object) [
            'ref'      => plugin_basename(__FILE__),
            'name'     => __('Netlify Deploy', 'netlify-deploy'),
            'author'   => __('Tiny Pixel Collective, LLC', 'netlify-deploy'),
            'link'     => 'https://tinypixel.dev/netlify-deploy',
            'license'  => 'MIT',
            'requires' => (object) [
                'php'  => '7.2',
                'wp'   => '5.2',
            ],
            'translationDir' => basename(dirname(__FILE__)) . '/languages',
        ];

        /**
         * WordPress profile & references
         * @var object
         */
        $this->wp = (object) [
            'version'     => get_bloginfo('version'),
            'adminLinks'  => (object) [
                'plugins' => admin_url('plugins.php'),
            ],
            'lib' => (object) [
                'plugins' => ABSPATH . 'wp-admin/includes/plugin.php',
            ],
        ];

        /**
         * Error message template string defaults
         * @var object
         */
        $this->errorDefaults = (object) [
            'title'    => __('Netlify Deploy Runtime Error', 'netlify-deploy'),
            'subtitle' => '',
            'body'     => __('There was a problem with the plugin.', 'netlify-deploy'),
            'footer'   => __('The plugin has been deactivated.', 'netlify-deploy'),
            'link' => [
                'link_text' => __('Plugin Administration ⌫', 'netlify-deploy'),
                'link_url'  => $this->wp->adminLinks->plugins,
            ],
        ];
    }

    /**
     * Plugin runtime
     */
    public function run()
    {
        // preflight
        $this->loadTextDomain()
                ->checkPHPVersion()
                ->checkWPVersion()
                ->checkEnv()
                ->checkComposer();

        // business
        $this->setupClient()
                ->setupNetlifyWebhooks()
                ->hookPublish();
    }

    /**
     * Initializes http client for webhook request
     * @return self $this
     */
    public function setupClient()
    {
        // load dependencies
        require $this->composer;

        // instantiate guzzle for POST req
        $this->client = new \GuzzleHttp\Client(['headers' => [
            'Content-Type' => 'application/json',
        ]]);

        return $this;
    }

    /**
     * Sets webhook URLs for POSTing
     * @return self $this
     */
    private function setupNetlifyWebhooks()
    {
        $this->netlify = (object) [
            'hooks' => (object) [
                'development' => env("{$this->netlifyEnv}DEVELOPMENT"),
                'staging'     => env("{$this->netlifyEnv}STAGING"),
                'production'  => env("{$this->netlifyEnv}PRODUCTION"),
            ],
        ];

        has_filter('netlify_webhooks') &&
            apply_filters('netlify_webhooks', $this->netlify->hooks);

        return $this;
    }

    /**
     * Hooks into WordPress lifecycle
     */
    private function hookPublish()
    {
        // mile high
        foreach ($this->getPostTypes() as $type) {
            add_action("publish_{$type}", [
                $this, 'onPublish'
            ], 10, 2);
        }
    }

    /**
     * Fields PostTypes that are set to trigger webhook
     * @return array $postTypes
     */
    private function getPostTypes()
    {
        has_filter('netlify_posttypes') &&
            apply_filters('netlify_posttypes', $this->postTypes);

        return $this->postTypes;
    }

    /**
     * POSTs to appropriate webhook on publish actions
     */
    public function onPublish()
    {
        // set the netlify hook based on envvar
        switch ($this->currentEnv) :
            case 'DEVELOPMENT':
                $this->hook = $this->netlify->hooks->development;
                break;

            case 'STAGING':
                $this->hook = $this->netlify->hooks->staging;
                break;

            case 'PRODUCTION':
                $this->hook = $this->netlify->hooks->production;
                break;
        endswitch;

        // make the run
        $this->hook &&
            $this->client->post($this->hook);
    }

    /**
     * Checks for minimum PHP version compatibility
     * @return self $this
     */
    private function checkPHPVersion()
    {
        version_compare($this->plugin->requires->php, $this->php, '>') && $this->error([
            'body' => sprintf(
                __('You must be using PHP %s or greater.', 'netlify-deploy'),
                $this->plugin->requires->php,
            ),
            'subtitle' => sprintf(
                __('Invalid PHP version (%s)', 'netlify-deploy'),
                $this->php,
            ),
        ]);

        return $this;
    }

    /**
     * Checks for minimum WordPress version compatibility
     * @return self $this
     */
    private function checkWPVersion()
    {
        version_compare($this->plugin->requires->wp, $this->wp->version, '>') && $this->error([
            'body' => sprintf(
                __('You must be using WordPress %s or greater', 'netlify-deploy'),
                $this->plugin->requires->wp,
            ),
            'subtitle' => sprintf(
                __('Invalid WordPress version (%s)', 'netlify-deploy'),
                $this->wp->version,
            ),
        ]);

        return $this;
    }

    /**
     * Checks for vendor dependencies
     * @return self $this
     */
    private function checkComposer()
    {
        !file_exists($this->composer) && $this->error([
            'body'     => __('Netlify Deploy needs to be installed in order to be run.<br />Run <code>composer install</code> from the plugin directory.', 'netlify-deploy'),
            'subtitle' => __('Autoloader not found.', 'netlify-deploy'),
        ]);

        return $this;
    }

    /**
     * Checks for environment variables
     * @return self $this
     */
    private function checkEnv()
    {
        !env("{$this->netlifyEnv}{$this->currentEnv}") && $this->error([
            'body' => sprintf(
                __("The <code>%s%s</code> variable must be present.", 'netlify-deploy'),
                $this->netlifyEnv,
                $this->currentEnv
            ),
            'subtitle' => __('Netlify webhook not found.', 'netlify-deploy'),
        ]);

        return $this;
    }

    /**
     * Load plugin translations
     */
    private function loadTextDomain()
    {
        load_plugin_textdomain('netlify-deploy', false, $this->plugin->translationDir);

        return $this;
    }

    /**
     * Handles deactivating plugin and displaying errors
     * @param array $error
     */
    private function error($error)
    {
        // deactivate self
        $this->forceEjectPlugin();

        // get formatted error variables
        $dirge = $this->processError($error);

        // bear the bad news
        wp_die(
            $dirge->message,
            $dirge->title,
            $dirge->link,
        );
    }

    /**
     * Immediately deactivate plugin regardless of WordPress lifecycle
     */
    private function forceEjectPlugin()
    {
        // this runs too late for deactivation
        // so we just manually include the function
        require_once $this->wp->lib->plugins;

        is_plugin_active($this->plugin->ref) &&
            deactivate_plugins($this->plugin->ref);
    }

    /**
     * Processes error message for display to user
     *
     * @param array $error
     * @return object $error
     */
    private function processError($error)
    {
        $error = is_array($error) ? (object) $error : $error;

        $errorObj = (object) [
            'title'    => $this->errorTitle($error),
            'subtitle' => $this->errorSubtitle($error),
            'body'     => $this->errorBody($error),
            'footer'   => $this->errorFooter($error),
            'link'     => $this->errorLink($error),
        ];

        $errorObj->message = $this->errorMessage($errorObj);

        return $errorObj;
    }

    /**
     * Formats error message
     * @return string
     */
    private function errorMessage($error)
    {
        return sprintf(
            "<h1>%s<br><small>%s</small></h1><p>%s</p><p>%s</p>",
            $error->title,
            $error->subtitle,
            $error->body,
            $error->footer,
        );
    }

    /**
     * Returns error title from error object
     * @return string
     */
    private function errorTitle($error)
    {
        return isset($error->title)
            ? $error->title
            : $this->errorDefaults->title;
    }

    /**
     * Returns error subtitle from error object
     * @return string
     */
    private function errorSubtitle($error)
    {
        return isset($error->subtitle)
            ? $error->subtitle
            : $this->errorDefaults->subtitle;
    }

    /**
     * Returns error body from error object
     * @return string
     */
    private function errorBody($error)
    {
        return isset($error->body)
            ? $error->body
            : $this->errorDefaults->body;
    }

    /**
     * Returns error link from error object
     * @return string
     */
    private function errorLink($error)
    {
        return isset($error->link)
            ? $error->link
            : $this->errorDefaults->link;
    }

    /**
     * Returns footer from error object
     * @return string
     */
    private function errorFooter($error)
    {
        return isset($error->footer)
            ? $error->footer
            : $this->errorDefaults->footer;
    }
})->run();
