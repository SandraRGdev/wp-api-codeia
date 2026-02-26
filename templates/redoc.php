<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(isset($title) ? $title : 'API Documentation'); ?> - <?php bloginfo('name'); ?></title>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/redoc@latest/bundles/redoc.min.css">
    <style>
        /* ============================================
           BASE - PREVENT ALL HORIZONTAL OVERFLOW
        ============================================ */
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
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
            border-top: 4px solid #e63946;
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
            max-width: 800px;
            margin: 20px auto;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .retry-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background: #e63946;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .retry-btn:hover { background: #c62828; }

        /* ============================================
           REDOC SPECIFIC OVERRIDES FOR OVERFLOW
        ============================================ */
        #redoc-container {
            overflow-x: hidden !important;
            max-width: 100% !important;
        }

        /* ALL tables - fixed layout */
        #redoc-container table {
            table-layout: fixed !important;
            max-width: 100% !important;
            width: 100% !important;
            overflow: hidden !important;
        }

        #redoc-container table thead,
        #redoc-container table tbody,
        #redoc-container table tfoot,
        #redoc-container table tr,
        #redoc-container table th,
        #redoc-container table td {
            max-width: 100% !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
        }

        /* Code blocks - prevent overflow */
        #redoc-container code,
        #redoc-container pre {
            max-width: 100% !important;
            overflow: hidden !important;
            word-wrap: break-word !important;
            white-space: pre-wrap !important;
        }

        /* Scrollbar styling */
        #redoc-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        #redoc-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #redoc-container::-webkit-scrollbar-thumb {
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

    <div id="redoc-container" style="display: none;"></div>

    <script>
        // Configuration
        const CONFIG = {
            specUrl: <?php echo wp_json_encode(isset($specUrl) ? $specUrl : ''); ?>,
            timeout: 30000,
            debug: true
        };

        function log(...args) {
            if (CONFIG.debug) {
                console.log('[ReDoc]', ...args);
            }
        }

        function error(...args) {
            console.error('[ReDoc]', ...args);
        }

        // Initialize ReDoc
        async function initReDoc() {
            const loading = document.getElementById('loading');
            const errorDiv = document.getElementById('error');
            const container = document.getElementById('redoc-container');
            const errorMessage = document.getElementById('error-message');
            const errorDetails = document.getElementById('error-details');

            try {
                log('Initializing ReDoc...');
                log('Spec URL:', CONFIG.specUrl);

                // Check if Redoc is available
                if (typeof Redoc === 'undefined') {
                    throw new Error('ReDoc library is not loaded. Please check your internet connection.');
                }

                // Hide loading, show container
                loading.style.display = 'none';
                container.style.display = 'block';

                log('Redoc loaded, initializing...');

                // Initialize ReDoc
                Redoc.init(
                    CONFIG.specUrl,
                    {
                        theme: {
                            colors: {
                                primary: {
                                    main: '#e63946'
                                },
                                http: {
                                    get: '#61affe',
                                    post: '#49cc90',
                                    put: '#fca130',
                                    delete: '#f93e3e',
                                    options: '#9013fe',
                                    head: '#4d99db',
                                    patch: '#50e3c2'
                                }
                            },
                            typography: {
                                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                                fontSize: '14px',
                                lineHeight: '1.5'
                            }
                        },
                        scrollYOffset: 60,
                        hideDownloadButton: false,
                        disableSearch: false,
                        expandSingleSpecField: true,
                        requiredPropsFirst: true,
                        sortEnumValuesAlphabetically: true,
                        showObjectSchemaExamples: true
                    },
                    container,
                    function() {
                        log('ReDoc loaded successfully!');
                    },
                    function(err) {
                        error('ReDoc error:', err);
                        throw err;
                    }
                );

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
        function waitForRedoc(callback, timeout = 15000) {
            const startTime = Date.now();

            function check() {
                if (typeof Redoc !== 'undefined') {
                    log('ReDoc library loaded');
                    callback(true);
                } else if (Date.now() - startTime > timeout) {
                    error('Timeout waiting for ReDoc library');
                    callback(false);
                } else {
                    setTimeout(check, 100);
                }
            }

            check();
        }

        // Start initialization
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                waitForRedoc((loaded) => {
                    if (loaded) {
                        initReDoc();
                    } else {
                        const loading = document.getElementById('loading');
                        const errorDiv = document.getElementById('error');
                        loading.style.display = 'none';
                        errorDiv.style.display = 'block';
                        document.getElementById('error-message').textContent = 'Failed to load ReDoc library from CDN';
                    }
                });
            });
        } else {
            waitForRedoc((loaded) => {
                if (loaded) {
                    initReDoc();
                } else {
                    const loading = document.getElementById('loading');
                    const errorDiv = document.getElementById('error');
                    loading.style.display = 'none';
                    errorDiv.style.display = 'block';
                    document.getElementById('error-message').textContent = 'Failed to load ReDoc library from CDN';
                }
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/redoc@latest/bundles/redoc.standalone.js"></script>
</body>
</html>
