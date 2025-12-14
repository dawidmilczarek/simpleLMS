# simpleLMS - WordPress Plugin Architecture

> **IMPORTANT**: This document is the source of truth for the plugin architecture. It MUST be updated whenever there are any changes to the architecture, database schema, file structure, or functionality.

---

## Overview

A lightweight LMS plugin for WordPress/WooCommerce that integrates with WooCommerce Memberships and WooCommerce Subscriptions for access control.

---

## Core Features

1. **Custom Post Type**: `lms_course` - standalone entry type for courses
2. **Access Control**: Integration with WooCommerce Memberships & Subscriptions (OR logic)
3. **Template System**: Admin-editable templates with placeholders, assigned per status
4. **Shortcode System**: Configurable presets for course listings
5. **Taxonomies**: Course Categories, Course Tags, Course Statuses

---

## File Structure

```
simpleLMS/
├── simple-lms.php                    # Main plugin file
├── uninstall.php                     # Cleanup on uninstall
│
├── includes/
│   ├── class-simple-lms.php          # Main plugin class
│   ├── class-lms-post-types.php      # CPT & taxonomy registration
│   ├── class-lms-meta-boxes.php      # Course meta fields admin UI
│   ├── class-lms-access-control.php  # Membership/subscription checks
│   ├── class-lms-templates.php       # Template engine & placeholder replacement
│   ├── class-lms-shortcodes.php      # Shortcode rendering
│   ├── class-lms-admin.php           # Admin pages & settings
│   └── class-lms-database.php        # Database table management
│
├── admin/
│   ├── views/
│   │   ├── settings-page.php         # Settings page (tabbed)
│   │   ├── tab-general.php           # General settings tab
│   │   ├── tab-templates.php         # Templates tab
│   │   ├── tab-shortcodes.php        # Shortcode presets tab
│   │   └── meta-box-course.php       # Course edit meta box
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js                  # Repeater fields JS
│
├── public/
│   ├── class-lms-public.php          # Frontend controller
│   ├── css/
│   │   └── public.css
│   └── js/
│       └── public.js
│
└── templates/
    └── single-lms_course.php         # Default single course template
```

---

## Database Schema

### Custom Tables (created on plugin activation)

#### 1. `{prefix}simple_lms_templates`
Stores course templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(255) | Template name (for admin reference) |
| `status_id` | BIGINT UNSIGNED | Course status term_id (NULL = default template) |
| `content` | LONGTEXT | Template HTML with placeholders |
| `is_default` | TINYINT(1) | 1 = default template, 0 = status-specific |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

#### 2. `{prefix}simple_lms_shortcode_presets`
Stores shortcode preset configurations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(100) | Preset slug (used in shortcode) |
| `label` | VARCHAR(255) | Human-readable name |
| `settings` | LONGTEXT | JSON-encoded settings |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

#### 3. `{prefix}simple_lms_settings`
Stores plugin settings.

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `option_name` | VARCHAR(100) | Setting key |
| `option_value` | LONGTEXT | Setting value (JSON for complex data) |

### WordPress Native Tables (used as normal)
- `wp_posts` - courses (post_type = 'lms_course')
- `wp_postmeta` - course meta fields
- `wp_terms` / `wp_term_taxonomy` - categories, tags, statuses

---

## Custom Post Type: `lms_course`

### Registration
- **Rewrite Slug**: `/course/` (URLs: `/course/course-title/`)
- **Public**: Yes
- **Has Archive**: No
- **Supports**: title, editor (WYSIWYG for additional content), thumbnail

### Meta Fields (stored as post meta)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_lms_date` | date | Course/training date |
| `_lms_time_range` | string | Time range (e.g., "10:00 - 16:00") |
| `_lms_duration` | string | Duration (e.g., "5 godzin") |
| `_lms_lecturer` | string | Lecturer/trainer name |
| `_lms_videos` | array | Array of videos (see below) |
| `_lms_materials` | array | Array of materials (see below) |
| `_lms_access_memberships` | array | Allowed membership plan IDs |
| `_lms_access_products` | array | Allowed subscription product IDs |
| `_lms_redirect_url` | string | Custom redirect URL (default: global setting) |

### Video Structure (`_lms_videos`)
```php
[
    ['title' => 'Część 1 - Wprowadzenie', 'vimeo_url' => 'https://vimeo.com/123456'],
    ['title' => 'Część 2 - Praktyka', 'vimeo_url' => 'https://vimeo.com/789012'],
]
```

### Materials Structure (`_lms_materials`)
```php
[
    ['label' => 'Pobierz prezentację', 'url' => 'https://example.com/file1.pdf'],
    ['label' => 'Pobierz ćwiczenia', 'url' => 'https://example.com/file2.pdf'],
]
```

---

## Taxonomies

### 1. Course Categories (`lms_course_category`)
- Hierarchical (like post categories)
- Future uses TBD by user

### 2. Course Tags (`lms_course_tag`)
- Non-hierarchical
- Future uses TBD by user

### 3. Course Status (`lms_course_status`)
- Non-hierarchical
- Admin can create unlimited statuses
- Examples: "Nagranie", "Zoom", "Zaplanowane"
- **Used for template assignment** (each status can have its own template)
- Used for shortcode filtering

---

## Access Control Logic

```
User has access IF:
  - User has ANY of the specified memberships (active)
  OR
  - User has active subscription to ANY of the specified products
  OR
  - No access rules defined (public course)
  OR
  - User is administrator
```

### Integration Points

**WooCommerce Memberships:**
```php
wc_memberships_is_user_active_member($user_id, $plan_id)
```

**WooCommerce Subscriptions:**
```php
wcs_user_has_subscription($user_id, $product_id, 'active')
```

### Access Denied Behavior
1. Check if user is logged in
2. If not logged in → redirect to login, then back to course
3. If logged in but no access → redirect to configured URL (default: `/sklep/`)

---

## Template System

### Template Storage
Templates stored in `{prefix}simple_lms_templates` table.

### Template Hierarchy
1. Status-specific template (if course has status with assigned template)
2. Default template (marked with `is_default = 1`)
3. Built-in fallback (hardcoded in plugin)

### Available Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{LMS_TITLE}}` | Course title |
| `{{LMS_DATE}}` | Formatted course date |
| `{{LMS_TIME}}` | Time range (e.g., "10:00 - 16:00") |
| `{{LMS_DURATION}}` | Duration (e.g., "5 godzin") |
| `{{LMS_LECTURER}}` | Lecturer name |
| `{{LMS_VIDEOS}}` | All videos rendered (titles + embedded players) |
| `{{LMS_MATERIALS}}` | All materials rendered (clickable links) |
| `{{LMS_CATEGORY}}` | Primary category name |
| `{{LMS_STATUS}}` | Course status |
| `{{LMS_CONTENT}}` | Post editor content (WYSIWYG) |

### Conditional Blocks

```html
{{#IF_MATERIALS}}
<h2>Materiały szkoleniowe</h2>
{{LMS_MATERIALS}}
{{/IF_MATERIALS}}

{{#IF_VIDEOS}}
<div class="video-section">
{{LMS_VIDEOS}}
</div>
{{/IF_VIDEOS}}

{{#IF_CONTENT}}
<div class="course-content">{{LMS_CONTENT}}</div>
{{/IF_CONTENT}}
```

### Rendered Output Examples

**{{LMS_VIDEOS}}** renders as:
```html
<div class="lms-video-item">
  <h3>Część 1 - Wprowadzenie</h3>
  <div class="lms-video-embed">
    <iframe src="https://player.vimeo.com/video/123456" ...></iframe>
  </div>
</div>
<div class="lms-video-item">
  <h3>Część 2 - Praktyka</h3>
  <div class="lms-video-embed">
    <iframe src="https://player.vimeo.com/video/789012" ...></iframe>
  </div>
</div>
```

**{{LMS_MATERIALS}}** renders as:
```html
<ul class="lms-materials-list">
  <li><a href="https://example.com/file1.pdf" target="_blank">Pobierz prezentację</a></li>
  <li><a href="https://example.com/file2.pdf" target="_blank">Pobierz ćwiczenia</a></li>
</ul>
```

### Admin UI for Templates
- Default template editor (always exists)
- Status-specific template editor (one per status, optional)
- WYSIWYG editor with placeholder insertion buttons
- Live preview with sample data

---

## Shortcode System

### Usage
```
[lms_courses preset="featured"]
[lms_courses preset="recent-webinars"]
[lms_courses preset="all"]
```

### Preset Configuration (Admin UI)

Each preset defines:

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `name` | string | required | Preset slug (used in shortcode) |
| `label` | string | required | Human-readable name for admin |
| `statuses` | array | all | Filter by course statuses |
| `categories` | array | all | Filter by categories |
| `order` | string | DESC | ASC or DESC |
| `orderby` | string | date | date, title, menu_order |
| `limit` | int | -1 | Number of courses (-1 = all) |
| `show_date` | bool | true | Display date |
| `show_time` | bool | true | Display time range |
| `show_duration` | bool | true | Display duration |
| `show_lecturer` | bool | true | Display lecturer |
| `show_status` | bool | true | Display status badge |
| `show_category` | bool | false | Display category |
| `show_thumbnail` | bool | true | Display featured image |
| `columns` | int | 3 | Grid columns (1-4) |

### Shortcode Output Structure
```html
<div class="lms-courses-grid lms-columns-3" data-preset="featured">
  <article class="lms-course-card">
    <div class="lms-course-thumbnail">
      <a href="..."><img src="..." alt="..."></a>
      <span class="lms-course-status">Nagranie</span>
    </div>
    <div class="lms-course-content">
      <h3 class="lms-course-title"><a href="...">Course Title</a></h3>
      <div class="lms-course-meta">
        <span class="lms-meta-date">15.01.2025</span>
        <span class="lms-meta-time">10:00 - 16:00</span>
        <span class="lms-meta-duration">5 godzin</span>
        <span class="lms-meta-lecturer">Jan Kowalski</span>
      </div>
    </div>
  </article>
  <!-- more courses... -->
</div>
```

---

## Admin Menu Structure

### Main Menu: "simpleLMS"

```
simpleLMS
├── Courses          → CPT list (edit.php?post_type=lms_course)
├── Add New Course   → New course (post-new.php?post_type=lms_course)
└── Settings         → Tabbed settings page
    ├── Tab: General
    │   ├── Default redirect URL
    │   ├── Date format
    │   └── Other options
    ├── Tab: Templates
    │   ├── Default template editor
    │   └── Status-specific template editors
    └── Tab: Shortcodes
        ├── List of presets
        └── Add/edit preset form
```

### Taxonomy Pages (under Courses submenu or separate)
- Categories (lms_course_category)
- Tags (lms_course_tag)
- Statuses (lms_course_status)

---

## Implementation Phases

### Phase 1: Foundation
1. Create main plugin file with activation/deactivation hooks
2. Create database class with table creation on activation
3. Register Custom Post Type `lms_course` (URL: `/course/course-title/`)
4. Register taxonomies (categories, tags, statuses)
5. Create meta boxes for course fields (with repeater for videos/materials)
6. Set up admin menu structure
7. Basic admin styling + repeater JS

### Phase 2: Access Control
1. Create access control class
2. Integrate with WooCommerce Memberships API
3. Integrate with WooCommerce Subscriptions API
4. Add membership/product selectors to course edit screen
5. Implement redirect logic for unauthorized access

### Phase 3: Template System
1. Create template engine class
2. Implement placeholder replacement
3. Implement conditional blocks
4. Create Templates tab in settings with WYSIWYG editor
5. Add status-specific template assignment UI
6. Override single course display with template

### Phase 4: Shortcode System
1. Create shortcode class
2. Build Shortcodes tab in settings
3. Implement course query based on preset settings
4. Create frontend rendering with grid layout
5. Add basic CSS for course cards

---

## Character Encoding

Plugin uses UTF-8 encoding throughout for full Polish character support (ą, ć, ę, ł, ń, ó, ś, ź, ż).

Database tables use `utf8mb4` character set.

---

## Security Considerations

1. **Nonce verification** on all form submissions
2. **Capability checks** for admin pages (`manage_options`)
3. **Sanitization** of all inputs (esc_html, esc_url, wp_kses)
4. **Prepared statements** for all custom table queries
5. **Access control** hooks on `template_redirect` and `the_content`

---

## Dependencies

**Required:**
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+

**Optional (for access control):**
- WooCommerce Memberships 1.0+
- WooCommerce Subscriptions 3.0+

Plugin will work without membership/subscription plugins but access control features will be disabled with admin notice.

---

## Hooks & Filters (Extensibility)

### Filters
- `lms_placeholders` - modify available placeholders
- `lms_template_output` - filter final template HTML
- `lms_course_query_args` - modify shortcode query
- `lms_user_has_access` - override access check
- `lms_redirect_url` - filter redirect URL
- `lms_video_embed_html` - filter video embed output
- `lms_materials_html` - filter materials list output

### Actions
- `lms_before_course_content` - before course template
- `lms_after_course_content` - after course template
- `lms_access_denied` - when access is denied (before redirect)

---

## Sample Default Template

```html
<div class="lms-course-single">
  <div class="lms-course-header">
    <p>
      <strong>Wykładowca:</strong> {{LMS_LECTURER}}<br>
      <strong>Data szkolenia:</strong> {{LMS_DATE}}<br>
      <strong>Godziny:</strong> {{LMS_TIME}}<br>
      <strong>Czas trwania:</strong> {{LMS_DURATION}}
    </p>
  </div>

  {{#IF_VIDEOS}}
  <div class="lms-video-section">
    <h2>Nagrania</h2>
    {{LMS_VIDEOS}}
  </div>
  {{/IF_VIDEOS}}

  {{#IF_CONTENT}}
  <div class="lms-content-section">
    {{LMS_CONTENT}}
  </div>
  {{/IF_CONTENT}}

  {{#IF_MATERIALS}}
  <div class="lms-materials-section">
    <h2>Materiały szkoleniowe</h2>
    {{LMS_MATERIALS}}
  </div>
  {{/IF_MATERIALS}}
</div>
```

---

## Course Edit Screen Meta Box

### Fields Layout

```
┌─────────────────────────────────────────────────────────────┐
│ Course Details                                              │
├─────────────────────────────────────────────────────────────┤
│ Date:        [____/____/________]                          │
│ Time Range:  [__________] (e.g., 10:00 - 16:00)            │
│ Duration:    [__________] (e.g., 5 godzin)                 │
│ Lecturer:    [____________________________]                 │
├─────────────────────────────────────────────────────────────┤
│ Videos                                          [+ Add Video]│
│ ┌─────────────────────────────────────────────────────────┐│
│ │ Title:     [________________________]      [Remove]     ││
│ │ Vimeo URL: [________________________]                   ││
│ └─────────────────────────────────────────────────────────┘│
│ ┌─────────────────────────────────────────────────────────┐│
│ │ Title:     [________________________]      [Remove]     ││
│ │ Vimeo URL: [________________________]                   ││
│ └─────────────────────────────────────────────────────────┘│
├─────────────────────────────────────────────────────────────┤
│ Materials                                    [+ Add Material]│
│ ┌─────────────────────────────────────────────────────────┐│
│ │ Label:     [________________________]      [Remove]     ││
│ │ URL:       [________________________]                   ││
│ └─────────────────────────────────────────────────────────┘│
├─────────────────────────────────────────────────────────────┤
│ Access Control                                              │
│ Memberships: [x] Gold  [ ] Silver  [x] Platinum            │
│ Products:    [x] Annual Sub  [ ] Monthly Sub               │
│ Redirect URL: [________________] (leave empty for default)  │
└─────────────────────────────────────────────────────────────┘
```

---

> **REMINDER**: Keep this document updated! Any changes to the plugin architecture, database schema, file structure, or functionality must be reflected here.
