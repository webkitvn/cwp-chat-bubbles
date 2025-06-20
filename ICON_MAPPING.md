# CWP Chat Bubbles - Icon Mapping Structure

## Centralized Icon Management System

The plugin now uses a centralized constant mapping structure for platform icons, ensuring consistency across all components.

### Icon Mapping Configuration

Located in: `includes/class-items-manager.php`

```php
const PLATFORM_ICON_MAP = array(
    // Core supported platforms
    'phone'     => 'hotline.svg',
    'zalo'      => 'zalo.svg',
    'whatsapp'  => 'whatsapp.svg',
    'viber'     => 'viber.svg',
    'telegram'  => 'telegram.svg',
    'messenger' => 'messenger.svg',
    'line'      => 'line.svg',
    'kakaotalk' => 'kakaotalk.svg',
    
    // Additional icons available for future expansion
    'facebook'  => 'facebook.svg',
    'instagram' => 'instagram.svg',
    'youtube'   => 'youtube.svg',
    'tiktok'    => 'tiktok.svg',
    'wechat'    => 'wechat.svg'
);
```

### Icon Directory Structure

```
assets/images/socials/
├── hotline.svg      (used by 'phone' platform)
├── zalo.svg         (used by 'zalo' platform)
├── whatsapp.svg     (used by 'whatsapp' platform)
├── viber.svg        (used by 'viber' platform)
├── telegram.svg     (used by 'telegram' platform)
├── messenger.svg    (used by 'messenger' platform)
├── line.svg         (used by 'line' platform)
├── kakaotalk.svg    (used by 'kakaotalk' platform)
├── facebook.svg     (available for future use)
├── instagram.svg    (available for future use)
├── youtube.svg      (available for future use)
├── tiktok.svg       (available for future use)
└── wechat.svg       (available for future use)
```

### Centralized Icon Methods

#### Items Manager (Central Authority)
```php
// Get single platform icon URL
$icon_url = $items_manager->get_platform_icon_url('phone');
// Returns: [plugin_url]/assets/images/socials/hotline.svg

// Get all available platform icons
$all_icons = $items_manager->get_all_platform_icons();
// Returns: array of platform => icon_url pairs
```

#### Frontend Class
```php
private function get_platform_icon_url($platform) {
    return CWP_Chat_Bubbles_Items_Manager::get_instance()->get_platform_icon_url($platform);
}
```

#### Assets Class
```php
private function get_platform_icon_url($platform) {
    return CWP_Chat_Bubbles_Items_Manager::get_instance()->get_platform_icon_url($platform);
}
```

#### Options Page Class
```php
private function get_platform_icon_url($platform) {
    return $this->items_manager->get_platform_icon_url($platform);
}
```

## Benefits of Centralized Icon Mapping

### 1. **Single Source of Truth**
- All platform-to-icon mappings defined in one location
- No duplication of mapping logic across classes
- Easy to maintain and update

### 2. **Consistency Guarantee**
- All classes use the same icon mapping
- No risk of inconsistent icon display
- Unified behavior across admin and frontend

### 3. **Easy Expansion**
- Adding new platforms requires updating only the constant
- New icons can be added to the map without code changes
- Future-proof for additional social platforms

### 4. **Developer Friendly**
- Clear documentation of available icons
- Simple API for getting icon URLs
- Easy to debug icon-related issues

## Usage Examples

### Adding a New Platform with Icon

1. **Add icon file**: Place `newplatform.svg` in `assets/images/socials/`

2. **Update constant**: Add to `PLATFORM_ICON_MAP`
   ```php
   'newplatform' => 'newplatform.svg'
   ```

3. **Add platform config**: Add to `$supported_platforms`
   ```php
   'newplatform' => array(
       'label' => 'New Platform',
       'contact_field' => 'username',
       'pattern' => '/^[a-zA-Z0-9_]{1,50}$/',
       'placeholder' => 'username'
   )
   ```

### Custom Icon Mapping

If a platform needs a different icon filename:
```php
'custom_platform' => 'special_icon.svg'
```

### Fallback Behavior

If no mapping is found, the system defaults to `{platform}.svg`:
```php
// For 'unknown' platform without mapping
// Will try to load: unknown.svg
$icon_filename = isset(self::PLATFORM_ICON_MAP[$platform]) 
    ? self::PLATFORM_ICON_MAP[$platform] 
    : $platform . '.svg';
```

## Icon File Requirements

- **Format**: SVG (Scalable Vector Graphics)
- **Optimization**: Icons should be optimized for web use
- **Size**: Consistent dimensions (recommended: 32x32 or 64x64)
- **Style**: Consistent visual style across all icons
- **Naming**: Lowercase, descriptive filenames

## Maintenance Notes

- Always update the constant when adding new platform support
- Ensure icon files exist before adding to the mapping
- Test icon display in both admin and frontend after changes
- Keep documentation updated with new platform additions 