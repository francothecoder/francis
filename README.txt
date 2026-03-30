Academic Support Hub - Premium PHP Edition
=========================================

Default admin login
- Email: admin@academicsupporthub.com
- Password: password123

Sample student login
- Email: student@example.com
- Password: password123

Sample tutor login
- Email: tutor@example.com
- Password: password123

Key upgrades included
- Premium refreshed UI with cleaner cards and navigation
- Resource publishing with downloadable attachments and optional reference links
- Session chat now supports file attachments
- Lenco mobile money payment workflow for subscriptions and tutor sessions
- Payment status page with manual refresh/requery support
- Webhook endpoint for Lenco callback updates
- Request flow now separates offer acceptance from payment confirmation
- Improved settings page for payment gateway configuration

Setup
1. Extract the ZIP into your web root.
2. Create a MySQL database.
3. Import database.sql.
4. Update config/database.php or set DB_* environment variables.
5. Update BASE_URL in config/config.php or set BASE_URL env variable.
6. In Admin > Settings, add your Lenco public key, secret key, mode, and callback URL.
7. In your Lenco dashboard, point the callback/webhook URL to: /webhook/lenco.php

Notes
- For local testing, leave Lenco in sandbox mode until your callback URL is publicly reachable.
- The system can still be used without Lenco configured, but live payment buttons are disabled.
- Re-import database.sql for the new schema.


Premium update notes (payments + email)
-------------------------------------
- Mobile money now normalizes Zambia numbers to 260XXXXXXXXX before sending to Lenco.
- The Lenco payload now sends both provider and operator fields, stores gateway failure reasons in payment notes, and writes diagnostics to logs/lenco_gateway.log.
- Payment status now treats pay-offline as pending so students can approve on phone and refresh safely.
- Webhook payloads are logged to logs/lenco_webhook.log for troubleshooting.
- PHPMailer is bundled in vendor/phpmailer/src and SMTP settings are available in Admin > Settings.
- To enable emails, fill SMTP host, port, username, password, from email/name, then turn on SMTP and email notifications.
