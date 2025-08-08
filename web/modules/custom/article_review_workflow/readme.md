# Article Workflow Module

This module provides a custom editorial workflow for the Article content type in Drupal 11, including a dashboard for articles in the "Needs Review" state and email notifications.

## Installation

1. Place the `article_review_workflow` module in the `modules/custom` directory.
2. Enable the module:
   ```bash
   drush en article_review_workflow
   ```
3. **If you get configuration conflicts**, follow the manual setup instructions in `MANUAL_SETUP.md`

## Configuration

- The workflow is automatically assigned to the Article content type.
- Two roles are created: **Content Author** and **Content Editor**.
- Configure the notification email at `/admin/config/article_review_workflow/settings`.

## Roles and Permissions

### Content Author
- Create articles
- Edit own articles  
- Submit articles for review
- **Cannot approve articles**

### Content Editor  
- All Content Author permissions
- Edit any articles
- **Approve articles** (key restriction)
- Access the dashboard at `/admin/content/needs-review-dashboard`
- Receive email notifications

## Usage

- **Content Authors**: Create articles and submit them for review
- **Content Editors**: Review submissions and approve/reject them
- Email notifications are sent when articles move to "Needs Review"

## Testing the Editorial Workflow

1. **Create test users**:
   - Create a user with **Content Author** role
   - Create a user with **Content Editor** role

2. **Test Content Author workflow**:
   - Log in as Content Author (Browser 1)
   - Create an Article 
   - Transition it to "Needs Review"
   - Verify you **cannot** transition to "Approved"

3. **Test Content Editor workflow**:
   - Log in as Content Editor (Browser 2)
   - Visit `/admin/content/needs-review-dashboard`
   - Verify the article appears in the dashboard
   - Verify email notification was sent
   - Approve the article (only Editors can do this)

4. **Verify restrictions**:
   - Content Authors cannot access the dashboard
   - Content Authors cannot approve articles
   - Only Content Editors can approve articles

## Development

- Code follows Drupal Coding Standards.
- Uses PSR-4 namespacing.
- Located in `src/` for controllers, services, and event subscribers.
