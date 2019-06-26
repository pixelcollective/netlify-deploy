# Netlify Deploy 🚗💨

<p>
  <img src="https://img.shields.io/badge/version-v0.0.6-blue.svg?cacheSeconds=2592000" />
  <a href="https://github.com/pixelcollective/netlify-deploy/blob/master/LICENSE">
    <img alt="License: MIT" src="https://img.shields.io/badge/License-MIT-yellow.svg" target="_blank" />
  </a>
  <a href="https://packagist.org/pixelcollective/netlify-deploy">
    <img src="https://img.shields.io/packagist/dt/pixelcollective/netlify-deploy.svg?color=purple-blue&style=flat-square" />
  </a>
  <a href="https://github.com/pixelcollective/netlify-deploy/tree/master/CHANGELOG.md">
    <img src="https://img.shields.io/badge/Changelog-Trying-lightgrey.svg" />
  </a>
  <a href="https://twitter.com/tinydevteam">
    <img alt="Twitter: @tinydevteam" src="https://img.shields.io/twitter/follow/tinydevteam.svg?style=social" target="_blank" />
  </a>
</p>

> Automatic Netlify builds on WordPress publish and update events.

## Features

- Makes it super easy to keep a static frontend in sync with your post database
- Out of the box support for configuration with .env
- Full composer support
- Supports custom posttypes
- Supports custom publish hooks
- Free &amp; open source

## Requirements

- WordPress 5.2+
- PHP 7.2+

## Install

```sh
composer require pixelcollective\netlify-deploy
```

## Usage

Request a webhook URL from Netlify to use to trigger builds (you can find the "Build hooks" section on your site dashboard at `/settings/deploys#build-hooks`).

Next, add the URL to your site .env variables and activate the plugin. Env variables are included in `.env.example` and below, for your reference:

```bash
## Hooks
NETLIFY_WEBHOOK_DEVELOPMENT=https://api.netlify.com/build_hooks/{yourBuildHookId}
NETLIFY_WEBHOOK_STAGING=https://api.netlify.com/build_hooks/{yourBuildHookId}
NETLIFY_WEBHOOK_PRODUCTION=https://api.netlify.com/build_hooks/{yourBuildHookId}
```

### Filters

#### netlify_posttypes

By default the plugin makes a run on the provided Netlify webhook when the standard WordPress posttypes `post` and `page` undergo a change in `publish` status.

If you would like to modify this you can do so by passing an array of desired posttypes to the `netlify_posttypes` filter.

```php
add_filter('netlify_posttypes', [
  'post',
  'page',
  'video-film',
  'brandon-small-jokes',
]);
```

#### netlify_webhooks

You can modify your webhooks at runtime using the `netlify_hooks` filter:

```php
add_filter('netlify_hooks', [
  'development' => 'https://api.netlify.com/build_hooks/########',
  'testing'     => 'https://api.netlify.com/build_hooks/########',
  'production'  => 'https://api.netlify.com/build_hooks/########',
])
```

#### netlify_env_override

If you don't want to use env variables because you don't have a deployment strategy and enjoy living poorly you can hook into the `netlify_env_override` filter and pass the target webhook directly at runtime:

```php
add_filter('netlify_env_override', 'https://api.netlify.com/build_hooks/########');
```

#### netlify_transitions

Change the post status transitions which trigger a build. Usage with the default values is shown below:

```php
add_filter('netlify_transitions', [
  'draft_to_publish',
  'publish_to_draft',
  'publish_to_trash',
  'publish_to_private',
  'private_to_public',
  'new_to_publish',
]);
```

## Author

👤 **Tiny Pixel Collective, LLC**

* Twitter: [@tinydevteam](https://twitter.com/tinydevteam)
* Github: [@pixelcollective](https://github.com/pixelcollective)
* Web: [tinypixel.dev](https://tinypixel.dev)

## 🤝 Contributing

Contributions, issues and feature requests are welcome!

🧠 ? [Open a PR](https://github.com/pixelcollective/netlify-deploy/pulls). Be sure to [abide by our contribution guidelines](https://github.com/pixelcollective/netlify-deploy/tree/master/CONTRIBUTORS.md).

😩 ? [Open an issue](https://github.com/pixelcollective/netlify-deploy/issues).

## Show your support

We need ⭐️s to live. _Please_.

## 📝 License

This project is [MIT](https://github.com/pixelcollective/netlify-deploy/blob/master/LICENSE) licensed.

***

Copyright © 2019 [Tiny Pixel Collective, LLC](https://github.com/@pixelcollective).
