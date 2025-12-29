# Cache Refresh Instructions for TrustGrade Plugin

## When to Refresh Cache

You need to refresh the Moodle cache after:
- Adding new external web service functions
- Updating plugin version
- Making changes to `db/services.php`

## How to Refresh Cache

### Method 1: Admin UI (Recommended)
1. Log in as an administrator
2. Navigate to: **Site administration → Development → Purge all caches**
3. Click the "Purge all caches" button
4. Wait for the process to complete

### Method 2: CLI Command
Run this command from your Moodle root directory:
```bash
php admin/cli/purge_caches.php
```

### Method 3: Upgrade Plugin
1. Navigate to: **Site administration → Notifications**
2. If the plugin version has changed, click "Upgrade Moodle database now"
3. This will automatically refresh caches

## Troubleshooting External Functions

If you see an error like:
```
"לא ניתן למצוא את רשומת הנתון בטבלה external_functions במסד הנתונים"
```

This means the external function is not registered. Follow these steps:

1. **Check version number** - Ensure `version.php` has been incremented
2. **Upgrade the plugin** - Go to Site administration → Notifications
3. **Purge all caches** - Go to Site administration → Development → Purge all caches
4. **Verify registration** - Check that the function appears in the services list

## Current External Functions

The following external functions are registered for TrustGrade:
- `local_trustgrade_toggle_mandatory_question` - Toggle mandatory status for questions
- `local_trustgrade_save_question` - Save question
- `local_trustgrade_delete_question` - Delete question
- `local_trustgrade_get_pending_tasks` - Get user's pending async tasks
- And more... (see `db/services.php` for complete list)
