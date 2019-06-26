<?php

namespace TinyPixel\NetlifyDeploy;

use \TinyPixel\NetlifyDeployPlugin as Plugin;
use function \admin_url;

class Error
{
    /**
     * Construct
     * @param \TinyPixel\NetlifyDeployPlugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Deactivates plugin and displays errors
     * @param array $error
     */
    public static function throw($error = null)
    {
        // this runs too late for deactivation
        // so we just manually include the function
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        // if plugin is activated, deactivate it
        is_plugin_active(Plugin::getInstance()->name) &&
            deactivate_plugins(Plugin::getInstance()->name);

        // if error was set cast it to an object
        if (!is_null($error)) {
            $error = is_array($error) ? (object) $error : $error;
        }

        // get formatted error variables
        $message = (object) [
            'title'    => isset($error->title)    ? $error->title : self::defaultTitle(),
            'subtitle' => isset($error->subtitle) ? $error->subtitle : self::defaultSubtitle(),
            'body'     => isset($error->body)     ? $error->body : self::defaultBody(),
            'footer'   => isset($error->footer)   ? $error->footer : self::defaultFooter(),
            'link'     => isset($error->link)     ? $error->link : self::defaultLink(),
        ];

        // prepare the liturgy
        $dirge = sprintf(
            "<h1>%s<br><small>%s</small></h1><p>%s</p><p>%s</p>",
            $message->title,
            $message->subtitle,
            $message->body,
            $message->footer
        );

        // bear the bad news
        wp_die($dirge, $message->title, $message->subtitle);
    }

    /**
     * Get default error message title
     * @return i18n formatted string
     */
    public static function defaultTitle()
    {
        return __(
            'Netlify Deploy Runtime Error',
            'netlify-deploy',
        );
    }

    /**
     * Get default error message subtitle
     * @return i18n formatted string
     */
    public static function defaultSubtitle()
    {
        return __(
            'There is a problem with the plugin',
            'netlify-deploy',
        );
    }

    /**
     * Get default error message body
     * @return i18n formatted string
     */
    public static function defaultBody()
    {
        return __(
            'There was a problem with the plugin.',
            'netlify-deploy',
        );
    }

    /**
     * Get default error message footer
     * @return i18n formatted string
     */
    public static function defaultFooter()
    {
        return __(
            'The plugin has been deactivated.',
            'netlify-deploy'
        );
    }

    /**
     * Get default error message link
     * @return array link
     */
    public static function defaultLink()
    {
        return [
            'link_text' => __('Plugin Administration ⌫', 'netlify-deploy'),
            'link_url'  => admin_url('plugins.php')
        ];
    }
}
