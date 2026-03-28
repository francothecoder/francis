FRANCIS KWESA - NATIVE PHP + MYSQL (UPDATED)
============================================

Included Features
-----------------
- public website pages
- student registration and login
- student dashboard
- membership plans with subscription request flow
- admin approval of subscriptions
- payment recording
- projects library with access levels and protected downloads
- resources section with protected downloads
- support request system with threaded replies
- community section with topics, comments, and replies
- admin dashboard
- admin management for users, projects, resources, support, community, and contact messages

Quick Setup (XAMPP / Windows)
-----------------------------
1. Extract this project into htdocs, for example:
   C:\xampp\htdocs\francis

2. IMPORTANT:
   Open config/config.php and make sure:
   $baseUrl = '/francis';
   matches your actual folder name in htdocs.

3. Start Apache and MySQL in XAMPP.

4. Create a new empty MySQL database in phpMyAdmin.
   Example: franciskwesa_db

5. Open the installer in your browser:
   http://localhost/francis/install.php

6. Enter your database details and click Install Now.

7. Open the site homepage:
   http://localhost/francis/

Demo Login
----------
Admin:
- Email: admin@franciskwesa.com
- Password: password123

Student:
- Email: student@example.com
- Password: student123

Notes
-----
- Make sure PHP has pdo_mysql enabled.
- Uploaded project files go into /uploads/projects
- Uploaded resource files go into /uploads/resources
- Subscription approval is manual from the admin dashboard
