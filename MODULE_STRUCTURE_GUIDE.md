# YugoVote Module Pattern Reference Guide

**Last Updated:** December 26, 2025

---

## 📦 STANDARD MODULE STRUCTURE

Every feature module in `inc/` MUST follow this exact structure:

```
inc/[module-name]/
├── [module]-init.php           ← Module loader (REQUIRED)
├── [module]-scripts.php        ← Asset enqueuing (optional, use if needed)
├── [module]-shortcodes.php     ← Frontend rendering (if using shortcodes)
├── [module]-hooks.php          ← Module actions/filters (optional)
├── helpers.php                 ← Module-specific helpers (optional)
├── cpts/                       ← Custom post types AND taxonomies (REQUIRED)
│   ├── cpt-[name].php
│   └── taxonomy-[name].php
├── meta/                       ← Metaboxes (REQUIRED)
│   └── [post-type]-meta.php
├── api/                        ← AJAX endpoints (REQUIRED)
│   └── [module]-endpoints.php
├── admin/                      ← Admin columns & filters (NEW!)
│   └── [module]-columns.php
├── templates/                  ← HTML templates (optional but recommended)
│   └── [name].php
└── [special-folders]/          ← Module-specific (e.g., services/)
    └── [special-file].php
```

---

## ✅ EXAMPLE: VOTING MODULE (Gold Standard)

```
inc/voting/
├── voting-init.php            ✅ Loads all voting files
├── voting-scripts.php         ✅ Enqueues voting JS/CSS
├── voting-shortcodes.php      ✅ Frontend shortcodes
├── voting-hooks.php           ✅ Custom actions
├── helpers.php                ✅ Voting-specific functions
├── cpts/
│   ├── cpt-user-level.php
│   ├── cpt-voting-list.php
│   ├── cpt-voting-list-items.php
│   ├── taxonomy-voting-list-category.php    ✅ MOVED HERE (Dec 26, 2025)
│   └── taxonomy-voting-item-category.php    ✅ MOVED HERE (Dec 26, 2025)
├── meta/
│   ├── user-level-meta.php
│   ├── voting-list-meta.php
│   ├── voting-list-items-meta.php
│   └── voting-list-taxonomy-meta.php
├── api/
│   └── voting-endpoints.php
├── admin/                     ✅ NEW (Dec 26, 2025)
│   └── voting-columns.php
└── templates/
    └── voting-item-card.php
```

---

## 🎯 INIT FILE TEMPLATE

**Use this template for every new module:**

```php
<?php
/**
 * [Module Name] Feature Initializer
 *
 * Loads all necessary files for [module name] functionality.
 *
 * @package YugoVote
 */

if (!defined('ABSPATH')) {
    exit();
}

$[module]_inc_path = trailingslashit(get_stylesheet_directory()) . 'inc/[module]/';

// --- Custom Post Types & Taxonomies ---
require_once $[module]_inc_path . 'cpts/cpt-[name].php';
require_once $[module]_inc_path . 'cpts/taxonomy-[name].php';

// --- Meta Boxes ---
require_once $[module]_inc_path . 'meta/[name]-meta.php';

// --- API Endpoints ---
require_once $[module]_inc_path . 'api/[module]-endpoints.php';

// --- Admin (columns, filters) ---
if (file_exists($[module]_inc_path . 'admin/[module]-columns.php')) {
    require_once $[module]_inc_path . 'admin/[module]-columns.php';
}

// --- Shortcodes ---
require_once $[module]_inc_path . '[module]-shortcodes.php';

// --- Scripts & Styles ---
if (file_exists($[module]_inc_path . '[module]-scripts.php')) {
    require_once $[module]_inc_path . '[module]-scripts.php';
}

// --- Helpers ---
if (file_exists($[module]_inc_path . 'helpers.php')) {
    require_once $[module]_inc_path . 'helpers.php';
}
```

---

## 🔑 CODING STANDARDS

### **Function Naming**

- Prefix all functions with `cs_`
- Use snake_case: `cs_get_voting_score()`
- Pattern: `cs_[module]_[action]`

**Examples:**

```php
cs_register_voting_list_cpt()
cs_get_voting_item_score()
cs_add_question_columns()
cs_filter_voting_lists_query()
```

### **Data Storage**

- Use `post_meta` arrays for options/votes
- Do NOT use ACF, comments, or separate custom tables
- Use meta keys prefixed with `_`

**Examples:**

```php
get_post_meta($post_id, '_vote_count_cache', true)
update_post_meta($post_id, '_is_featured', '1')
$options = get_post_meta($post_id, '_voting_options', true)
```

### **Frontend Delivery**

- Use Shortcodes for user-facing content
- Never echo HTML directly in functions
- Separate HTML in `/templates/` folder

**Pattern:**

```php
// voting-shortcodes.php
function cs_voting_list_shortcode($atts) {
    ob_start();
    include 'templates/voting-list.php';
    return ob_get_clean();
}
add_shortcode('voting_list', 'cs_voting_list_shortcode');
```

---

## 📋 ADMIN COLUMNS CHECKLIST

When adding admin columns to a module:

### **Required Folder:**

```
inc/[module]/admin/
└── [module]-columns.php
```

### **Required Elements:**

- [ ] Column registration filter: `manage_[cpt]_posts_columns`
- [ ] Column population action: `manage_[cpt]_posts_custom_column`
- [ ] Query modification filter: `pre_get_posts` (for filters)
- [ ] Filter dropdown action: `restrict_manage_posts`
- [ ] Optional: Sortable columns filter: `manage_edit-[cpt]_sortable_columns`
- [ ] Optional: Orderby filter: `pre_get_posts` for sorting logic

### **Required Update:**

Add to module's `*-init.php`:

```php
// --- Admin (columns, filters) ---
if (file_exists($[module]_inc_path . 'admin/[module]-columns.php')) {
    require_once $[module]_inc_path . 'admin/[module]-columns.php';
}
```

---

## 📁 FOLDER GUIDELINES

### **DO:**

- ✅ Create folders for each clear responsibility
- ✅ Use consistent naming (lowercase, hyphens for multi-word)
- ✅ Keep files small and focused (one concept per file)
- ✅ Group related files in folders

### **DON'T:**

- ❌ Create folders for single-file sections
- ❌ Mix multiple concerns in one file
- ❌ Put helpers in root when they belong in module
- ❌ Put module code in global folders

---

## 🔄 CURRENT MODULES (Dec 26, 2025)

### **✅ VOTING** (Fully Compliant)

- Structure: ✅ Complete & correct
- Admin: ✅ Proper `/admin/` folder
- Taxonomies: ✅ In `/cpts/`
- Status: **GOLD STANDARD**

### **✅ QUIZZES** (Fully Compliant)

- Structure: ✅ Complete & correct
- Admin: ✅ New `/admin/` folder (Dec 26)
- Services: ✅ Special `/services/` folder supported
- Status: **EXCELLENT**

### **✅ POLLS** (Fully Compliant)

- Structure: ✅ Complete & correct
- Admin: ✅ Proper `/admin/` folder
- Status: **EXCELLENT**

### **✅ ACCOUNT** (Fully Compliant)

- Structure: ✅ Complete & correct
- Shortcodes: ✅ Separate `/shortcodes/` folder supported
- Status: **GOOD** (No standard admin columns needed)

### **✅ ADMIN** (Global)

- Purpose: Only truly global admin code
- Current: Menu, scripts, AJAX, tools
- Status: **CLEAN** (Module-specific filters removed)

### **✅ MIGRATIONS** (Proper Initializer)

- Structure: ✅ New `migrations-init.php` (Dec 26)
- Status: **IMPROVED**

### **✅ HELPERS** (Proper Initializer)

- Structure: ✅ New `helpers-init.php` (Dec 26)
- Status: **IMPROVED**

---

## 🆕 CREATING A NEW MODULE

### **Step 1: Create Folder Structure**

```bash
mkdir -p inc/my-feature/{cpts,meta,api,admin,templates}
```

### **Step 2: Create Module Init File**

```bash
touch inc/my-feature/my-feature-init.php
```

### **Step 3: Create CPT Files**

```bash
touch inc/my-feature/cpts/cpt-my-feature.php
touch inc/my-feature/cpts/taxonomy-my-feature-category.php
```

### **Step 4: Create Meta Files**

```bash
touch inc/my-feature/meta/my-feature-meta.php
```

### **Step 5: Create API File**

```bash
touch inc/my-feature/api/my-feature-endpoints.php
```

### **Step 6: Create Admin File (If Needed)**

```bash
touch inc/my-feature/admin/my-feature-columns.php
```

### **Step 7: Create Shortcodes File (If Needed)**

```bash
touch inc/my-feature/my-feature-shortcodes.php
```

### **Step 8: Create Templates Folder**

```bash
touch inc/my-feature/templates/my-feature.php
```

### **Step 9: Add to Main Init**

In `inc/init.php`:

```php
require_once get_stylesheet_directory() . '/inc/my-feature/my-feature-init.php';
```

---

## 🧹 CLEANUP TASKS

**Files backed up from refactoring (Dec 26, 2025):**

- `inc/admin/admin-filters.php.bak` - Safe to delete after 30 days

**Folders removed from refactoring:**

- `inc/voting/taxonomies/` - Moved to `inc/voting/cpts/`

---

## 📞 SUPPORT

**Questions about module structure?**

- Check MIGRATION_REPORT.md for detailed changes
- Review example modules (voting, quizzes, polls)
- Follow the init file template above

**Need to add a new feature?**

- Copy an existing module as template
- Rename folders and files
- Follow the standard structure above
