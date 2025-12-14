# simpleLMS - WordPress Plugin Architecture

> **IMPORTANT**: This document is the source of truth for the plugin architecture. It MUST be updated whenever there are any changes to the architecture, database schema, file structure, or functionality.

---

## Overview

A lightweight LMS plugin for WordPress/WooCommerce that integrates with WooCommerce Memberships and WooCommerce Subscriptions for access control.

---

## Core Features

1. **Custom Post Type**: `simple_lms_course` - standalone entry type for courses
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
│   ├── class-lms-meta-boxes.php      # Course meta fields (legacy, kept for compatibility)
│   ├── class-lms-access-control.php  # Membership/subscription checks
│   ├── class-lms-templates.php       # Template engine & placeholder replacement
│   ├── class-lms-shortcodes.php      # Shortcode rendering
│   └── class-lms-admin.php           # Admin pages, settings & course form handling
│
├── admin/
│   ├── views/
│   │   ├── courses-list.php          # Custom courses list page
│   │   ├── course-form.php           # Dedicated course add/edit form
│   │   ├── settings-page.php         # Settings page (tabbed)
│   │   ├── tab-general.php           # General settings tab
│   │   ├── tab-templates.php         # Templates tab
│   │   ├── tab-shortcodes.php        # Shortcode presets tab
│   │   └── tab-taxonomy.php          # Taxonomy management (categories, tags, statuses)
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js                  # Repeater fields, course actions JS
│
├── public/
│   ├── class-lms-public.php          # Frontend controller
│   ├── css/
│   │   └── public.css
│   └── js/
│       └── public.js
│
└── templates/
    └── single-simple_lms_course.php  # Default single course template
```

---

## Database Schema

### Storage Approach (WordPress Best Practice)

Uses `wp_options` table for plugin data (no custom tables). This is the WordPress standard approach - simpler, uses built-in caching, and easier to maintain.

| Option Key | Type | Description |
|------------|------|-------------|
| `simple_lms_settings` | array | General plugin settings |
| `simple_lms_default_template` | string | Default template HTML |
| `simple_lms_status_templates` | array | Status-specific templates (status_id => template HTML) |
| `simple_lms_shortcode_presets` | array | Shortcode presets (preset_name => settings) |

### WordPress Native Tables (used as normal)
- `wp_posts` - courses (post_type = 'simple_lms_course')
- `wp_postmeta` - course meta fields
- `wp_terms` / `wp_term_taxonomy` - categories, tags, statuses

---

## Custom Post Type: `simple_lms_course`

### Registration
- **Rewrite Slug**: `/course/` (URLs: `/course/course-title/`)
- **Public**: Yes
- **Has Archive**: No
- **Supports**: title, editor (WYSIWYG for additional content)

### Meta Fields (stored as post meta)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_simple_lms_date` | date | Course/training date |
| `_simple_lms_time_start` | string | Start time (e.g., "10:00") |
| `_simple_lms_time_end` | string | End time (e.g., "16:00") |
| `_simple_lms_duration` | string | Duration (e.g., "6h") - auto-calculated but editable |
| `_simple_lms_lecturer` | string | Lecturer/trainer name |
| `_simple_lms_videos` | array | Array of videos (see below) |
| `_simple_lms_materials` | array | Array of materials (see below) |
| `_simple_lms_access_memberships` | array | Allowed membership plan IDs |
| `_simple_lms_access_products` | array | Allowed subscription product IDs |
| `_simple_lms_redirect_url` | string | Custom redirect URL (default: global setting) |

### Video Structure (`_simple_lms_videos`)
```php
[
    ['title' => 'Część 1 - Wprowadzenie', 'vimeo_url' => 'https://vimeo.com/123456'],
    ['title' => 'Część 2 - Praktyka', 'vimeo_url' => 'https://vimeo.com/789012'],
]
```

### Materials Structure (`_simple_lms_materials`)
```php
[
    ['label' => 'Pobierz prezentację', 'url' => 'https://example.com/file1.pdf'],
    ['label' => 'Pobierz ćwiczenia', 'url' => 'https://example.com/file2.pdf'],
]
```

---

## Taxonomies

### 1. Course Categories (`simple_lms_category`)
- Hierarchical (like post categories)
- Future uses TBD by user

### 2. Course Tags (`simple_lms_tag`)
- Non-hierarchical
- Future uses TBD by user

### 3. Course Status (`simple_lms_status`)
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
Templates stored in `wp_options` table:
- `simple_lms_default_template` - default template
- `simple_lms_status_templates` - array of status_id => template

### Template Hierarchy
1. Status-specific template (if course has status with assigned template)
2. Default template (marked with `is_default = 1`)
3. Built-in fallback (hardcoded in plugin)

### Available Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{LMS_TITLE}}` | Course title |
| `{{LMS_DATE}}` | Formatted course date |
| `{{LMS_TIME}}` | Time range (e.g., "10:00 - 16:00") - combined from start/end |
| `{{LMS_DURATION}}` | Duration (e.g., "5 godzin") |
| `{{LMS_LECTURER}}` | Lecturer name |
| `{{LMS_VIDEOS}}` | All videos rendered (titles + embedded players) |
| `{{LMS_MATERIALS}}` | All materials rendered (clickable links) |
| `{{LMS_CATEGORY}}` | Primary category name |
| `{{LMS_TAGS}}` | Course tags (comma-separated) |
| `{{LMS_STATUS}}` | Course status |
| `{{LMS_CONTENT}}` | Post editor content (WYSIWYG) |

### Conditional Blocks

All placeholders have corresponding conditional blocks. Block only renders if data exists (not empty).

| Conditional Block | Renders if... |
|-------------------|---------------|
| `{{#IF_DATE}}...{{/IF_DATE}}` | Date is set |
| `{{#IF_TIME}}...{{/IF_TIME}}` | Time range is set |
| `{{#IF_DURATION}}...{{/IF_DURATION}}` | Duration is set |
| `{{#IF_LECTURER}}...{{/IF_LECTURER}}` | Lecturer field is not empty |
| `{{#IF_VIDEOS}}...{{/IF_VIDEOS}}` | At least one video exists |
| `{{#IF_MATERIALS}}...{{/IF_MATERIALS}}` | At least one material link exists |
| `{{#IF_CATEGORY}}...{{/IF_CATEGORY}}` | Course has a category |
| `{{#IF_TAGS}}...{{/IF_TAGS}}` | Course has at least one tag |
| `{{#IF_STATUS}}...{{/IF_STATUS}}` | Course has a status |
| `{{#IF_CONTENT}}...{{/IF_CONTENT}}` | Post editor has content |

**Example usage:**
```html
{{#IF_LECTURER}}
<p><strong>Wykładowca:</strong> {{LMS_LECTURER}}</p>
{{/IF_LECTURER}}

{{#IF_VIDEOS}}
<div class="video-section">
  <h2>Nagrania</h2>
  {{LMS_VIDEOS}}
</div>
{{/IF_VIDEOS}}

{{#IF_MATERIALS}}
<div class="materials-section">
  <h2>Materiały szkoleniowe</h2>
  {{LMS_MATERIALS}}
</div>
{{/IF_MATERIALS}}
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
| `tags` | array | all | Filter by course tags |
| `order` | string | DESC | ASC (oldest/A first) or DESC (newest/Z first) |
| `orderby` | string | date | `date` (course date), `title` (alphabetical), `menu_order` (manual) |
| `limit` | int | -1 | Number of courses to show (-1 = all) |
| `columns` | int | 3 | Grid columns: 1 (full), 2, 3, or 4 per row |

**Element Display & Order:**

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `elements` | array | (see below) | Ordered list of elements to display |

The `elements` array defines which elements are shown AND their order (drag-drop in admin):

```php
// Default element order
'elements' => [
    'title',       // Course title (linked)
    'status',      // Status badge
    'date',        // Course date
    'time',        // Time range
    'duration',    // Duration
    'lecturer',    // Lecturer name
    'category',    // Category name (hidden by default)
    'tags',        // Tags (hidden by default)
]
```

Admin UI shows a drag-drop list where you can:
- Reorder elements by dragging
- Toggle visibility with checkbox for each element

### Shortcode Output Structure
```html
<div class="lms-courses-grid lms-columns-3" data-preset="featured">
  <article class="lms-course-card">
    <h3 class="lms-course-title"><a href="...">Course Title</a></h3>
    <span class="lms-course-status">Nagranie</span>
    <div class="lms-course-meta">
      <span class="lms-meta-date">15.01.2025</span>
      <span class="lms-meta-time">10:00 - 16:00</span>
      <span class="lms-meta-duration">5 godzin</span>
      <span class="lms-meta-lecturer">Jan Kowalski</span>
    </div>
  </article>
  <!-- more courses... -->
</div>
```

---

## Admin Menu Structure

### Main Menu: "simpleLMS"

Plugin uses dedicated custom admin pages (not default WordPress CPT screens).

```
simpleLMS
├── Courses          → Custom courses list (admin.php?page=simple-lms)
├── Add New Course   → Dedicated course form (admin.php?page=simple-lms-add)
└── Settings         → Tabbed settings page (admin.php?page=simple-lms-settings)
    ├── Tab: General
    │   ├── Default redirect URL
    │   ├── Date format
    │   └── Default values (see below)
    ├── Tab: Templates
    │   ├── Default template editor
    │   └── Status-specific template editors
    ├── Tab: Shortcodes
    │   ├── List of presets
    │   └── Add/edit preset form
    ├── Tab: Categories
    │   └── Add/edit/delete course categories
    ├── Tab: Tags
    │   └── Add/edit/delete course tags
    └── Tab: Statuses
        └── Add/edit/delete course statuses
```

### Key Admin Features
- **Custom Courses List**: Dedicated table with search, filtering by status, pagination
- **Dedicated Course Form**: Two-column layout with all fields in one page (not WordPress meta boxes)
- **Taxonomy Management**: Integrated into Settings tabs (no separate WordPress taxonomy pages)
- **Menu Highlighting**: simpleLMS menu stays highlighted/expanded across all plugin pages

### Default Values (General Tab)

Configurable default values for new courses. All fields are simple text inputs (empty on fresh install).

| Setting | Example | Description |
|---------|---------|-------------|
| Default Material Label | `Pobierz` | Pre-filled label when adding new material |
| Default Video Title | `Nagranie` | Pre-filled title when adding new video |
| Default Lecturer | `Dawid Milczarek` | Pre-filled lecturer field |
| Default Time Range | `10:00 - 15:00` | Pre-filled time range (uses time picker) |
| Default Duration | `5h` | Pre-filled duration (auto-calculated from time range, but editable) |
| Default Status | `Nagranie` | Pre-selected status for new courses |

---

## Implementation Phases

### Phase 1: Foundation
1. Create main plugin file with activation/deactivation hooks
2. Set up wp_options defaults on activation
3. Register Custom Post Type `simple_lms_course` (URL: `/course/course-title/`)
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

## Course Add/Edit Form

The plugin uses a dedicated custom form for adding and editing courses (not the default WordPress editor).

### Form Layout (Two-Column)

```
┌────────────────────────────────────────────────────────┬─────────────────────┐
│ MAIN CONTENT                                           │ SIDEBAR             │
├────────────────────────────────────────────────────────┼─────────────────────┤
│ [_____________ Course Title (large input) ___________] │ ┌─────────────────┐ │
│                                                        │ │ Publish         │ │
│ ┌────────────────────────────────────────────────────┐ │ │ ○ Published     │ │
│ │ Course Details                                     │ │ │ ○ Draft         │ │
│ │ Date:      [📅 Date Picker]                        │ │ │ [Update Course] │ │
│ │ Time:      [🕐 Start] - [🕐 End]                   │ │ │ [View Course]   │ │
│ │ Duration:  [________]                              │ │ └─────────────────┘ │
│ │ Lecturer:  [____________________]                  │ │                     │
│ └────────────────────────────────────────────────────┘ │ ┌─────────────────┐ │
│                                                        │ │ Course Status   │ │
│ ┌────────────────────────────────────────────────────┐ │ │ ☐ Nagranie      │ │
│ │ Videos                               [+ Add Video] │ │ │ ☐ Zoom          │ │
│ │ ┌────────────────────────────────────────────────┐ │ │ │ ☐ Zaplanowane   │ │
│ │ │ Title: [___________]  Vimeo: [___________]     │ │ │ └─────────────────┘ │
│ │ └────────────────────────────────────────────────┘ │ │                     │
│ └────────────────────────────────────────────────────┘ │ ┌─────────────────┐ │
│                                                        │ │ Categories      │ │
│ ┌────────────────────────────────────────────────────┐ │ │ ☐ Category 1    │ │
│ │ Materials                         [+ Add Material] │ │ │ ☐ Category 2    │ │
│ │ ┌────────────────────────────────────────────────┐ │ │ └─────────────────┘ │
│ │ │ Label: [___________]  URL: [___________]       │ │ │                     │
│ │ └────────────────────────────────────────────────┘ │ │ ┌─────────────────┐ │
│ └────────────────────────────────────────────────────┘ │ │ Tags            │ │
│                                                        │ │ [comma-separated]│ │
│ ┌────────────────────────────────────────────────────┐ │ └─────────────────┘ │
│ │ Additional Content                                 │ │                     │
│ │ [═══════════ WYSIWYG Editor ═══════════]          │ │                     │
│ └────────────────────────────────────────────────────┘ │                     │
│                                                        │                     │
│ ┌────────────────────────────────────────────────────┐ │                     │
│ │ Access Control                                     │ │                     │
│ │ Memberships: ☐ Gold  ☐ Silver  ☐ Platinum         │ │                     │
│ │ Products:    ☐ Annual  ☐ Monthly                  │ │                     │
│ │ Redirect:    [________________]                    │ │                     │
│ └────────────────────────────────────────────────────┘ │                     │
└────────────────────────────────────────────────────────┴─────────────────────┘
```

### Form Sections
1. **Title** - Large input field at top
2. **Course Details** - Date, time range, duration, lecturer
3. **Videos** - Repeater field (drag to reorder, add/remove)
4. **Materials** - Repeater field (drag to reorder, add/remove)
5. **Additional Content** - WordPress WYSIWYG editor
6. **Access Control** - Membership/subscription checkboxes, redirect URL

### Sidebar Sections
1. **Publish** - Status selection (Published/Draft), Update/Create button, View link
2. **Course Status** - Taxonomy checkboxes
3. **Categories** - Taxonomy checkboxes
4. **Tags** - Comma-separated text input

---

## Courses List Page

Custom table view for managing courses.

### Features
- **Search**: Search courses by title
- **Filter**: Filter by course status taxonomy
- **Pagination**: Navigate through courses
- **Columns**: Title, Status, Course Date, Lecturer, Published status
- **Actions**: Edit, View, Delete (with AJAX confirmation)

---

> **REMINDER**: Keep this document updated! Any changes to the plugin architecture, database schema, file structure, or functionality must be reflected here.
