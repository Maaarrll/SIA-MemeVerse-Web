<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memeverse</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">

        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
            Meme<span>Verse</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto gap-2">

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house-door"></i> Home
                    </a>
                </li>

                <?php if (isLoggedIn()): ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/upload.php">
                            <i class="bi bi-plus-circle"></i> Upload
                        </a>
                    </li>

                    <li class="nav-item">
                          <a class="nav-link" href="<?php echo BASE_URL; ?>/messages.php">
                            <i class="bi bi-chat-dots"></i> Messages
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#" id="logoutLink">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link btn-outline-pastel" href="<?php echo BASE_URL; ?>/register.php">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>

    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <div class="col-lg-3 col-xl-2 sidebar d-none d-lg-block">
            <h5>Discover</h5>

            <ul class="category-list">

                <li>
                    <a href="<?php echo BASE_URL; ?>/index.php"
                       class="category-link <?php echo (!isset($_GET['slug'])) ? 'active' : ''; ?>">
                        <i class="bi bi-grid-3x3-gap-fill"></i> All Memes
                    </a>
                </li>

                <?php
                $catQuery = $conn->query("SELECT slug, name FROM categories ORDER BY name");

                if ($catQuery) {
                    while ($cat = $catQuery->fetch_assoc()) {
                        $active = (isset($_GET['slug']) && $_GET['slug'] === $cat['slug']) ? 'active' : '';

                        $icon = 'bi-tag';

                        switch ($cat['slug']) {
                            case 'funny':
                                $icon = 'bi-emoji-laughing';
                                break;
                            case 'animals':
                                $icon = 'bi-paw';
                                break;
                            case 'music':
                                $icon = 'bi-music-note-beamed';
                                break;
                            case 'tv':
                                $icon = 'bi-tv';
                                break;
                            case 'games':
                                $icon = 'bi-controller';
                                break;
                            case 'movie':
                                $icon = 'bi-film';
                                break;
                            case 'sport':
                                $icon = 'bi-trophy';
                                break;
                            case 'foods':
                                $icon = 'bi-cup-hot';
                                break;
                            case 'travel':
                                $icon = 'bi-airplane';
                                break;
                        }

                        echo '<li>
                                <a href="' . BASE_URL . '/category.php?slug=' . htmlspecialchars($cat['slug']) . '"
                                   class="category-link ' . $active . '">
                                    <i class="bi ' . $icon . '"></i> ' . htmlspecialchars($cat['name']) . '
                                </a>
                              </li>';
                    }
                } else {
                    echo '<li class="text-muted small">Error loading categories</li>';
                }
                ?>

            </ul>
        </div>

        <div class="col-lg-9 col-xl-10 main-content">