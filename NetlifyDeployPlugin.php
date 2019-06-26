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

class NetlifyDeployPlugin
{
    /**
     * Instance
     * @static object
     */
    public static $instance;

    /**
     * Composer autoload location
     * @var string
     */
    public $dependencies = __DIR__ . '/vendor/autoload.php';

    /**
     * Error handler in case of autoload failure
     * @var string
     */
    public $errorHandler = __DIR__ . '/src/Error.php';

    /**
     * Default PostTypes
     * @var array
     */
    public $postTypes = [
        'post',
        'page',
    ];

    /**
     * Containerized classes
     * @var array
     */
    public $services = [
        'netlify' => '\TinyPixel\NetlifyDeploy\Netlify',
        'error'   => '\TinyPixel\NetlifyDeploy\Error',
    ];

    /**
     * Setup class parameters
     */
    public function __construct()
    {
        /**
         * Plugin name
         * @var string
         */
        $this->name = basename(dirname(__FILE__));

        /**
         * Plugin service container
         * @var object
         */
        $this->container = (object) [];

        /**
         * Plugin requirements
         * @var object
         */
        $this->requires = (object) [
            'php'  => '7.2',
            'wp'   => '5.2',
        ];
        /**
         * Runtime
         * @var string
         */
        $this->runtime = (object) [
            'env' => strtoupper(env('WP_ENV')),
            'php' => phpversion(),
        ];

        /**
         * WordPress profile & references
         * @var object
         */
        $this->wordpressVersion = get_bloginfo('version');

        /**
         * Location of plugin translation files
         * @var string
         */
        $this->i18nHandle = "{$this->name}/languages";
    }

    /**
     * Returns singleton construct
     * @return self $instance
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new NetlifyDeployPlugin();
        }

        return self::$instance;
    }

    /**
     * Does compatibility checks
     */
    public function preflight()
    {
        $this
            ->loadTextDomain()
            ->checkDependencies()
            ->checkPHPVersion()
            ->checkWPVersion()
            ->checkEnv();

        return $this;
    }

    /**
     * Executes plugin runtime
     */
    public function run()
    {
        $this->container->netlify
            ->setupClient()
            ->setWebhooks()
            ->filterPostTypes()
            ->filterWebhooks()
            ->filterTransitions()
            ->filterOverrides()
            ->setTargetHook()
            ->addActions();
    }

    /**
     * Checks autoload viability
     */
    private function checkDependencies()
    {
        // If there is an issue with the autoloader this manually
        // loads the error handler class and uses it to throw an early
        // error
        !file_exists($this->dependencies) && $this->throwEarlyError([
            'body'     => __('Netlify Deploy needs to be installed in order to be run.<br />Run <code>composer install</code> from the plugin directory.', 'netlify-deploy'),
            'subtitle' => __('Autoloader not found.', 'netlify-deploy'),
        ]);

        // load dependencies
        require $this->dependencies;

        // bind services to container
        $this->bindServices();

        return $this;
    }

    /**
     * Binds service classes
     */
    private function bindServices()
    {
        foreach ($this->services as $handle => $class) {
            $this->container->$handle = new $class(self::getInstance());
        }
    }

    /**
     * Loads plugin i18n texts
     * @return self $this
     */
    private function loadTextDomain()
    {
        load_plugin_textdomain(
            'netlify-deploy',
            false,
            $this->i18nHandle
        );

        return $this;
    }

    /**
     * Checks for minimum PHP version compatibility
     * @return self $this
     */
    private function checkPHPVersion()
    {
        version_compare($this->requires->php, $this->runtime->php, '>') &&
            $this->container->error::throw([
                'body' => sprintf(
                    __('You must be using PHP %s or greater.', 'netlify-deploy'),
                    $this->requires->php,
                ),
                'subtitle' => sprintf(
                    __('Invalid PHP version (%s)', 'netlify-deploy'),
                    $this->runtime->php,
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
        version_compare($this->requires->wp, $this->wordpressVersion, '>') &&
            $this->container->error::throw([
                'body' => sprintf(__(
                    'You must be using WordPress %s or greater',
                    'netlify-deploy'
                ), $this->requires->wp),
                'subtitle' => sprintf(
                    __('Invalid WordPress version (%s)', 'netlify-deploy'),
                    $this->wordpressVersion,
                ),
            ]);

        return $this;
    }

    /**
     * Checks environment variables
     * @return self $this
     */
    private function checkEnv()
    {
        $webhook = $this->container->netlify::getNetlifyHook(
            $this->runtime->env
        );

        if (!$webhook->value && !(has_filter('netlify_webhooks') || has_filter('netlify_env_override'))) {
            $this->container->error::throw([
                    'body' => sprintf(__(
                        "The <code>%s</code> variable must be present.",
                        'netlify-deploy'
                    ), $webhook->key, $this->runtime->env),
                    'subtitle' => __('Netlify webhook not found.', 'netlify-deploy'),
                ]);
        }

        return $this;
    }

    /**
     * Throws an error before autoload
     * @param array $error
     */
    private function throwEarlyError($error)
    {
        require $this->errorHandler;

        \TinyPixel\NetlifyDeploy\Error::throw([
            'body'     => __('Netlify Deploy needs to be installed in order to be run.<br />Run <code>composer install</code> from the plugin directory.', 'netlify-deploy'),
            'subtitle' => __('Autoloader not found.', 'netlify-deploy'),
        ]);
    }
}

NetlifyDeployPlugin
    ::getInstance()
    ->preflight()
    ->run();
