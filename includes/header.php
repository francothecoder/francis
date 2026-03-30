<?php
$user = current_user();
$notifications = $user ? recent_notifications((int) $user['id']) : [];
$unreadCount = $user ? unread_notification_count((int) $user['id']) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($pageTitle ?? APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= app_url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= app_url() ?>"><?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="appNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= app_url() ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= app_url('tutors.php') ?>">Tutors</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= app_url('resources.php') ?>">Resources</a></li>
                <?php if (!$user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= app_url('pricing.php') ?>">Academic Plans</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">Notifications
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge rounded-pill text-bg-warning"><?= (int) $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-2 notify-menu">
                            <?php if ($notifications): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a class="dropdown-item small" href="<?= e($notification['target_path'] ? app_url($notification['target_path']) : '#') ?>">
                                        <strong class="d-block"><?= e($notification['title']) ?></strong>
                                        <span class="text-muted"><?= e($notification['message']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dropdown-item small text-muted">No notifications yet.</div>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="nav-item"><span class="nav-link"><?= e($user['name']) ?></span></li>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="btn btn-light btn-sm" href="<?= app_url('admin/dashboard.php') ?>">Admin</a></li>
                    <?php elseif ($user['role'] === 'tutor'): ?>
                        <li class="nav-item"><a class="btn btn-light btn-sm" href="<?= app_url('tutor/dashboard.php') ?>">Tutor Panel</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-light btn-sm" href="<?= app_url('student/dashboard.php') ?>">Student Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= app_url('logout.php') ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-outline-light btn-sm" href="<?= app_url('login.php') ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-warning btn-sm" href="<?= app_url('register.php') ?>">Create Account</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">
        <?php if ($message = get_flash('success')): ?>
            <div class="alert alert-success"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($message = get_flash('error')): ?>
            <div class="alert alert-danger"><?= e($message) ?></div>
        <?php endif; ?>
