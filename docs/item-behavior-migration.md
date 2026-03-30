# Item Behavior Migration

This note defines the selected storage strategy for per-item behavior before the chat-item CRUD and admin modal are extended.

## Goal
- Keep the existing custom item table authoritative for item identity, ordering, and contact data.
- Add per-item behavior without forcing a destructive rewrite of current rows.
- Make the follow-on CRUD, admin UI, and frontend beads reuse one explicit schema and fallback story.

## Current Item Storage

The custom table currently stores:

- `id`
- `platform`
- `enabled`
- `label`
- `contact_value`
- `qr_code_id`
- `sort_order`

There is no per-item behavior payload yet. Current runtime behavior is implicit:

- If an item has a QR code, the frontend opens the QR modal first.
- If an item has no QR code, the frontend opens the outbound link directly.

## Selected Strategy

Add one nullable column to the existing custom table:

- Column: `behavior_settings`
- Type: `longtext`
- Encoding: PHP-serialized associative array

Reasons for this choice:

- It keeps each item self-contained instead of splitting identity and behavior across separate storage layers.
- `longtext` avoids rigid schema churn as behavior fields evolve.
- PHP-serialized arrays work on the same MySQL footprint the plugin already targets and are safely ignored by older plugin versions.
- Rollback is simple because old code can continue reading the original item columns and ignore the extra column.

The code contract for this strategy lives in `CWP_Chat_Bubbles_Items_Manager::get_item_behavior_storage_contract()`.

## Normalized Payload

The first schema version is:

```php
array(
    'schema_version' => 1,
    'interaction_mode' => 'auto', // 'auto', 'direct_link', 'qr_modal'
    'prefill_message' => '',
)
```

### Interaction Mode
- `auto`: preserve the current behavior; QR modal when QR exists, otherwise direct link.
- `direct_link`: bypass the QR modal even if a QR code exists.
- `qr_modal`: force the QR modal first when a QR code exists; if no QR code exists, fall back to direct link.

### Prefill Message
- Stores the operator-authored raw message string at the item level.
- Runtime integrations decide whether the current platform supports appending that message to the outbound URL.
- Empty string means no prefilled message.

## Migration Path

### Forward Migration
1. Bump the custom-table db version.
2. Add the nullable `behavior_settings` `longtext` column with `dbDelta`.
3. Do not backfill every existing row.
4. Treat `NULL`, empty strings, and malformed payloads as the normalized default behavior.
5. Update CRUD/UI to persist the normalized payload only when an item opts into non-default behavior.

### Existing Installs
- Existing items should continue behaving exactly as they do today after the column ships.
- An item should only gain explicit `behavior_settings` data after an admin edits and saves it through the new UI, or after future tooling writes the normalized payload intentionally.

## Fallback And Rollback

### Fallback
- Missing or invalid `behavior_settings` data resolves to the default payload.
- The default payload preserves the current QR-first-versus-link-direct behavior, so old installs do not need a one-time data rewrite.

### Rollback
- Older plugin versions ignore the added `behavior_settings` column and continue using the legacy item fields.
- The first rollout must not delete or repurpose any existing columns.
- Because the migration is additive, rollback does not require restoring a table backup for normal cases.

## Guardrails For Follow-On Work
- Reuse the normalization helper instead of reading the raw column directly.
- Keep per-item behavior separate from global widget behavior and business-hours schedule; those remain top-level settings unless a later schema change says otherwise.
- Avoid column-per-flag growth for new per-item behavior fields; extend the versioned payload instead.
