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
5. **Taxonomies**: Course Categories, Course Tags, Course Statuses, Lecturers

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
│   ├── class-lms-certificates.php    # Certificate generation
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
│   │   ├── tab-taxonomy.php          # Taxonomy management (categories, tags, statuses, lecturers)
│   │   ├── tab-certificates.php      # Certificate settings tab
│   │   └── certificates-generate.php # Manual certificate generation
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
├── tcpdf/                            # TCPDF library for PDF generation
│
└── templates/
    ├── single-simple_lms_course.php  # Default single course template
    └── certificate-template.html     # Default certificate template
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
| `simple_lms_template_labels` | array | Customizable labels for default template (date, time, lecturer) |
| `simple_lms_certificate_labels` | array | Customizable labels for certificate frontend elements |

### WordPress Native Tables (used as normal)
- `wp_posts` - courses (post_type = 'simple_lms_course')
- `wp_postmeta` - course meta fields
- `wp_terms` / `wp_term_taxonomy` - categories, tags, statuses, lecturers
- `wp_usermeta` - user preferences (e.g., `simple_lms_courses_per_page` for Screen Options)

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
| `_simple_lms_videos` | array | Array of videos (see below) |
| `_simple_lms_materials` | array | Array of materials (see below) |
| `_simple_lms_access_memberships` | array | Allowed membership plan IDs |
| `_simple_lms_access_products` | array | Allowed subscription product IDs |
| `_simple_lms_redirect_url` | string | Custom redirect URL - relative path or full URL (default: global setting) |

> **Note**: Lecturer is now stored as a taxonomy (`simple_lms_lecturer`), not as post meta.

### Video Structure (`_simple_lms_videos`)
```php
[
    ['title' => 'Part 1 - Introduction', 'vimeo_url' => 'https://vimeo.com/123456'],
    ['title' => 'Part 2 - Practice', 'vimeo_url' => 'https://vimeo.com/789012'],
]
```

### Materials Structure (`_simple_lms_materials`)
```php
[
    ['label' => 'Download presentation', 'url' => 'https://example.com/file1.pdf'],
    ['label' => 'Download exercises', 'url' => 'https://example.com/file2.pdf'],
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
- Examples: "Recording", "Live", "Scheduled"
- **Used for template assignment** (each status can have its own template)
- Used for shortcode filtering

### 4. Lecturers (`simple_lms_lecturer`)
- Non-hierarchical
- Admin can create unlimited lecturers
- Used for filtering in courses list
- Displayed on course pages via `{{LMS_LECTURER}}` placeholder

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
1. Check if user has access (via membership or subscription)
2. If no access → redirect to configured URL (default: `/`, configurable in Settings → General)
3. Per-course override available in course edit form (Redirect URL field)

**Redirect URL Format:**
- Relative path (e.g., `/`, `/pricing/`) - will be prepended with site URL
- Full external URL (e.g., `https://example.com`) - used as-is

---

## Template System

### Template Storage
Templates stored in `wp_options` table:
- `simple_lms_default_template` - default template
- `simple_lms_status_templates` - array of status_id => template
- `simple_lms_template_labels` - customizable labels for default template

### Template Labels (`simple_lms_template_labels`)

These labels are used in the built-in default template (displayed when resetting to default):

| Key | Default | Description |
|-----|---------|-------------|
| `date` | "Date:" | Label before date value |
| `time` | "Time:" | Label before time value |
| `lecturer` | "Lecturer:" | Label before lecturer value |

### Template Hierarchy
1. Status-specific template (if course has status with assigned template)
2. Default template (stored in `simple_lms_default_template` option)
3. Built-in fallback (hardcoded in plugin via `LMS_Admin::get_builtin_default_template()`)

### Technical Note: Recursion Prevention
The `{{LMS_CONTENT}}` placeholder uses `apply_filters('the_content', ...)` internally. To prevent infinite recursion (since template rendering hooks into `the_content`), a static `$is_rendering` flag is used in `render_course_content()`.

### Available Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{LMS_TITLE}}` | Course title |
| `{{LMS_DATE}}` | Formatted course date |
| `{{LMS_TIME}}` | Time range (e.g., "10:00 - 16:00") - combined from start/end |
| `{{LMS_DURATION}}` | Duration (e.g., "5 hours") |
| `{{LMS_LECTURER}}` | Lecturer name |
| `{{LMS_VIDEOS}}` | All videos rendered (titles + embedded players) |
| `{{LMS_MATERIALS}}` | All materials rendered (clickable links) |
| `{{LMS_CATEGORY}}` | Primary category name |
| `{{LMS_TAGS}}` | Course tags (comma-separated) |
| `{{LMS_STATUS}}` | Course status |
| `{{LMS_CONTENT}}` | Post editor content (WYSIWYG) |
| `{{LMS_CERTIFICATE}}` | Certificate generation form |

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
| `{{#IF_CERTIFICATE}}...{{/IF_CERTIFICATE}}` | Certificate is available for course and user |

**Example usage:**
```html
{{#IF_LECTURER}}
<p><strong>Lecturer:</strong> {{LMS_LECTURER}}</p>
{{/IF_LECTURER}}

{{#IF_VIDEOS}}
<div class="video-section">
  <h2>Recordings</h2>
  {{LMS_VIDEOS}}
</div>
{{/IF_VIDEOS}}

{{#IF_MATERIALS}}
<div class="materials-section">
  <h2>Course Materials</h2>
  {{LMS_MATERIALS}}
</div>
{{/IF_MATERIALS}}
```

### Rendered Output Examples

**{{LMS_VIDEOS}}** renders as:
```html
<div class="lms-video-item">
  <h3>Part 1 - Introduction</h3>
  <div class="lms-video-embed">
    <iframe src="https://player.vimeo.com/video/123456" ...></iframe>
  </div>
</div>
<div class="lms-video-item">
  <h3>Part 2 - Practice</h3>
  <div class="lms-video-embed">
    <iframe src="https://player.vimeo.com/video/789012" ...></iframe>
  </div>
</div>
```

**{{LMS_MATERIALS}}** renders as:
```html
<ul class="lms-materials-list">
  <li><a href="https://example.com/file1.pdf" target="_blank">Download presentation</a></li>
  <li><a href="https://example.com/file2.pdf" target="_blank">Download exercises</a></li>
</ul>
```

### Admin UI for Templates
- Default template editor (always exists)
- Status-specific template editor (one per status, optional)
- Code editor (CodeMirror) with syntax highlighting
- **Reset to Default** button to restore built-in template

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
| `display_mode` | string | list | Display mode: `list` (comma-separated) or `grid` |
| `columns` | int | 3 | Grid columns: 1 (full), 2, 3, or 4 per row (only for grid mode) |

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

**List Mode (default):**
```html
<ul class="lms-courses-list" data-preset="all">
  <li class="lms-course-item">
    <a href="..." class="lms-course-link">Course Title</a>,
    <span class="lms-meta-status">Recording</span>,
    <span class="lms-meta-date">15.01.2025</span>
  </li>
  <!-- more courses... -->
</ul>
```

**Grid Mode:**
```html
<div class="lms-courses-grid lms-columns-3" data-preset="featured">
  <article class="lms-course-card">
    <h3 class="lms-course-title"><a href="...">Course Title</a></h3>
    <span class="lms-course-status">Recording</span>
    <div class="lms-course-meta">
      <span class="lms-meta-date">15.01.2025</span>
      <span class="lms-meta-time">10:00 - 16:00</span>
      <span class="lms-meta-duration">5 hours</span>
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
    │   ├── Redirect URL (for users without access, default: '/')
    │   ├── Date format
    │   ├── Product status filter (published only or all including drafts/trash)
    │   └── Default values (time range, duration, video title, material label)
    ├── Tab: Templates
    │   ├── Default template editor
    │   └── Status-specific template editors
    ├── Tab: Shortcodes
    │   ├── List of presets
    │   └── Add/edit preset form
    ├── Tab: Categories
    │   ├── Default category setting
    │   ├── Add form
    │   └── List of categories
    ├── Tab: Tags
    │   ├── Add form
    │   └── List of tags
    ├── Tab: Statuses
    │   ├── Default status setting
    │   ├── Add form
    │   └── List of statuses
    └── Tab: Lecturers
        ├── Default lecturer setting
        ├── Add form
        └── List of lecturers
```

### Key Admin Features
- **Custom Courses List**: Dedicated table with search, filtering by status, pagination
- **Dedicated Course Form**: Two-column layout with all fields in one page (not WordPress meta boxes)
- **Taxonomy Management**: Integrated into Settings tabs (no separate WordPress taxonomy pages)
- **Menu Highlighting**: simpleLMS menu stays highlighted/expanded across all plugin pages
- **Admin Bar Menu**: Quick access from WordPress admin bar

### Admin Bar Menu

Available from any page when logged in as admin:

```
LMS (in admin bar)
├── Add New Course    → admin.php?page=simple-lms-add
├── All Courses       → admin.php?page=simple-lms
└── Edit This Course  → (only on single course page) edit current course
```

### Default Values (General Tab)

Configurable default values for new courses. All fields are simple text inputs (empty on fresh install).

| Setting | Example | Description |
|---------|---------|-------------|
| Default Material Label | `Download` | Pre-filled label when adding new material |
| Default Video Title | `Recording` | Pre-filled title when adding new video |
| Default Lecturer | `Dawid Milczarek` | Pre-filled lecturer field |
| Default Time Range | `10:00 - 15:00` | Pre-filled time range (uses time picker) |
| Default Duration | `5h` | Pre-filled duration (auto-calculated from time range, but editable) |
| Default Status | `Recording` | Pre-selected status for new courses |

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

## Frontend Styling

### Design Philosophy
Plugin provides **minimal structural CSS only** - no decorative styles. This allows themes to fully control the appearance.

### Public CSS (`public/css/public.css`)
Only essential structural styles:

| Element | Styles |
|---------|--------|
| `.lms-video-embed` | Responsive 16:9 iframe container |
| `.lms-materials-list` | List style reset (no bullets) |
| `.lms-courses-list` | List style reset for course list mode |
| `.lms-course-item` | Basic margin for list items |
| `.lms-courses-grid` | CSS Grid layout (grid mode) |
| `.lms-columns-*` | Grid column definitions (1-4) |
| Responsive breakpoints | Grid adjustments for mobile |

**No decorative styles** (backgrounds, colors, borders, shadows, etc.) - all controlled by theme.

### Single Course Template
The `templates/single-simple_lms_course.php` provides basic structure:
- Uses theme's `get_header()` and `get_footer()`
- No sidebar (`get_sidebar()` removed)
- Course title in `<h1>`
- Content rendered via template system

---

## Character Encoding

Plugin uses UTF-8 encoding throughout for full Polish character support (ą, ć, ę, ł, ń, ó, ś, ź, ż).

Database tables use `utf8mb4` character set.

---

## Internationalization (i18n)

### Language Policy

**All hardcoded strings in the codebase MUST be in English.**

- Use `__()` or `esc_html__()` for translatable strings
- Text domain: `simple-lms`
- Polish translations should be provided via `.po`/`.mo` files (not hardcoded)

**Example:**
```php
// CORRECT - English source string
esc_html__( 'Download certificate', 'simple-lms' )

// WRONG - Polish hardcoded
esc_html__( 'Pobierz certyfikat', 'simple-lms' )
```

---

## Security Considerations

1. **Nonce verification** on all form submissions
2. **Capability checks** for admin pages (`manage_options`)
3. **Sanitization** of all inputs (esc_html, esc_url, wp_kses)
4. **Prepared statements** for all custom table queries
5. **Access control** hooks on `template_redirect` and `the_content`

---

## Uninstall Process

When the plugin is **deleted** (not just deactivated), `uninstall.php` runs and cleans up all plugin data:

### What Gets Deleted

| Data Type | Storage | Cleanup Method |
|-----------|---------|----------------|
| Plugin settings | `wp_options` | `delete_option()` |
| Default template | `wp_options` | `delete_option()` |
| Status templates | `wp_options` | `delete_option()` |
| Shortcode presets | `wp_options` | `delete_option()` |
| Courses | `wp_posts` | `wp_delete_post(true)` |
| Course meta | `wp_postmeta` | Auto-deleted with posts |
| Categories | `wp_terms` | `wp_delete_term()` |
| Tags | `wp_terms` | `wp_delete_term()` |
| Statuses | `wp_terms` | `wp_delete_term()` |

### Technical Note: Taxonomy Registration

Taxonomies must be **temporarily registered** in `uninstall.php` before terms can be deleted:

```php
// Required because plugin code doesn't run during uninstall
register_taxonomy('simple_lms_category', 'simple_lms_course', array('hierarchical' => true));
register_taxonomy('simple_lms_tag', 'simple_lms_course', array('hierarchical' => false));
register_taxonomy('simple_lms_status', 'simple_lms_course', array('hierarchical' => true));
```

Without this, `get_terms()` returns `WP_Error` for unregistered taxonomies and terms remain orphaned in the database.

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

The built-in default template includes a metadata list at the top and structural placeholders:

```html
<ul>
{{#IF_DATE}}<li>Date: {{LMS_DATE}}</li>{{/IF_DATE}}
{{#IF_TIME}}<li>Time: {{LMS_TIME}}</li>{{/IF_TIME}}
{{#IF_LECTURER}}<li>Lecturer: {{LMS_LECTURER}}</li>{{/IF_LECTURER}}
</ul>

{{#IF_VIDEOS}}
{{LMS_VIDEOS}}
{{/IF_VIDEOS}}

{{#IF_CONTENT}}
{{LMS_CONTENT}}
{{/IF_CONTENT}}

{{#IF_MATERIALS}}
{{LMS_MATERIALS}}
{{/IF_MATERIALS}}

{{#IF_DATE}}
{{LMS_CERTIFICATE}}
{{/IF_DATE}}
```

Users can customize this template in Settings → Templates to add headers, styling, and additional placeholders as needed.

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
│ ┌────────────────────────────────────────────────────┐ │ │ ☐ Recording     │ │
│ │ Videos                               [+ Add Video] │ │ │ ☐ Live          │ │
│ │ ┌────────────────────────────────────────────────┐ │ │ │ ☐ Scheduled     │ │
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
6. **Access Control** - Membership checkboxes, subscription products (searchable multi-select with AJAX), redirect URL

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
- **Filters**: Filter by category, tags, status, lecturer, published status (combinable with AND logic)
- **Sorting**: Clickable column headers for Title, Course Date, Lecturer, Published
- **Pagination**: Navigate through courses
- **Screen Options**: Configurable items per page (WordPress standard Screen Options panel)
- **Columns**: Title, Category, Tags, Status, Course Date, Lecturer, Published
- **Actions**: Edit, View, Delete (with AJAX confirmation)
- **Reset**: Button to clear all active filters

---

## Certificate System

### Overview
Plugin includes optional certificate generation for courses using TCPDF library. Users can generate PDF certificates for completed courses.

### Files
- `includes/class-lms-certificates.php` - Main certificate class
- `templates/certificate-template.html` - Default certificate template
- `admin/views/tab-certificates.php` - Certificate settings tab
- `admin/views/certificates-generate.php` - Manual certificate generation page
- `tcpdf/` - Optimized TCPDF library (~7 MB)

### Certificate Settings (wp_options)

| Option Key | Type | Description |
|------------|------|-------------|
| `simple_lms_certificate_template` | string | HTML template for PDF |
| `simple_lms_certificate_logo_url` | string | Logo image URL |
| `simple_lms_certificate_signature_url` | string | Signature image URL |
| `simple_lms_certificate_issuer_company` | string | Issuer company name |
| `simple_lms_certificate_issuer_name` | string | Issuer person name |
| `simple_lms_certificate_issuer_title` | string | Issuer person title |
| `simple_lms_certificate_labels` | array | Customizable frontend labels (see below) |

### Certificate Frontend Labels (`simple_lms_certificate_labels`)

| Key | Default | Description |
|-----|---------|-------------|
| `table_course` | "Course" | Shortcode table header - course column |
| `table_lecturer` | "Lecturer" | Shortcode table header - lecturer column |
| `table_date` | "Date" | Shortcode table header - date column |
| `table_certificate` | "Certificate" | Shortcode table header - certificate column |
| `btn_download` | "Download" | Download button text in shortcode table |
| `btn_download_certificate` | "Download certificate" | Download button text on course page |
| `msg_login_required` | "Please log in to view certificates." | Message for non-logged-in users |
| `msg_no_certificates` | "No certificates available." | Message when no courses found |
| `msg_available_after` | "Available after course" | Short message in table |
| `msg_available_after_long` | "Certificate will be available after the course." | Long message on course page |
| `pdf_filename` | "certificate" | PDF filename (without .pdf extension) |
| `pdf_title_prefix` | "Certificate - " | Prefix for PDF title metadata |

### Course Meta

| Meta Key | Type | Default | Description |
|----------|------|---------|-------------|
| `_simple_lms_certificate_enabled` | string | '1' | Enable certificate for course ('1' = enabled, '0' = disabled) |

### Certificate Placeholders (in PDF template)

| Placeholder | Description |
|-------------|-------------|
| `{{CERT_USER_NAME}}` | User full name |
| `{{CERT_COURSE_TITLE}}` | Course title |
| `{{CERT_LECTURER}}` | Lecturer name |
| `{{CERT_DURATION}}` | Course duration |
| `{{CERT_COMPLETION_DATE}}` | Completion date |
| `{{CERT_LOGO_URL}}` | Logo URL |
| `{{CERT_SIGNATURE_URL}}` | Signature URL |
| `{{CERT_ISSUER_COMPANY}}` | Company name |
| `{{CERT_ISSUER_NAME}}` | Issuer name |
| `{{CERT_ISSUER_TITLE}}` | Issuer title |

### Template Placeholders

| Placeholder | Description |
|-------------|-------------|
| `{{LMS_CERTIFICATE}}` | Certificate generation form |
| `{{#IF_CERTIFICATE}}...{{/IF_CERTIFICATE}}` | Conditional block - shows content only if certificate is available |

### Shortcode

`[lms_certificate]` - Displays table of courses with certificate generation forms. Only shows courses where:
- Certificate is enabled (`_simple_lms_certificate_enabled` = '1')
- User is logged in
- User has access to the course

### Admin Menu

- **simpleLMS > Generate Certificate** - Manual certificate generation page
- **simpleLMS > Settings > Certificates** - Certificate settings tab

### Access Control

Certificate generation requires:
1. User must be logged in
2. Certificate must be enabled for the course
3. User must have access to the course (via membership/subscription or admin)

### Date Validation

**Frontend certificate generation** (shortcode, template placeholder):
- Completion date cannot be in the future
- Completion date cannot be earlier than the course date
- If course date is in the future, certificate form is hidden (shows "Available after course" message)
- Date input has `min` attribute set to course date and `max` set to today

**Admin panel (manual generation)**:
- Completion date cannot be in the future
- **No restriction** on dates earlier than course date (allows flexibility for admins)

---

> **REMINDER**: Keep this document updated! Any changes to the plugin architecture, database schema, file structure, or functionality must be reflected here.
