# Organic Glassmorphism - User Manual

Welcome to the Organic Glassmorphism plugin! This guide will walk you through all the features and settings to help you achieve a beautiful, modern design on your WordPress site.

## Table of Contents

1.  [Introduction](#introduction)
2.  [Automatic Styling](#automatic-styling)
3.  [Using the `.glass-card` Component](#using-the-glass-card-component)
4.  [Dark/Normal Mode Toggle](#darknormal-mode-toggle)
5.  [Customizing the Effect](#customizing-the-effect)
6.  [Frequently Asked Questions](#frequently-asked-questions)

---

### Introduction

The plugin automatically applies a warm, organic glassmorphism effect to the main content area of your theme. This provides an instant visual upgrade to your entire site. The effect is designed to be subtle and professional, enhancing your content without distracting from it.

### Using the `.glass-card` Component

The `.glass-card` class is a powerful tool for highlighting specific pieces of content, such as featured articles, calls to action, or product descriptions. When you hover over a glass card, it will subtly lift up, providing pleasant interactive feedback.

**How to use it with the WordPress Block Editor (Gutenberg):**

1.  Open any page or post in the WordPress editor.
2.  Select the block you want to highlight (e.g., a Group, a Paragraph, or a Columns block). For best results, use a **Group** block to contain multiple elements.
3.  In the block settings panel on the right-hand side, find the **Advanced** section and click to expand it if it's not already open.
4.  In the field labeled **Additional CSS class(es)**, type `glass-card`.
5.  Save or update the page.

That's it! The block will now be rendered as a beautiful, interactive glass card.

**Example:**

To style a heading and a paragraph together as a single card, you should first wrap them in a **Group** block, and then apply the `glass-card` class to the Group block itself.

```html
<!-- Before (in the editor) -->
<div class="wp-block-group">
    <h3>Our Mission</h3>
    <p>To bring beautiful, organic design to the web.</p>
</div>

<!-- After (add the class to the Group block) -->
<div class="wp-block-group glass-card">
    <h3>Our Mission</h3>
    <p>To bring beautiful, organic design to the web.</p>
</div>
```

### Dark/Normal Mode Toggle

In the bottom-right corner of every page on your site, you will find a toggle switch. This allows you and your visitors to instantly switch between the default "normal" (light) mode and a stunning dark mode.

The plugin automatically remembers each visitor's preference in their browser, so if they choose dark mode, it will remain active on their next visit.

### Customizing the Effect

You can fine-tune the appearance of the glass effect from the WordPress admin dashboard.

1.  Navigate to **Settings > Organic Glassmorphism**.
2.  You will see an option for **Glass Transparency**.
    -   **Value:** This is a number between `0.0` (completely transparent) and `1.0` (completely opaque).
    -   **Default:** `0.35`.
    -   Lowering the value (e.g., to `0.2`) will make the glass more see-through and subtle.
    -   Raising the value (e.g., to `0.6`) will make it more solid and pronounced.
3.  Click **Save Changes** to apply your adjustments across the entire site.

### Frequently Asked Questions

**Q: The glass effect isn't applying to the right part of my theme. What can I do?**

A: The plugin includes support for most popular themes out of the box. However, some themes use unique HTML structures. If the effect doesn't look right, please contact support or a developer. The plugin is built to be extensible, and a developer can easily add support for your theme using the `organic_glassmorphism_content_selectors` filter.

**Q: Can I change the colors?**

A: The current version of the plugin uses a professionally curated warm, organic color palette for both light and dark modes. The ability to customize colors from the admin panel is a planned future feature.

---

Thank you for using the Organic Glassmorphism plugin!
