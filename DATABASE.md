# Database Structure

This document describes the database schema for the Forum Application.

## Tables Overview

The application uses the following database tables:

### Core Tables

#### `users`
User accounts and profiles
- `id` - Primary key
- `username` - Unique username
- `name` - Display name
- `email` - Email address (unique)
- `password` - Hashed password
- `avatar` - Avatar image path
- `bio` - User biography
- `location` - User location
- `website` - Personal website URL
- `role` - User role (user/moderator/admin)
- `is_banned` - Ban status
- `banned_at` - Ban timestamp
- `banned_until` - Temporary ban expiration
- `ban_reason` - Reason for ban
- `banned_by` - Foreign key to users table
- `last_activity_at` - Last activity timestamp
- `posts_count` - Number of posts
- Timestamps

#### `categories`
Forum categories
- `id` - Primary key
- `name` - Category name
- `description` - Category description
- `icon` - Icon class or image
- `sort_order` - Display order
- `seo_title` - SEO meta title
- `seo_description` - SEO meta description
- `seo_keywords` - SEO keywords
- `allow_gallery` - Enable gallery for topics in category
- Timestamps

#### `topics`
Discussion topics
- `id` - Primary key
- `title` - Topic title
- `content` - Topic content (HTML)
- `user_id` - Foreign key to users
- `category_id` - Foreign key to categories
- `last_activity_at` - Last activity timestamp
- `replies_count` - Number of replies
- `likes_count` - Number of likes
- `views` - View count
- `is_closed` - Closed status
- `tags` - Legacy tags field
- `pin_type` - Pin type (none/category/global)
- `edited_at` - Last edit timestamp
- `edit_count` - Number of edits
- Timestamps

#### `posts`
Replies to topics
- `id` - Primary key
- `content` - Post content (HTML)
- `topic_id` - Foreign key to topics
- `user_id` - Foreign key to users
- `likes_count` - Number of likes
- `edited_at` - Last edit timestamp
- `edit_count` - Number of edits
- Timestamps

### Tag System

#### `tags`
Topic tags
- `id` - Primary key
- `name` - Tag name (unique)
- `slug` - URL-friendly slug (unique)
- `category_id` - Foreign key to categories
- `topics_count` - Number of topics with this tag
- `is_active` - Active status
- Timestamps

#### `topic_tag` (Pivot table)
Many-to-many relationship between topics and tags
- `id` - Primary key
- `topic_id` - Foreign key to topics
- `tag_id` - Foreign key to tags
- Timestamps

### Engagement

#### `likes` (Polymorphic)
Likes for topics and posts
- `id` - Primary key
- `user_id` - Foreign key to users
- `likeable_id` - ID of liked item
- `likeable_type` - Type of liked item (Topic/Post)
- Timestamps
- Unique constraint on (user_id, likeable_id, likeable_type)

#### `bookmarks`
User bookmarks for topics
- `id` - Primary key
- `user_id` - Foreign key to users
- `topic_id` - Foreign key to topics
- Timestamps
- Unique constraint on (user_id, topic_id)

#### `notifications`
User notifications
- `id` - Primary key
- `user_id` - Recipient user ID
- `from_user_id` - Sender user ID (nullable)
- `type` - Notification type
- `data` - JSON data
- `is_read` - Read status
- Timestamps

### File Management

#### `topic_files`
Files attached to topics
- `id` - Primary key
- `topic_id` - Foreign key to topics
- `file_path` - Storage path
- `original_name` - Original filename
- `mime_type` - MIME type
- `size` - File size in bytes
- Timestamps

#### `post_files`
Files attached to posts
- `id` - Primary key
- `post_id` - Foreign key to posts
- `file_path` - Storage path
- `original_name` - Original filename
- `mime_type` - MIME type
- `size` - File size in bytes
- Timestamps

#### `temporary_files`
Temporary uploaded files
- `id` - Primary key
- `file_path` - Storage path
- `original_name` - Original filename
- `session_id` - Session identifier
- `mime_type` - MIME type
- `size` - File size in bytes
- Timestamps

#### `topic_gallery_images`
Gallery images for topics
- `id` - Primary key
- `topic_id` - Foreign key to topics
- `image_path` - Original image path
- `thumbnail_path` - Thumbnail image path
- `caption` - Image caption
- `order` - Display order
- Timestamps

### Messaging System

#### `conversations`
Private message conversations
- `id` - Primary key
- `user_one_id` - First participant
- `user_two_id` - Second participant
- `last_message_at` - Last message timestamp
- Timestamps

#### `messages`
Private messages
- `id` - Primary key
- `conversation_id` - Foreign key to conversations
- `sender_id` - Foreign key to users
- `content` - Message content
- `is_read` - Read status
- Timestamps

### Security & Tracking

#### `user_sessions`
User session tracking
- `id` - Primary key
- `user_id` - Foreign key to users
- `ip_address` - IP address
- `user_agent` - Browser user agent
- `action` - Action performed
- `details` - Additional details
- Timestamps

#### `login_attempts`
Login attempt tracking for security
- `id` - Primary key
- `email` - Email address attempted
- `ip_address` - IP address
- `user_agent` - Browser user agent
- `successful` - Success status
- `created_at` - Timestamp

#### `password_reset_tokens`
Password reset tokens
- `email` - Primary key
- `token` - Reset token
- `created_at` - Creation timestamp

## Indexes

The database includes indexes on:
- Foreign keys for all relationships
- Frequently queried columns (role, is_banned, last_activity_at, etc.)
- Composite indexes for common query patterns
- Unique constraints where appropriate

## Relationships

### User Relationships
- Has many: topics, posts, likes, bookmarks, notifications, conversations
- Belongs to: banned_by (self-referential)

### Topic Relationships
- Belongs to: user, category
- Has many: posts, files, gallery_images, bookmarks
- Has many through: tags (via topic_tag)
- Morphs many: likes

### Post Relationships
- Belongs to: user, topic
- Has many: files
- Morphs many: likes

### Category Relationships
- Has many: topics, tags

## Migration Order

Migrations are numbered to ensure proper execution order:
1. `users` - Must be first (referenced by many tables)
2. `password_reset_tokens` - Independent
3. `categories` - Referenced by topics and tags
4. `tags` - Referenced by topic_tag
5. `topics` - Referenced by posts and bookmarks
6. `posts` - Referenced by post_files
7. Pivot and relationship tables
8. File and tracking tables

## Running Migrations

To create all tables:
```bash
php artisan migrate
```

To rollback all migrations:
```bash
php artisan migrate:rollback
```

To reset and re-run all migrations:
```bash
php artisan migrate:fresh
```

To reset and seed database:
```bash
php artisan migrate:fresh --seed
```

## Seeding Data

The application includes seeders for:
- Categories (CategorySeeder)
- Roles (RoleSeeder)

Run seeders:
```bash
php artisan db:seed
```
