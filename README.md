# MemeVerse Web Application

MemeVerse is a web-based meme sharing application where users can upload, browse, vote, and comment on memes. It includes user authentication, profile management, category-based browsing, image uploads, and a responsive dark-themed interface.

## Features

- User registration and login
- Meme/image upload with title, description, and category
- Home feed with uploaded memes
- Upvote and downvote system
- Comment system
- Single post view
- User profile page
- Edit profile information
- Avatar upload
- Category-based browsing
- Responsive web design using Bootstrap
- PHP API endpoints for web and mobile integration

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap 5
- Bootstrap Icons
- XAMPP / Apache

## Folder Structure

```text
memeverse/
├── api/
│   ├── register.php
│   ├── login.php
│   ├── logout.php
│   ├── upload.php
│   ├── posts.php
│   ├── post.php
│   ├── vote.php
│   ├── comments.php
│   ├── profile.php
│   ├── upload_avatar.php
│   ├── unread_messages.php
│   └── unread_notifications.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── uploads/
│       └── avatars/
├── includes/
│   ├── config.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── index.php
├── register.php
├── login.php
├── upload.php
├── post.php
├── profile.php
├── category.php
└── logout.php
```
## API Endpoints
```text
POST api/register.php
POST api/login.php
POST api/logout.php
GET  api/posts.php
GET  api/post.php?id=1
POST api/upload.php
POST api/vote.php
GET  api/comments.php?post_id=1
POST api/comments.php
GET  api/profile.php?id=1
POST api/profile.php
POST api/upload_avatar.php
```
## Project Status
This project is currently under development. 
The web application is functional locally and is being prepared for mobile integration and possible deployment to free PHP/MySQL hosting.

## Group Members
<lu>
  <li>Fabia, Sean Ivan</li>
  <li>Pinca, Paolo Leandro</li>
  <li>Ordonia, Marl June</li>
  <li>Quitorio, Adielyn</li>
  <li>Balat, Khrister John</li>
</lu>
