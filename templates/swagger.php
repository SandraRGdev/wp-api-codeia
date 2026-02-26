<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(isset($title) ? $title : 'API Documentation'); ?> - <?php bloginfo('name'); ?></title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>
        /* ============================================
           BASE - PREVENT ALL HORIZONTAL OVERFLOW
        ============================================ */
        html, body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow-x: hidden !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        html *, body * {
            box-sizing: border-box !important;
        }

        /* Force ALL elements to stay within bounds */
        html *, body *, html *::before, body *::after, html *::before, body *::after {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

        #swagger-ui {
            max-width: 100% !important;
            margin: 0 auto;
            overflow-x: hidden !important;
        }

        /* Topbar */
        .topbar {
            background: #1d1d1d;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden !important;
            max-width: 100% !important;
        }
        .topbar h1 { color: #fff; margin: 0; font-size: 20px; font-weight: 600; word-wrap: break-word; }
        .topbar .site-name { color: #999; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topbar .logo { height: 30px; width: auto; }

        /* Loading */
        #loading { text-align: center; padding: 100px 20px; }
        #loading .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* Error */
        #error { display: none; padding: 40px 20px; text-align: center; background: #fff; }
        #error h2 { color: #d32f2f; margin-top: 0; }
        #error pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            text-align: left;
            overflow-x: auto;
            max-width: 100%;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .error-details { max-width: 800px; margin: 0 auto; }
        .retry-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .retry-btn:hover { background: #2980b9; }

        /* ============================================
           SWAGGER UI SPECIFIC OVERRIDES FOR OVERFLOW
        ============================================ */
        .swagger-ui {
            overflow-x: hidden !important;
            max-width: 100% !important;
        }

        .swagger-ui *, .swagger-ui *::before, .swagger-ui *::after {
            box-sizing: border-box !important;
        }

        /* ALL tables - fixed layout */
        .swagger-ui table {
            table-layout: fixed !important;
            max-width: 100% !important;
            width: 100% !important;
            overflow: hidden !important;
        }

        .swagger-ui table thead,
        .swagger-ui table tbody,
        .swagger-ui table tfoot,
        .swagger-ui table tr,
        .swagger-ui table th,
        .swagger-ui table td {
            max-width: 100% !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        /* Code blocks - prevent overflow */
        .swagger-ui code,
        .swagger-ui pre,
        .swagger-ui .highlight-code {
            max-width: 100% !important;
            overflow: hidden !important;
            word-wrap: break-word !important;
            white-space: pre-wrap !important;
        }

        /* Info section */
        .swagger-ui .info {
            margin: 20px 0 !important;
            padding: 20px !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }

        .swagger-ui .info .title {
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        .swagger-ui .info .description {
            max-width: 100%;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        /* Operation blocks */
        .swagger-ui .opblock {
            border-radius: 4px !important;
            margin-bottom: 10px !important;
            overflow: hidden !important;
            max-width: 100% !important;
        }

        .swagger-ui .opblock-summary {
            padding: 10px 20px !important;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .swagger-ui .opblock-summary-description {
            max-width: 100%;
            word-wrap: break-word;
            overflow-wrap: break-word;
            flex: 1;
            min-width: 0;
        }

        /* Parameters */
        .swagger-ui .parameters {
            max-width: 100% !important;
            table-layout: fixed !important;
            overflow: hidden !important;
        }

        .swagger-ui .parameters .parameter-name {
            max-width: 180px !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
        }

        /* Responses */
        .swagger-ui .responses-wrapper {
            max-width: 100% !important;
            overflow: hidden !important;
        }

        .swagger-ui .response {
            max-width: 100% !important;
            overflow: hidden !important;
        }

        /* Model box */
        .swagger-ui .model-box {
            max-width: 100% !important;
            overflow: hidden !important;
            overflow-y: auto !important;
            max-height: 400px;
        }

        .swagger-ui .model-title {
            word-wrap: break-word !important;
            overflow: hidden !important;
        }

        .swagger-ui .property-name {
            max-width: 180px !important;
            word-break: break-word !important;
        }

        /* Inputs */
        .swagger-ui textarea,
        .swagger-ui input[type="text"],
        .swagger-ui select {
            max-width: 100% !important;
            word-wrap: break-word !important;
        }

        /* Execute wrapper */
        .swagger-ui .execute-wrapper {
            padding: 20px !important;
            overflow: hidden !important;
            max-width: 100% !important;
        }

        /* Scrollbar styling */
        .swagger-ui::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .swagger-ui::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .swagger-ui::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if (!empty($logo_url)): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <h1><?php echo esc_html(isset($title) ? $title : 'API Documentation'); ?></h1>
        </div>
        <div class="site-name"><?php echo esc_html(isset($site_name) ? $site_name : get_bloginfo('name')); ?></div>
    </div>

    <div id="loading">
        <div class="spinner"></div>
        <p style="margin-top: 20px; color: #666;">Loading API Documentation...</p>
    </div>

    <div id="error">
        <div class="error-details">
            <h2>Error Loading Documentation</h2>
            <p id="error-message"></p>
            <pre id="error-details"></pre>
            <button class="retry-btn" onclick="location.reload()">Retry</button>
        </div>
    </div>

    <div id="swagger-ui" style="display: none;"></div>

    <script>
        // Configuration
        const CONFIG = {
            specUrl: <?php echo wp_json_encode(isset($specUrl) ? $specUrl : ''); ?>,
            timeout: 30000,
            debug: true
        };

        function log(...args) {
            if (CONFIG.debug) {
                console.log('[Swagger UI]', ...args);
            }
        }

        function error(...args) {
            console.error('[Swagger UI]', ...args);
        }

        // Fetch with timeout
        function fetchWithTimeout(url, timeout = CONFIG.timeout) {
            log('Fetching spec from:', url);

            return Promise.race([
                fetch(url),
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Request timeout after ' + timeout + 'ms')), timeout)
                )
            ]);
        }

        // Load spec and initialize Swagger UI
        async function initSwaggerUI() {
            const loading = document.getElementById('loading');
            const errorDiv = document.getElementById('error');
            const swaggerUi = document.getElementById('swagger-ui');
            const errorMessage = document.getElementById('error-message');
            const errorDetails = document.getElementById('error-details');

            try {
                // Check if SwaggerUIBundle is available
                if (typeof SwaggerUIBundle === 'undefined') {
                    throw new Error('SwaggerUIBundle is not loaded. Please check your internet connection.');
                }

                log('SwaggerUIBundle loaded, fetching spec...');

                // Fetch the spec
                const response = await fetchWithTimeout(CONFIG.specUrl);
                log('Response received:', response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const text = await response.text();
                log('Spec text length:', text.length);

                // Parse and validate spec
                let spec;
                try {
                    spec = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON: ' + e.message);
                }

                if (!spec.openapi) {
                    throw new Error('Invalid OpenAPI spec: missing openapi version');
                }

                log('Spec validated successfully');
                log('Spec info:', spec.info);
                log('Available paths:', Object.keys(spec.paths || {}));

                // Hide loading, show Swagger UI
                loading.style.display = 'none';
                swaggerUi.style.display = 'block';

                // Initialize Swagger UI
                log('Initializing Swagger UI...');
                const ui = SwaggerUIBundle({
                    url: CONFIG.specUrl,
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
                    },
                    displayRequestDuration: true,
                    displayOperationId: false,
                    showRequestHeaders: true,
                    validatorUrl: null,
                    responseInterceptor: function(response) {
                        log('API Response:', response);
                    },
                    requestInterceptor: function(request) {
                        log('API Request:', request);
                        return request;
                    },
                    onComplete: function() {
                        log('Swagger UI loaded successfully!');
                    },
                    onFailure: function(err) {
                        error('Swagger UI initialization failed:', err);
                    }
                });

                window.ui = ui;
                log('Initialization complete');

            } catch (err) {
                error('Initialization error:', err);

                // Show error
                loading.style.display = 'none';
                errorDiv.style.display = 'block';
                errorMessage.textContent = err.message;
                errorDetails.textContent = err.stack || err.toString();
            }
        }

        // Wait for DOM and scripts to load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSwaggerUI);
        } else {
            initSwaggerUI();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
</body>
</html>
