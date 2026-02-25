<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?> - <?php bloginfo('name'); ?></title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/redoc@2.0.0/bundles/redoc.min.css">
    <style>
        body { margin: 0; padding: 0; background: #fff; }
        .topbar { background: #1d1d1d; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .topbar h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 600; }
        .topbar .site-name { color: #999; font-size: 14px; }
        .topbar .logo { height: 30px; width: auto; }
        #redoc { padding: 0; }
    </style>
</head>
<body>
    <div class="topbar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if (!empty($logo_url)): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <h1><?php echo esc_html($title); ?></h1>
        </div>
        <div class="site-name"><?php echo esc_html($site_name); ?></div>
    </div>
    <redoc spec-url="<?php echo esc_url($specUrl); ?>" id="redoc"></redoc>
    <script src="https://cdn.jsdelivr.net/npm/redoc@2.0.0/bundles/redoc.standalone.js"></script>
</body>
</html>
