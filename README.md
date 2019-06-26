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

### netlify_webhooks

You can modify your webhooks at runtime using the `netlify_hooks` filter:

```php
add_filter('netlify_hooks', [
  'development' => 'https://api.netlify.com/build_hooks/########',
  'testing'     => 'https://api.netlify.com/build_hooks/########',
  'production'  => 'https://api.netlify.com/build_hooks/########',
])
```

### netlify_env_override

If you don't want to use env variables because you don't have a deployment strategy and enjoy living poorly you can hook into the `netlify_env_override` filter and pass the target webhook directly at runtime:

```php
add_filter('netlify_env_override', 'https://api.netlify.com/build_hooks/########');
```

### netlify_transitions

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

You can learn more about the [`Post Status Transitions` API in the Codex](https://codex.wordpress.org/Post_Status_Transitions).

## Happy static publishing!

MIT Licensed // 2019+ Tiny Pixel Collective, LLC
