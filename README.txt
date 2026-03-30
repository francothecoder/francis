ACADEMIC SUPPORT HUB
====================

A native PHP academic support platform with:

- student / tutor / admin roles
- tutor verification
- student help requests
- tutor offers
- accepted session workflow
- session chat thread
- monthly academic subscriptions
- XP, levels, reward credits
- tutor ratings
- platform commission control
- payout requests and admin payout approval
- academic resources module

SETUP
-----
1. Create a MySQL database.
2. Edit config/database.php with your database credentials.
3. Import database.sql into the database.
4. Copy the folder to your web root.
5. Update BASE_URL in config/config.php if your folder name is different.
6. Login with seeded accounts below.

DEMO ACCOUNTS
-------------
Admin
Email: admin@academicsupporthub.com
Password: password123

Student
Email: student@example.com
Password: student123

Tutor
Email: tutor@example.com
Password: tutor12345

NOTES
-----
- This package uses manual internal payment records, not live mobile money APIs.
- File uploads are validated by extension, MIME type, and size.
- CSRF protection is enabled for all major forms.
- This is a strong native PHP starter for production-minded deployment, but before live launch you should still add:
  - HTTPS on hosting
  - stronger audit logging
  - email notifications
  - payment gateway integration
  - cron tasks for expiring subscriptions
  - server hardening and backup automation

FOLDER HIGHLIGHTS
-----------------
/admin      platform control
/student    student workflows
/tutor      tutor workflows
/includes   shared bootstrap, helpers, layout
/config     database configuration
/database.sql full schema + seed data
