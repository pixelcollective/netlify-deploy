# Netlify Deploy

Automatic Netlify builds on WordPress publish and update events.

## Instructions

```bash
composer require pixelcollective/netlify-deploy
```

First, request a webhook URL from Netlify to use to trigger builds (you can find the "Build hooks" section on your site dashboard at `/settings/deploys#build-hooks`).

Next, add the URL to your site .env variables and activate the plugin.

```bash
## Hooks
NETLIFY_WEBHOOK_DEVELOPMENT=https://api.netlify.com/build_hooks/{yourBuildHookId}
NETLIFY_WEBHOOK_STAGING=https://api.netlify.com/build_hooks/{yourBuildHookId}
NETLIFY_WEBHOOK_PRODUCTION=https://api.netlify.com/build_hooks/{yourBuildHookId}
```

## Happy static publishing!

MIT Licensed // 2019+ Tiny Pixel Collective, LLC
