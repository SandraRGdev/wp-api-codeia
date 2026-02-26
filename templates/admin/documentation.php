<?php
/**
 * Admin Documentation Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$specUrlWithRefresh = add_query_arg(array('refresh' => '1'), $specUrl);
$testUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/test');
$minimalDocsUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs/minimal');
$simpleDocsUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs/simple');
?>
<style>
    /* ============================================
       BASE WRAPPER - PREVENT ALL HORIZONTAL OVERFLOW
    ============================================ */
    .wrap {
        max-width: 100% !important;
        overflow-x: hidden !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    /* ============================================
       TABLE STYLES
    ============================================ */
    .wp-list-table {
        table-layout: fixed !important;
    }

    .wp-list-table th,
    .wp-list-table td {
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        hyphens: auto;
    }

    .wp-list-table code {
        display: inline-block;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
        background: #f0f0f1;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }

    .wp-list-table .button {
        white-space: nowrap;
        text-decoration: none;
    }

    .docs-url-cell {
        max-width: 500px;
    }

    .docs-url-cell a {
        display: block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 12px;
    }

    .docs-url-cell .button-group {
        margin-top: 10px;
        white-space: nowrap;
    }

    /* ============================================
       LOADING STATE
    ============================================ */
    #swagger-loading {
        text-align: center;
        padding: 60px 40px;
        background: #f9f9f9;
        border-radius: 4px;
        border: 1px solid #ddd;
        margin: 20px 0;
    }

    #swagger-loading .spinner {
        float: none;
        margin: 0 auto 15px;
    }

    /* ============================================
       SWAGGER UI CONTAINER - FORCE NO OVERFLOW
    ============================================ */
    #swagger-ui-container {
        height: 750px !important;
        min-height: 750px;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 4px;
        margin: 20px 0;
        display: none;
        overflow: hidden !important;
        position: relative;
        max-width: 100% !important;
    }

    /* Force ALL children to stay within bounds */
    #swagger-ui-container,
    #swagger-ui-container *,
    #swagger-ui-container *::before,
    #swagger-ui-container *::after {
        box-sizing: border-box !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
    }

    /* Swagger UI root container */
    #swagger-ui-container > div {
        height: 100% !important;
        width: 100% !important;
        overflow: hidden !important;
        max-width: 100% !important;
    }

    #swagger-ui-container .swagger-ui {
        height: 100% !important;
        width: 100% !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
        max-width: 100% !important;
    }

    /* ALL tables in Swagger UI - fixed layout, no overflow */
    #swagger-ui-container table {
        table-layout: fixed !important;
        max-width: 100% !important;
        width: 100% !important;
        overflow: hidden !important;
    }

    #swagger-ui-container table thead,
    #swagger-ui-container table tbody,
    #swagger-ui-container table tfoot,
    #swagger-ui-container table tr,
    #swagger-ui-container table th,
    #swagger-ui-container table td {
        max-width: 100% !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }

    /* Code blocks - prevent overflow */
    #swagger-ui-container code,
    #swagger-ui-container pre,
    #swagger-ui-container .highlight-code {
        max-width: 100% !important;
        overflow: hidden !important;
        word-wrap: break-word !important;
        white-space: pre-wrap !important;
    }

    /* ============================================
       SWAGGER UI SPECIFIC SECTIONS
    ============================================ */
    #swagger-ui-container .topbar {
        padding: 8px 20px !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .topbar-wrapper {
        padding: 0 !important;
    }

    #swagger-ui-container .link {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #swagger-ui-container .info {
        margin: 20px 0 !important;
        padding: 20px !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .info .title {
        font-size: 32px !important;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    #swagger-ui-container .info .description {
        max-width: 100%;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    #swagger-ui-container .scheme-container {
        margin: 0 !important;
        padding: 20px !important;
        box-shadow: 0 1px 2px 0 rgba(0,0,0,.05) !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .schemes > label {
        max-width: 100%;
        word-wrap: break-word;
    }

    #swagger-ui-container .opblock {
        border-radius: 4px !important;
        margin-bottom: 10px !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .opblock-tag {
        padding: 8px 20px !important;
    }

    #swagger-ui-container .opblock-summary {
        padding: 10px 20px !important;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    #swagger-ui-container .opblock-summary-description {
        max-width: 100%;
        word-wrap: break-word;
        overflow-wrap: break-word;
        flex: 1;
        min-width: 0;
    }

    #swagger-ui-container .opblock-description-wrapper,
    #swagger-ui-container .opblock-body {
        padding: 15px 20px !important;
        max-width: 100%;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .opblock-description {
        max-width: 100%;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    #swagger-ui-container .opblock-section-header {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 8px 20px;
    }

    #swagger-ui-container .parameters {
        max-width: 100% !important;
        table-layout: fixed !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .parameters th,
    #swagger-ui-container .parameters td {
        max-width: 100% !important;
        overflow-wrap: break-word !important;
        word-break: break-word !important;
        padding: 10px !important;
        vertical-align: top;
        overflow: hidden !important;
    }

    #swagger-ui-container .parameters .parameter-name {
        max-width: 180px !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .parameters .parameter-source {
        max-width: 120px !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .responses-wrapper {
        max-width: 100% !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .response {
        max-width: 100% !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .response-col_status {
        min-width: 80px !important;
        max-width: 100px !important;
    }

    #swagger-ui-container .response-col_links {
        min-width: 100px !important;
    }

    #swagger-ui-container .response-col_description {
        max-width: 100% !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
        flex: 1;
        min-width: 0;
        overflow: hidden !important;
    }

    #swagger-ui-container .response-control-media-type {
        max-width: 200px !important;
    }

    #swagger-ui-container .response .responses-inner {
        max-width: 100% !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .model-box {
        max-width: 100% !important;
        overflow: hidden !important;
        overflow-y: auto !important;
        max-height: 400px;
    }

    #swagger-ui-container .model-title {
        word-wrap: break-word;
        overflow: hidden;
    }

    #swagger-ui-container .property-row {
        max-width: 100% !important;
        overflow-wrap: break-word !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .property-name {
        max-width: 180px !important;
        word-break: break-word !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .property-type {
        max-width: 120px !important;
        overflow: hidden !important;
    }

    /* Try it out section */
    #swagger-ui-container .execute-wrapper {
        padding: 20px !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }

    #swagger-ui-container .body-param-input {
        max-width: 100%;
        width: 100%;
        box-sizing: border-box;
    }

    #swagger-ui-container textarea {
        max-width: 100% !important;
        word-wrap: break-word !important;
    }

    #swagger-ui-container input[type="text"] {
        max-width: 100% !important;
    }

    #swagger-ui-container select {
        max-width: 100% !important;
    }

    /* Buttons */
    #swagger-ui-container .btn {
        white-space: normal !important;
        word-wrap: break-word;
    }

    /* ============================================
       ERROR STATE
    ============================================ */
    #swagger-error {
        display: none;
        padding: 30px;
        margin: 20px 0;
        background: #fff;
        border-left: 4px solid #dc3232;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    #swagger-error strong {
        display: block;
        margin-bottom: 15px;
        color: #dc3232;
        font-size: 16px;
    }

    #swagger-error p {
        margin: 10px 0;
    }

    #swagger-error .button {
        margin-right: 10px;
        margin-bottom: 10px;
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media screen and (max-width: 1200px) {
        .docs-url-cell {
            max-width: 350px;
        }
    }

    @media screen and (max-width: 782px) {
        #swagger-ui-container {
            height: 500px !important;
            min-height: 500px;
        }

        .wp-list-table code {
            max-width: 100px;
            font-size: 11px;
        }

        .docs-url-cell {
            max-width: 200px;
        }

        #swagger-ui-container .info .title {
            font-size: 24px !important;
        }

        #swagger-ui-container .opblock-summary {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* Scrollbar */
    #swagger-ui-container .swagger-ui::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    #swagger-ui-container .swagger-ui::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #swagger-ui-container .swagger-ui::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    #swagger-ui-container .swagger-ui::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>

<div class="wrap">
    <h1><?php esc_html_e('API Documentation', 'wp-api-codeia'); ?></h1>
    <p><?php esc_html_e('View and interact with the API documentation.', 'wp-api-codeia'); ?></p>

    <h2><?php esc_html_e('Diagnostic Endpoints', 'wp-api-codeia'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 150px;"><?php esc_html_e('Endpoint', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Description', 'wp-api-codeia'); ?></th>
                <th style="width: 120px;"><?php esc_html_e('Status', 'wp-api-codeia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>/v1/test</code></td>
                <td><?php esc_html_e('Basic API test', 'wp-api-codeia'); ?></td>
                <td><a href="<?php echo esc_url($testUrl); ?>" target="_blank" class="button button-small"><?php esc_html_e('Test', 'wp-api-codeia'); ?></a></td>
            </tr>
            <tr>
                <td><code>/v1/docs/simple</code></td>
                <td><?php esc_html_e('Simple OpenAPI spec (no dependencies)', 'wp-api-codeia'); ?></td>
                <td><a href="<?php echo esc_url($simpleDocsUrl); ?>" target="_blank" class="button button-small"><?php esc_html_e('View', 'wp-api-codeia'); ?></a></td>
            </tr>
            <tr>
                <td><code>/v1/docs/minimal</code></td>
                <td><?php esc_html_e('Minimal OpenAPI spec', 'wp-api-codeia'); ?></td>
                <td><a href="<?php echo esc_url($minimalDocsUrl); ?>" target="_blank" class="button button-small"><?php esc_html_e('View', 'wp-api-codeia'); ?></a></td>
            </tr>
        </tbody>
    </table>

    <h2><?php esc_html_e('Documentation Links', 'wp-api-codeia'); ?></h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 150px;"><?php esc_html_e('Format', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('URL', 'wp-api-codeia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>OpenAPI JSON</strong></td>
                <td class="docs-url-cell">
                    <div>
                        <a href="<?php echo esc_url($specUrl); ?>" target="_blank"><?php echo esc_html($specUrl); ?></a>
                    </div>
                    <div class="button-group">
                        <button type="button" id="refresh-cache-btn" class="button button-small"><?php esc_html_e('Refresh Cache', 'wp-api-codeia'); ?></button>
                    </div>
                </td>
            </tr>
            <tr>
                <td><strong>Swagger UI</strong></td>
                <td class="docs-url-cell">
                    <a href="<?php echo esc_url($swaggerUrl); ?>" target="_blank"><?php echo esc_html($swaggerUrl); ?></a>
                </td>
            </tr>
            <tr>
                <td><strong>ReDoc</strong></td>
                <td class="docs-url-cell">
                    <a href="<?php echo esc_url($redocUrl); ?>" target="_blank"><?php echo esc_html($redocUrl); ?></a>
                </td>
            </tr>
        </tbody>
    </table>

    <h2><?php esc_html_e('Shortcodes', 'wp-api-codeia'); ?></h2>
    <p><code>[codeia_api_docs]</code> - <?php esc_html_e('Embed Swagger UI', 'wp-api-codeia'); ?></p>
    <p><code>[codeia_api_redoc]</code> - <?php esc_html_e('Embed ReDoc', 'wp-api-codeia'); ?></p>

    <hr style="margin: 30px 0;">

    <h2><?php esc_html_e('Interactive Documentation (Swagger UI)', 'wp-api-codeia'); ?></h2>
    <p><?php esc_html_e('Test your API endpoints directly from this page.', 'wp-api-codeia'); ?></p>

    <div id="swagger-loading">
        <span class="spinner is-active"></span>
        <p><?php esc_html_e('Loading documentation...', 'wp-api-codeia'); ?></p>
    </div>

    <div id="swagger-ui-container"></div>

    <div id="swagger-error" style="display: none;">
        <strong><?php esc_html_e('Error loading documentation:', 'wp-api-codeia'); ?></strong>
        <p id="error-message"></p>
        <p>
            <a href="<?php echo esc_url($testUrl); ?>" target="_blank" class="button"><?php esc_html_e('Test API Connection', 'wp-api-codeia'); ?></a>
            <a href="<?php echo esc_url($minimalDocsUrl); ?>" target="_blank" class="button"><?php esc_html_e('Try Minimal Spec', 'wp-api-codeia'); ?></a>
            <button type="button" id="retry-btn" class="button"><?php esc_html_e('Retry', 'wp-api-codeia'); ?></button>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Swagger UI] DOM loaded, initializing...');

    const specUrl = <?php echo wp_json_encode($specUrl); ?>;
    const specUrlWithRefresh = <?php echo wp_json_encode($specUrlWithRefresh); ?>;
    const simpleSpecUrl = <?php echo wp_json_encode($simpleDocsUrl); ?>;
    const container = document.getElementById('swagger-ui-container');
    const loading = document.getElementById('swagger-loading');
    const errorDiv = document.getElementById('swagger-error');
    const errorMessage = document.getElementById('error-message');
    const refreshBtn = document.getElementById('refresh-cache-btn');
    const retryBtn = document.getElementById('retry-btn');

    let swaggerBundleLoaded = false;
    let maxRetries = 3;
    let currentRetry = 0;

    // Wait for SwaggerUIBundle
    function waitForSwaggerUI(callback, timeout = 10000) {
        const startTime = Date.now();

        function check() {
            if (window.SwaggerUIBundle) {
                console.log('[Swagger UI] SwaggerUIBundle loaded');
                swaggerBundleLoaded = true;
                callback(true);
            } else if (Date.now() - startTime > timeout) {
                console.error('[Swagger UI] Timeout waiting for SwaggerUIBundle');
                callback(false);
            } else {
                setTimeout(check, 100);
            }
        }

        check();
    }

    // Function to load Swagger UI
    function loadSwaggerUI(url) {
        console.log('[Swagger UI] Loading with spec:', url);

        if (!swaggerBundleLoaded || !window.SwaggerUIBundle) {
            console.error('[Swagger UI] SwaggerUIBundle not available');
            showError('Swagger UI library not loaded. Please refresh the page.');
            return;
        }

        loading.style.display = 'none';
        errorDiv.style.display = 'none';
        container.style.display = 'block';

        container.innerHTML = '';

        try {
            const ui = SwaggerUIBundle({
                url: url,
                domNode: container,
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "BaseLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: "list",
                tryItOutEnabled: true,
                validatorUrl: null,
                displayRequestDuration: true,
                displayOperationId: false,
                filter: true,
                showRequestHeaders: true,
                persistAuthorization: true,
                syntaxHighlight: {
                    activate: true,
                    theme: "monokai"
                },
                onComplete: function() {
                    console.log('[Swagger UI] Loaded successfully');

                    // Apply layout fixes after load
                    setTimeout(function() {
                        const tables = container.querySelectorAll('table');
                        tables.forEach(function(table) {
                            table.style.tableLayout = 'fixed';
                            const cells = table.querySelectorAll('td, th');
                            cells.forEach(function(cell) {
                                cell.style.maxWidth = '100%';
                                cell.style.wordWrap = 'break-word';
                            });
                        });
                    }, 100);
                }
            });

            window.ui = ui;
        } catch (error) {
            console.error('[Swagger UI] Error:', error);
            showError('Failed to initialize: ' + error.message);
        }
    }

    // Fetch with timeout
    function fetchWithTimeout(url, timeout = 30000) {
        return Promise.race([
            fetch(url),
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Timeout')), timeout)
            )
        ]);
    }

    // Load spec
    function loadSpec(url, isFallback = false) {
        console.log('[Swagger UI] Fetching spec:', url);

        fetchWithTimeout(url, isFallback ? 15000 : 30000)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                const spec = JSON.parse(text);
                if (!spec.openapi) {
                    throw new Error('Missing openapi version');
                }
                loadSwaggerUI(url);
            })
            .catch(error => {
                console.error('[Swagger UI] Error:', error);

                if (!isFallback && currentRetry < maxRetries) {
                    currentRetry++;
                    loadSpec(simpleSpecUrl, true);
                } else {
                    showError('Cannot load API specification.<br><br>Error: ' + error.message);
                }
            });
    }

    function showError(message) {
        loading.style.display = 'none';
        container.style.display = 'none';
        errorDiv.style.display = 'block';
        errorMessage.innerHTML = message;
    }

    // Event handlers
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentRetry = 0;
            loading.style.display = 'block';
            errorDiv.style.display = 'none';
            container.style.display = 'none';
            loadSpec(specUrlWithRefresh);
        });
    }

    if (retryBtn) {
        retryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentRetry = 0;
            loading.style.display = 'block';
            errorDiv.style.display = 'none';
            container.style.display = 'none';
            initSwaggerUI();
        });
    }

    // Initialize
    function initSwaggerUI() {
        waitForSwaggerUI(function(loaded) {
            if (loaded) {
                loadSpec(specUrl);
            } else {
                showError('Failed to load Swagger UI library from CDN.');
            }
        });
    }

    initSwaggerUI();
});
</script>
