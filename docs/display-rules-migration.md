# Display Rules Migration

This note defines how the current advanced-settings layer rolls into the future unified contextual targeting model.

## Goal
- Keep the existing quick-win settings authoritative until the richer rule builder ships.
- Avoid a second disruptive rename or schema rewrite for installs that already saved advanced settings.
- Make follow-on contextual targeting work map current saved data into rule groups instead of inventing replacement fields.

## Current Saved Inputs

### Targeting-Relevant
- `device_visibility`
- `exclude_pages`
- `schedule`
- `behavior`

### Compatibility Alias
- `load_on_mobile`

`load_on_mobile` is not a separate source of truth anymore. It remains a compatibility alias for `device_visibility.mobile`.

### Explicitly Non-Targeting
- `appearance`
- `analytics`
- `custom_css`
- `main_button_color`
- `show_labels`
- `offset_x`
- `offset_y`
- `custom_main_icon`

These settings can affect rendering or reporting, but they should not be merged into the future contextual rule model.

## Unified Targeting Shape

The planned rule builder should treat current quick-win settings as seed data for these groups:

| Current field | Future group | Mode | Notes |
| --- | --- | --- | --- |
| `device_visibility` | `device_visibility` | `allow` | Preserve per-device booleans as the initial device rule set. |
| `exclude_pages` | `page` | `exclude` | Convert saved page IDs into a page exclusion rule group. |
| `schedule` | `schedule` | `allow_when_open` | Reuse the existing schedule payload as the first availability rule. |
| `behavior` | `engagement` | n/a | Keep engagement settings adjacent to targeting, but not inside rule matching. |

## Runtime Boundary

Before the future rule builder ships:

- `CWP_Chat_Bubbles_Settings::get_display_rules_migration_contract()` is the authoritative mapping contract.
- `CWP_Chat_Bubbles_Data_Service::get_display_rules_context()` exposes that contract together with runtime state such as device visibility and schedule availability.
- `should_load_on_current_page()` continues to enforce the current quick-win rules directly.

After the future rule builder ships:

- The new matcher should consume the mapped rule groups.
- The old fields should still be readable for backwards compatibility and rollback.
- The admin UI may present the richer rule builder, but it should import existing quick-win settings rather than resetting them.

## Migration And Rollback Strategy

### Forward Migration
1. Read the current quick-win settings through the migration contract helper.
2. Materialize equivalent unified rule groups in memory or in the new storage layer.
3. Preserve original quick-win fields during rollout for compatibility.
4. Only treat the unified rule storage as primary after parity has been verified.

### Rollback
1. If the new rule builder must be disabled, continue reading the original quick-win fields.
2. Do not delete `device_visibility`, `exclude_pages`, `schedule`, or `behavior` during the first rollout.
3. Keep `load_on_mobile` writable as an alias until legacy integrations no longer depend on it.

## Follow-On Expectations For E2
- Reuse the migration contract helper instead of hard-coding field names again.
- Keep engagement (`behavior`) separate from matching rules unless there is a deliberate schema change.
- Do not fold appearance or analytics into contextual targeting just because they live in the same advanced-settings screen.
