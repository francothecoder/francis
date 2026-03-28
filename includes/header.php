<?php require_once __DIR__ . '/functions.php'; refresh_current_user(); $user = current_user(); ?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? APP_NAME) . ' | ' . APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-blur app-blur-one"></div>
<div class="app-blur app-blur-two"></div>
<nav class="navbar navbar-expand-lg sticky-top app-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold brand-logo" href="<?= url('index.php') ?>">
            <span class="brand-dot"></span> Francis Kwesa
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 nav-pills-pro">
                <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('about.php') ?>">About</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('student-hub.php') ?>">Student Hub</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('projects.php') ?>">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('resources.php') ?>">Resources</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('community/index.php') ?>">Community</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('membership.php') ?>">Membership</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('contact.php') ?>">Contact</a></li>
                <li class="nav-item ms-lg-2">
                    <button type="button" class="theme-toggle" data-theme-toggle aria-label="Toggle dark mode">
                        <span class="theme-icon theme-icon-sun">☀️</span>
                        <span class="theme-icon theme-icon-moon">🌙</span>
                    </button>
                </li>
                <?php if ($user): ?>
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <li class="nav-item"><a class="btn btn-warning btn-pill btn-sm" href="<?= url('admin/index.php') ?>">Admin</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-primary btn-pill btn-sm" href="<?= url('student/dashboard.php') ?>">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('auth/logout.php') ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('auth/login.php') ?>">Login</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-pill btn-sm" href="<?= url('auth/register.php') ?>">Join Now</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="py-4 py-lg-5">
    <div class="container position-relative">
        <?php if ($msg = get_flash('success')): ?><div class="alert alert-success soft-alert"><?= e($msg) ?></div><?php endif; ?>
        <?php if ($msg = get_flash('error')): ?><div class="alert alert-danger soft-alert"><?= e($msg) ?></div><?php endif; ?>
