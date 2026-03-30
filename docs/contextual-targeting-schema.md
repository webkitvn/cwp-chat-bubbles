# Contextual Targeting Schema

This document defines the normalized option payload for the future contextual targeting builder.

## Settings Key

The schema lives under the top-level option key:

```php
$options['targeting']
```

## Shape

```php
array(
    'schema_version' => 1,
    'operator' => 'all', // 'all' or 'any'
    'rules' => array(
        'pages' => array(
            'include' => array(),
            'exclude' => array(),
        ),
        'post_types' => array(
            'include' => array(),
            'exclude' => array(),
        ),
        'special_pages' => array(
            'front_page' => 'ignore',
            'blog_index' => 'ignore',
            'search' => 'ignore',
            '404' => 'ignore',
            'archive' => 'ignore',
        ),
    ),
)
```

## Semantics

### Operator
- `all`: every populated rule bucket must match for the request to be eligible.
- `any`: any populated include-style bucket may match to make the request eligible, while explicit excludes should still win.

### Pages
- `pages.include` is a list of page IDs that explicitly allow display.
- `pages.exclude` is a list of page IDs that explicitly suppress display.

### Post Types
- `post_types.include` is a list of sanitized post-type slugs that explicitly allow display.
- `post_types.exclude` is a list of sanitized post-type slugs that explicitly suppress display.

### Special Pages
Each named special page uses a tri-state string:

- `ignore`
- `include`
- `exclude`

This avoids ambiguous booleans and makes future admin controls straightforward.

The initial named keys are:

- `front_page`
- `blog_index`
- `search`
- `404`
- `archive`

## Runtime Precedence

Runtime evaluation follows these rules:

1. Explicit excludes always win.
2. If no include buckets are populated, contextual targeting allows the request.
3. If include buckets exist:
   - `operator = any` means one populated include bucket must match.
   - `operator = all` means every populated include bucket must match.

The current runtime implementation treats `pages`, `post_types`, and `special_pages` as separate include buckets.

## Optional Plugin Safety

Special-page detection is guarded so optional plugin environments remain safe:

- Core contexts use standard WordPress conditionals such as `is_front_page()` and `is_archive()`.
- Optional environments can add matching conditionals without forcing those plugins to be active on every site.
- The current runtime path avoids calling optional conditionals unless the function exists.

## Extension Strategy

Future rule types can be added safely by introducing new buckets under `rules`, for example:

- `taxonomies`
- `user_roles`
- `languages`

Because the schema is bucketed and versioned, new rule families can be added without renaming the existing page, post-type, or special-page payloads.

## Backwards Compatibility

- Legacy quick-win display settings remain outside this schema until the richer targeting builder becomes authoritative.
- `docs/display-rules-migration.md` defines how `exclude_pages` and other legacy settings roll forward into this targeting model.
- `schema_version` exists so later migrations can branch safely if the option shape evolves.
