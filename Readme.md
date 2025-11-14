# Senthor IO

**Contributors:** Senthor.io
**Tags:** AI, paywall, content, protection, media
**Requires at least:** 10.0
**Tested up to:** 10.5
**License:** GPL-2.0-or-later
**Project URL:** https://senthor.io

---

## Description

Generative AIs (ChatGPT, Perplexity, Mistral, etc.) are scraping millions of articles every day, without authorization, control, or revenue for publishers.
**Senthor for Drupal** allows you to take back control:

- Real-time detection of AI crawlers.
- Selective blocking or authorization.
- Monetization of each AI request as a new revenue stream.
- Dashboard with detailed statistics.

This plugin connects your Drupal site to your Senthor account.
Once activated, your content is monitored and protected in the background without modifying your site.

---

## Features

- Intercepts **GET requests** for all front-end pages.
- Skips admin pages, AJAX requests, and internal Drupal sub-requests.
- Returns **HTTP 402** if the request is blocked.
- Lightweight and easy to install — no settings form or configuration needed.

---

## Installation

1. Configure your website on senthor.io

- Create a free account on [Senthor.io](https://www.senthor.io)
- Add your domain (e.g. `www.yoursite.com`) in your Senthor dashboard.

2. Place the module in your custom modules directory:

```text
web/modules/custom/senthor_io
```

3. Enable the module using Drush or the Drupal admin UI:

```text
vendor/bin/drush en senthor_io -y
```

4. Clear the cache
```text
vendor/bin/drush cr
```

5. Visit your site as usual. The module will automatically intercept front-end GET requests and validate them via the API.

---

## Usage
- The module automatically applies to all HTML front-end pages.
- Admin users, AJAX requests, and sub-requests are ignored.
- Requests blocked by the API will return a 402 response
- senthor.io's dashboard will show you your AI traffic

---

## License
GPL-2.0-or-later

---

## Changelog

### 1.0.0
- Initial release: intercepts GET requests and validates via Senthor API.

