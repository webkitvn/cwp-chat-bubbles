# CWP Chat Bubbles

A standalone WordPress plugin that auto-injects floating chat bubbles for popular messaging platforms. It is designed for sites that need fast contact affordances, QR-code handoff flows, and practical operator controls without depending on shortcodes or third-party builders.

## Features
- Auto-loads the floating bubble on the frontend, with shortcode-free default behavior.
- Supports Phone/Hotline, Zalo, Zalo OA, WhatsApp, Viber, Telegram, Facebook Messenger, Line, and KakaoTalk.
- Lets operators manage chat items with drag-and-drop ordering and optional QR-code dialogs.
- Exposes focused controls for display behavior, excluded pages, appearance tuning, analytics hooks, and small CSS overrides.
- Keeps runtime customization centralized through CSS variables instead of scattered inline style logic.
- Documents the next-step migration contracts for contextual targeting and future per-item behavior storage.

## Requirements
- WordPress 5.0+
- PHP 7.4+

## Installation
1. Upload the plugin to `/wp-content/plugins/cwp-chat-bubbles/` or install it through wp-admin.
2. Activate the plugin from the Plugins screen.
3. Open `Chat Bubbles` in wp-admin and configure your channels and display rules.

## Admin Overview
The plugin settings screen is organized into four tabs:

- `Basics`: turn the bubble on or off, choose whether it loads automatically, and decide whether labels are shown.
- `Contact Methods`: manage channel entries, contact values, QR codes, click behavior, and sort order.
- `Display`: control placement, opening state, the main toggle icon, color, and motion.
- `Advanced`: fine-tune timing, excluded pages, appearance tokens, analytics hooks, and small CSS overrides.

## Data Model Notes
- Contextual targeting migration is documented in [`docs/display-rules-migration.md`](docs/display-rules-migration.md) and [`docs/contextual-targeting-schema.md`](docs/contextual-targeting-schema.md).
- Future per-item behavior storage is documented in [`docs/item-behavior-migration.md`](docs/item-behavior-migration.md).

## Advanced Settings

### When the bubble appears
- `Initial state` controls whether the bubble starts closed or open on first render.
- `Display delay` and `Scroll trigger` help the bubble wait before showing.
- `Dismiss for session` keeps the bubble hidden after someone closes it until they leave the current tab.

### Hide on pages
- Selected excluded pages prevent auto-loading without affecting manual integration paths.

### Appearance
These controls feed the plugin CSS-variable layer:

- Bubble size
- Panel width and radius
- Modal width and radius
- Item padding
- Label text color
- Base z-index

Defaults preserve the existing presentation, while operators can tune layout density without editing template markup.

### Tracking
- External analytics emission is opt-in.
- `Provider` can be `none`, `ga4`, or `gtm`.
- `Event prefix` is sanitized to lowercase snake_case and becomes the external event-name prefix.

### Accessibility
The frontend markup and runtime now include:

- Button semantics for the main toggle and QR-dialog triggers
- `aria-expanded`, `aria-controls`, and `aria-hidden` state management
- Modal `role="dialog"` and `aria-modal="true"` semantics
- Focus restoration to the trigger after closing a modal
- Escape-key and backdrop closing support
- Focus-visible outlines for the main interactive controls
- Reduced-motion handling both for `prefers-reduced-motion` users and when plugin animations are disabled

## Analytics Hooks
The plugin always dispatches internal browser events for bubble interactions. External analytics emission is opt-in.

### Internal Event Contract
Frontend JavaScript dispatches:

- `cwp-chat-bubbles:event`
- `cwp-chat-bubbles:open`
- `cwp-chat-bubbles:item_click`

The generic event uses `event.detail.name` and `event.detail.payload`. The scoped events expose the payload directly on `event.detail`.

Payload fields include:

- `component`
- `position`
- `trigger`
- `itemId`
- `itemPlatform`
- `itemLabel`
- `targetType`
- `hasQr`

### External Analytics Gate
- `Analytics enabled` must be checked before any external emission occurs.
- `Provider` can be `none`, `ga4`, or `gtm`.
- `Event prefix` is sanitized to lowercase snake_case and becomes the external event-name prefix.
- GA4 forwarding uses `window.gtag`.
- GTM forwarding uses `window.dataLayer.push`.
- If the selected provider is not present on the page, the plugin keeps dispatching internal events and simply skips the external bridge.

## Custom CSS
Use `Custom CSS` for small, focused overrides. Unsafe patterns such as `javascript:` URLs, `@import`, `expression()`, and legacy binding rules are stripped during save.

## Testing
Useful local verification commands:

```bash
php -l includes/class-settings.php
php -l includes/class-options-page.php
php -l includes/class-data-service.php
php -l includes/class-assets.php
php -l templates/chat-bubbles.php
npm run build
vendor/bin/phpunit
```

Current automated coverage includes:

- Settings normalization and sanitization for behavior, analytics, appearance, and page exclusions
- Data-service exposure of runtime settings such as behavior and analytics
- Items-manager helpers for future per-item behavior storage and fallback semantics
- Frontend template markup contracts for accessibility and analytics-related data attributes

## Release Checklist
- Confirm chat items still render and link correctly for both direct-link and QR-code flows.
- Verify focus movement and Escape-key handling on the frontend.
- Check reduced-motion behavior with both browser preference and plugin animation toggle.
- Review analytics settings on a site that actually loads GA4 or GTM before enabling external forwarding.
- Run `npm run build` and `vendor/bin/phpunit` before shipping.

## License
GPLv2 or later. See [LICENSE](LICENSE).

## Support
Open an issue or contact the maintainer for bugs, feature requests, or release questions.
