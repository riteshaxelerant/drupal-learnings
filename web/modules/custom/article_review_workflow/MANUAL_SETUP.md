# Manual Setup Instructions

Since your Drupal site already has the Article content type and content_editor role, follow these manual steps after enabling the module:

## 1. Enable the Module
```bash
drush en article_review_workflow
```

## 2. Assign Workflow to Article Content Type

**Via Admin UI:**
1. Go to `Configuration` → `Workflow` → `Workflows`
2. Click "Edit" on "Article Editorial Workflow"
3. Click "This workflow applies to" tab
4. Check "Article" under Content types
5. Save

**Via Drush:**
```bash
drush config:set node.type.article third_party_settings.content_moderation.enabled true
drush config:set node.type.article third_party_settings.content_moderation.allowed_moderation_states.0 draft
drush config:set node.type.article third_party_settings.content_moderation.allowed_moderation_states.1 needs_review  
drush config:set node.type.article third_party_settings.content_moderation.allowed_moderation_states.2 published
drush config:set node.type.article third_party_settings.content_moderation.default_moderation_state draft
```

## 3. Add Workflow Permissions to Existing Roles

### For Content Author Role (if it exists):
**Via Admin UI:**
1. Go to `People` → `Permissions`
2. Find "Content Author" role
3. Check these permissions:
   - `Use Article Editorial Workflow transition submit_for_review`
   - `Use Article Editorial Workflow transition revert_to_draft`

### For Content Editor Role:
**Via Admin UI:**
1. Go to `People` → `Permissions` 
2. Find "Content Editor" role
3. Check these permissions:
   - `Use Article Editorial Workflow transition submit_for_review`
   - `Use Article Editorial Workflow transition publish`
   - `Use Article Editorial Workflow transition revert_to_draft`
   - `View Needs Review Dashboard`

### Via Drush (if Content Author role exists):
```bash
drush user:role:add-perm content_author "use article_editorial_workflow transition submit_for_review"
drush user:role:add-perm content_author "use article_editorial_workflow transition revert_to_draft"
```

### Via Drush (for Content Editor):
```bash
drush user:role:add-perm content_editor "use article_editorial_workflow transition submit_for_review"
drush user:role:add-perm content_editor "use article_editorial_workflow transition publish" 
drush user:role:add-perm content_editor "use article_editorial_workflow transition revert_to_draft"
drush user:role:add-perm content_editor "view needs review dashboard"
```

## 4. Create Content Author Role (if it doesn't exist)
```bash
drush user:role:create content_author "Content Author"
drush user:role:add-perm content_author "create article content"
drush user:role:add-perm content_author "edit own article content"
drush user:role:add-perm content_author "delete own article content"
drush user:role:add-perm content_author "view own unpublished content"
drush user:role:add-perm content_author "use article_editorial_workflow transition submit_for_review"
drush user:role:add-perm content_author "use article_editorial_workflow transition revert_to_draft"
```

## 5. Configure Email Notifications
1. Go to `/admin/config/article_review_workflow/settings`
2. Enter the email address for notifications
3. Save

## 6. Test the Workflow
1. Create test users with Content Author and Content Editor roles
2. Test the workflow as described in README.md