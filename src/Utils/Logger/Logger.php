<?php
/**
 * Logger
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Utils\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Core\Container;

/**
 * Logger class for plugin logging.
 *
 * @since 1.0.0
 */
class Logger implements ServiceInterface
{
    /**
     * Log levels.
     *
     * @since 1.0.0
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';

    /**
     * Log level priorities.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $levels = array(
        'debug' => 100,
        'info' => 200,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
    );

    /**
     * Current minimum log level.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $minLevel;

    /**
     * Whether logging is enabled.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Log retention days.
     *
     * @since 1.0.0
     *
     * @var int
     */
    protected $retentionDays;

    /**
     * Create a new Logger instance.
     *
     * @since 1.0.0
     *
     * @param ?Container $container Optional DI container.
     */
    public function __construct(?Container $container = null)
    {
        // Container is not used but accepted for service provider compatibility
        $this->enabled = wp_api_codeia_config('logging.enabled', true);
        $this->minLevel = wp_api_codeia_config('logging.level', 'warning');
        $this->retentionDays = wp_api_codeia_config('logging.retention_days', 30);
    }

    /**
     * Register the logger service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Register log hooks if needed
        if ($this->enabled) {
            add_action('wp_api_codeia_log', array($this, 'logHook'), 10, 2);
        }
    }

    /**
     * Boot the logger service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // Set up scheduled cleanup
        if ($this->enabled && !wp_next_scheduled('wp_api_codeia_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'wp_api_codeia_cleanup_logs');
        }
    }

    /**
     * Log a debug message.
     *
     * @since 1.0.0
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @since 1.0.0
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @since 1.0.0
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @since 1.0.0
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log a critical message.
     *
     * @since 1.0.0
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log a message.
     *
     * @since 1.0.0
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->shouldLog($level)) {
            return;
        }

        $entry = $this->formatLogEntry($level, $message, $context);
        $this->write($entry);
    }

    /**
     * Log via WordPress hook.
     *
     * @since 1.0.0
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @return void
     */
    public function logHook($level, $message)
    {
        $this->log($level, $message);
    }

    /**
     * Check if a log level should be logged.
     *
     * @since 1.0.0
     *
     * @param string $level Log level.
     * @return bool True if should log, false otherwise.
     */
    protected function shouldLog($level)
    {
        if (!isset($this->levels[$level])) {
            return false;
        }

        return $this->levels[$level] >= $this->levels[$this->minLevel];
    }

    /**
     * Format a log entry.
     *
     * @since 1.0.0
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return string Formatted log entry.
     */
    protected function formatLogEntry($level, $message, array $context)
    {
        $timestamp = current_time('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';

        return sprintf(
            "[%s] %s: %s%s",
            $timestamp,
            strtoupper($level),
            $message,
            $contextStr
        );
    }

    /**
     * Write log entry.
     *
     * @since 1.0.0
     *
     * @param string $entry Formatted log entry.
     * @return void
     */
    protected function write($entry)
    {
        // Log to error log (configured by WordPress)
        error_log('[WP API Codeia] ' . $entry);

        // Also store in database if enabled
        $this->storeInDatabase($entry);
    }

    /**
     * Store log entry in database.
     *
     * @since 1.0.0
     *
     * @param string $entry Formatted log entry.
     * @return void
     */
    protected function storeInDatabase($entry)
    {
        $logToDatabase = wp_api_codeia_config('logging.log_to_database', false);

        if (!$logToDatabase) {
            return;
        }

        // Get or create log post type (optional, for future implementation)
        // For now, we'll use a simple option to store logs
        $logs = get_option('wp_api_codeia_logs', array());
        $logs[] = array(
            'entry' => $entry,
            'timestamp' => current_time('mysql'),
        );

        // Keep only last N logs based on retention
        $maxLogs = apply_filters('wp_api_codeia_max_logs', 1000);
        if (count($logs) > $maxLogs) {
            $logs = array_slice($logs, -$maxLogs);
        }

        update_option('wp_api_codeia_logs', $logs);
    }

    /**
     * Get all logs from database.
     *
     * @since 1.0.0
     *
     * @param int   $limit Number of logs to retrieve.
     * @param int   $offset Offset for pagination.
     * @return array Array of log entries.
     */
    public function getLogs($limit = 100, $offset = 0)
    {
        $logs = get_option('wp_api_codeia_logs', array());

        return array_slice(array_reverse($logs), $offset, $limit);
    }

    /**
     * Clear all logs.
     *
     * @since 1.0.0
     *
     * @return int Number of logs cleared.
     */
    public function clearLogs()
    {
        $logs = get_option('wp_api_codeia_logs', array());
        $count = count($logs);

        delete_option('wp_api_codeia_logs');

        return $count;
    }

    /**
     * Clean up old logs.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function cleanup()
    {
        $logs = get_option('wp_api_codeia_logs', array());
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$this->retentionDays} days"));

        $logs = array_filter($logs, function ($log) use ($cutoff) {
            return isset($log['timestamp']) && $log['timestamp'] > $cutoff;
        });

        update_option('wp_api_codeia_logs', array_values($logs));
    }

    /**
     * Get the current minimum log level.
     *
     * @since 1.0.0
     *
     * @return string Minimum log level.
     */
    public function getMinLevel()
    {
        return $this->minLevel;
    }

    /**
     * Set the minimum log level.
     *
     * @since 1.0.0
     *
     * @param string $level Minimum log level.
     * @return void
     */
    public function setMinLevel($level)
    {
        if (isset($this->levels[$level])) {
            $this->minLevel = $level;
        }
    }

    /**
     * Enable or disable logging.
     *
     * @since 1.0.0
     *
     * @param bool $enabled Whether logging should be enabled.
     * @return void
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if logging is enabled.
     *
     * @since 1.0.0
     *
     * @return bool True if enabled, false otherwise.
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
