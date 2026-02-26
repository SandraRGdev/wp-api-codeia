<?php
/**
 * Endpoints Test Script
 *
 * This script tests all the documentation endpoints to verify they work correctly.
 * Run with: php tests/endpoints-test.php
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    // If running from command line, bootstrap WordPress
    if (php_sapi_name() === 'cli') {
        $wpLoadPath = dirname(dirname(dirname(dirname(__DIR__)))) . '/wp-load.php';

        if (file_exists($wpLoadPath)) {
            require_once $wpLoadPath;
        } else {
            echo "ERROR: Could not find wp-load.php\n";
            echo "Looking for: $wpLoadPath\n";
            exit(1);
        }
    } else {
        exit;
    }
}

echo "=== WP API Codeia - Endpoint Tests ===\n\n";

// Define namespace constant if not already defined
if (!defined('WP_API_CODEIA_API_NAMESPACE')) {
    define('WP_API_CODEIA_API_NAMESPACE', 'wp-custom-api');
}

/**
 * Test a single endpoint
 */
function testEndpoint($slug, $expectedKeys = array()) {
    $url = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/' . $slug);

    echo "Testing: /v1/$slug\n";
    echo "URL: $url\n";

    $response = wp_remote_get($url, array(
        'timeout' => 30,
        'headers' => array(
            'Accept' => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        echo "  ❌ ERROR: " . $response->get_error_message() . "\n";
        return false;
    }

    $statusCode = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    echo "  Status: $statusCode\n";

    if ($statusCode !== 200) {
        echo "  ❌ FAILED: Expected 200, got $statusCode\n";
        echo "  Body: $body\n";
        return false;
    }

    // Try to decode JSON
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "  ❌ FAILED: Invalid JSON - " . json_last_error_msg() . "\n";
        echo "  Body: $body\n";
        return false;
    }

    echo "  ✅ JSON valid\n";

    // Check expected keys
    if (!empty($expectedKeys)) {
        $missing = array_diff($expectedKeys, array_keys($data));
        if (!empty($missing)) {
            echo "  ⚠️  WARNING: Missing keys: " . implode(', ', $missing) . "\n";
        } else {
            echo "  ✅ All expected keys present\n";
        }
    }

    // Show some info
    if (isset($data['openapi'])) {
        echo "  OpenAPI Version: " . $data['openapi'] . "\n";
    }
    if (isset($data['info']['title'])) {
        echo "  Title: " . $data['info']['title'] . "\n";
    }
    if (isset($data['paths'])) {
        echo "  Paths: " . count($data['paths']) . "\n";
    }

    echo "\n";
    return true;
}

echo "--- Testing Diagnostic Endpoints ---\n\n";

$results = array();

// Test 1: Basic test endpoint
$results['test'] = testEndpoint('test', array('status', 'message', 'namespace', 'rest_url'));

// Test 2: Simple docs endpoint
$results['docs/simple'] = testEndpoint('docs/simple', array('openapi', 'info', 'servers', 'paths'));

// Test 3: Minimal docs endpoint
$results['docs/minimal'] = testEndpoint('docs/minimal', array('openapi', 'info', 'servers', 'paths'));

// Test 4: Full docs endpoint
$results['docs'] = testEndpoint('docs', array('openapi', 'info', 'servers', 'paths', 'components'));

// Test 5: Full docs with refresh
echo "--- Testing Refresh Endpoint ---\n\n";
$refreshUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs?refresh=1');
echo "Testing: /v1/docs?refresh=1\n";
echo "URL: $refreshUrl\n";

$response = wp_remote_get($refreshUrl, array(
    'timeout' => 30,
    'headers' => array(
        'Accept' => 'application/json',
    ),
));

if (is_wp_error($response)) {
    echo "  ❌ ERROR: " . $response->get_error_message() . "\n";
    $results['docs-refresh'] = false;
} else {
    $statusCode = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    echo "  Status: $statusCode\n";

    if ($statusCode === 200) {
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "  ✅ JSON valid\n";
            if (isset($data['paths'])) {
                echo "  Paths: " . count($data['paths']) . " (should include post types)\n";
            }
            $results['docs-refresh'] = true;
        } else {
            echo "  ❌ Invalid JSON\n";
            $results['docs-refresh'] = false;
        }
    } else {
        echo "  ❌ FAILED: Expected 200, got $statusCode\n";
        $results['docs-refresh'] = false;
    }
    echo "\n";
}

// Summary
echo "--- Summary ---\n\n";
$passed = 0;
$failed = 0;

foreach ($results as $name => $result) {
    if ($result) {
        echo "  ✅ /v1/$name\n";
        $passed++;
    } else {
        echo "  ❌ /v1/$name\n";
        $failed++;
    }
}

echo "\nTotal: $passed passed, $failed failed\n";

if ($failed > 0) {
    echo "\n⚠️  Some tests failed. Check the output above for details.\n";
    exit(1);
} else {
    echo "\n✅ All tests passed!\n";
    exit(0);
}
