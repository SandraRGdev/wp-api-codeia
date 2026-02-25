<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($title); ?> - <?php bloginfo('name'); ?></title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@<?php echo $this->swaggerVersion; ?>/swagger-ui.css">
    <style>
        body { margin: 0; padding: 0; }
        #swagger-ui { max-width: 1460px; margin: 0 auto; }
        .topbar { background: #1d1d1d; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
        .topbar h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 600; }
        .topbar .site-name { color: #999; font-size: 14px; }
        .topbar .logo { height: 30px; width: auto; }
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
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@<?php echo $this->swaggerVersion; ?>/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@<?php echo $this->swaggerVersion; ?>/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: <?php echo wp_json_encode($specUrl); ?>,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: "list",
                filter: true,
                persistAuthorization: true,
                tryItOutEnabled: true,
                syntaxHighlight: {
                    activate: true,
                    theme: "monokai"
                }
            });
        };
    </script>
</body>
</html>
