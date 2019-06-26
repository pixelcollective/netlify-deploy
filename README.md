# Netlify Deploy

Automatic Netlify builds on WordPress publish and update events.

## Instructions

```bash
composer require pixelcollective/netlify-deploy
```

First, request a webhook URL from Netlify to use to trigger builds (you can find the "Build hooks" section on your site dashboard at `/settings/deploys#build-hooks`).

Next, add the URL to your site .env variables and activate the plugin. Env variables are included in `.env.example` and below, for your reference:

```bash
## Hooks
NETLIFY_WEBHOOK_DEVELOPMENT=https://api.netlify.com/build_hooks/{yourBuildHookId}
NETLIFY_WEBHOOK_STAGING=https://api.netlify.com/build_hooks/{yourBuildHookId}
NETLIFY_WEBHOOK_PRODUCTION=https://api.netlify.com/build_hooks/{yourBuildHookId}
```

## Filters

### netlify_posttypes

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

### netlify_webhoks

If you don't want to use env variables because you don't have a deployment strategy and enjoy living poorly you can also hoook into the `netlify_hooks` filter and pass your own array of webhooks to use instead.

```php
add_filter('netlify_hooks', [
  'development' => 'https://api.netlify.com/build_hooks/########',
  'testing'     => 'https://api.netlify.com/build_hooks/########',
  'production'  => 'https://api.netlify.com/build_hooks/########',
])
```


## Happy static publishing!

MIT Licensed // 2019+ Tiny Pixel Collective, LLC
