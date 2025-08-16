# Organic Glassmorphism - Developer Guide

This guide provides technical details about the Organic Glassmorphism plugin, including its architecture, hooks, filters, and best practices. It is intended for WordPress developers who wish to customize, extend, or contribute to the plugin.

## Table of Contents

1.  [Plugin Architecture](#plugin-architecture)
2.  [Key Hooks and Filters](#key-hooks-and-filters)
    -   `organic_glassmorphism_content_selectors`
3.  [File Structure](#file-structure)
4.  [Code Standards](#code-standards)
5.  [Building and Contributing](#building-and-contributing)

---

### Plugin Architecture

The plugin is built using a modern, object-oriented (OOP) approach to ensure maintainability, scalability, and prevent conflicts with other plugins.

-   **Main Class (`OG_Plugin`)**: All functionality is encapsulated within a final singleton class, `OG_Plugin`, located in `/includes/class-og-plugin.php`. This class is instantiated only once to run the plugin.
-   **Loader File (`organic-glassmorphism.php`)**: The main plugin file in the root directory is kept lean. Its sole responsibilities are to define core constants (`ORGANIC_GLASSMORPHISM_VERSION`, `_PATH`, `_URL`) and to load and instantiate the `OG_Plugin` class.
-   **Constructor-Driven Hooks**: All WordPress actions and filters are registered within the `__construct()` method of the `OG_Plugin` class. This provides a single, centralized location to understand how the plugin interacts with the WordPress core.
-   **Settings API**: The admin settings panel is built using the standard WordPress Settings API for security and reliability. All sanitization is handled within the `options_sanitize` method.

### Key Hooks and Filters

The primary way to extend this plugin's functionality is through its filters.

#### `organic_glassmorphism_content_selectors`

This is the most important filter for achieving perfect theme compatibility. It allows you to modify the array of CSS selectors to which the main, site-wide glass effect is applied.

**Usage:**

If your theme's main content wrapper has an ID of `#my-custom-wrapper`, you can easily add support for it by adding the following code to your theme's `functions.php` file:

```php
add_filter( 'organic_glassmorphism_content_selectors', 'my_theme_add_glass_selector' );

function my_theme_add_glass_selector( $selectors ) {
    // Add your theme's unique selector to the end of the array.
    $selectors[] = '#my-custom-wrapper';

    // For complete control, you could also replace the array entirely.
    // return array( '#my-custom-wrapper', '.another-selector' );

    return $selectors;
}
```

This makes it possible to integrate the plugin's effects seamlessly into any theme structure.

### File Structure

The plugin's file structure is organized for clarity and follows WordPress best practices:

```
/organic-glassmorphism
|-- /assets
|   |-- /css
|   |   |-- style.css      (Static styles for cards, dark mode toggle, etc.)
|   |-- /js
|   |   |-- script.js      (Client-side logic for the dark mode toggle)
|-- /includes
|   |-- class-og-plugin.php (The main OOP plugin class)
|-- /languages
|   |-- organic-glassmorphism.pot (Translation template file, to be generated)
|-- organic-glassmorphism.php (Main plugin loader file)
|-- README.md
|-- USER_MANUAL.md
|-- DEVELOPER_GUIDE.md
```

### Code Standards

The plugin strictly adheres to the **WordPress Coding Standards (WPCS)**. All code is documented using PHPDoc blocks, and function/method names are prefixed or encapsulated to prevent conflicts.

### Building and Contributing

We welcome contributions to the plugin!

**To generate a `.pot` file for translations:**
You can use the `wp i18n make-pot` command with WP-CLI from within the plugin's root directory:
```bash
wp i18n make-pot . languages/organic-glassmorphism.pot
```

For more details on contributing, please see `CONTRIBUTING.md` (to be created).

---
