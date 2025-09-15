#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * HashId Bundle - Rector Metrics Collection Tool
 * 
 * This tool runs Rector analysis and collects detailed metrics about the
 * modernization process, including automation rates, rule effectiveness,
 * and time savings calculations.
 */

use Pgs\HashIdBundle\Rector\MetricsCollector;

// Autoload dependencies
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

$autoloadFound = false;
foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    echo "Error: Unable to find autoloader. Please run 'composer install' first.\n";
    exit(1);
}

// Parse command line arguments
$options = getopt('c:o:f:hd', [
    'config:',
    'output:',
    'format:',
    'help',
    'dry-run',
]);

if (isset($options['h']) || isset($options['help'])) {
    showHelp();
    exit(0);
}

$configFile = $options['c'] ?? $options['config'] ?? 'rector.php';
$outputFile = $options['o'] ?? $options['output'] ?? null;
$format = $options['f'] ?? $options['format'] ?? 'markdown';
$isDryRun = isset($options['d']) || isset($options['dry-run']);

// Validate configuration file
if (!file_exists($configFile)) {
    echo "Error: Configuration file '$configFile' not found.\n";
    exit(1);
}

// Initialize metrics collector
$collector = new MetricsCollector();

// Start timing
$collector->startTiming();

// Build Rector command
$rectorCmd = sprintf(
    'vendor/bin/rector process %s --config=%s --output-format=json 2>&1',
    $isDryRun ? '--dry-run' : '',
    escapeshellarg($configFile)
);

echo "HashId Bundle - Rector Metrics Collection\n";
echo "==========================================\n\n";
echo "Configuration: $configFile\n";
echo "Mode: " . ($isDryRun ? 'Dry-run (no changes)' : 'Apply changes') . "\n";
echo "Output format: $format\n\n";
echo "Running Rector analysis...\n\n";

// Execute Rector and capture output
$output = [];
$returnCode = 0;
exec($rectorCmd, $output, $returnCode);

$jsonOutput = implode("\n", $output);

// Try to parse JSON output
$rectorData = json_decode($jsonOutput, true);

if ($rectorData === null && json_last_error() !== JSON_ERROR_NONE) {
    // Fallback: Parse text output for basic metrics
    echo "Note: Unable to parse JSON output, using text parsing fallback.\n\n";
    parseTextOutput($output, $collector);
} else {
    // Process JSON output
    processJsonOutput($rectorData, $collector);
}

// Stop timing
$collector->stopTiming();

// Analyze existing RECTOR-METRICS.md if it exists
$existingMetricsFile = __DIR__ . '/../docs/RECTOR-METRICS.md';
if (file_exists($existingMetricsFile)) {
    analyzeExistingMetrics($existingMetricsFile, $collector);
}

// Generate report based on format
$report = generateReport($collector, $format);

// Output or save report
if ($outputFile) {
    file_put_contents($outputFile, $report);
    echo "\nMetrics report saved to: $outputFile\n";
} else {
    echo "\n$report\n";
}

// Check automation target
if ($collector->meetsAutomationTarget(70.0)) {
    echo "\n✅ SUCCESS: Automation target of 70% has been met!\n";
    exit(0);
} else {
    $metrics = $collector->getMetrics();
    echo sprintf(
        "\n⚠️  WARNING: Automation rate (%.2f%%) is below the 70%% target.\n",
        $metrics['automation_percentage']
    );
    exit(1);
}

function showHelp(): void
{
    echo <<<HELP
HashId Bundle - Rector Metrics Collection Tool

Usage: php bin/rector-metrics.php [OPTIONS]

Options:
  -c, --config FILE      Rector configuration file (default: rector.php)
  -o, --output FILE      Save report to file instead of stdout
  -f, --format FORMAT    Output format: markdown, json, csv, html (default: markdown)
  -d, --dry-run         Run in dry-run mode (no changes applied)
  -h, --help            Show this help message

Examples:
  php bin/rector-metrics.php
  php bin/rector-metrics.php -c rector-php81.php -o metrics.md
  php bin/rector-metrics.php --dry-run --format json
  php bin/rector-metrics.php -c rector-symfony.php -f html -o report.html

HELP;
}

function processJsonOutput(?array $data, MetricsCollector $collector): void
{
    if (!$data) {
        return;
    }

    // Process file changes
    if (isset($data['file_diffs']) && is_array($data['file_diffs'])) {
        foreach ($data['file_diffs'] as $fileDiff) {
            $filePath = $fileDiff['file'] ?? 'unknown';
            $collector->trackFileProcessed($filePath);
            
            if (!empty($fileDiff['applied_rectors'])) {
                $changesCount = count($fileDiff['applied_rectors']);
                $collector->trackFileModified($filePath, $changesCount);
                
                foreach ($fileDiff['applied_rectors'] as $rectorClass) {
                    $collector->trackRuleApplied($rectorClass, $filePath, true);
                }
            }
        }
    }

    // Process errors if any
    if (isset($data['errors']) && is_array($data['errors'])) {
        foreach ($data['errors'] as $error) {
            $collector->trackError(
                $error['file'] ?? 'unknown',
                $error['message'] ?? 'Unknown error'
            );
        }
    }
}

function parseTextOutput(array $output, MetricsCollector $collector): void
{
    $currentFile = null;
    $changesInCurrentFile = 0;
    
    foreach ($output as $line) {
        // Match file processing lines
        if (preg_match('/^\s*(\d+\/\d+)\s+\[.*?\]\s+(.+)$/', $line, $matches)) {
            if ($currentFile && $changesInCurrentFile > 0) {
                $collector->trackFileModified($currentFile, $changesInCurrentFile);
            }
            
            $currentFile = trim($matches[2]);
            $collector->trackFileProcessed($currentFile);
            $changesInCurrentFile = 0;
        }
        
        // Match applied rector rules
        if (preg_match('/applied rule (.+)/', $line, $matches)) {
            $ruleName = trim($matches[1]);
            if ($currentFile) {
                $collector->trackRuleApplied($ruleName, $currentFile, true);
                $changesInCurrentFile++;
            }
        }
        
        // Match change indicators
        if (strpos($line, '-----') !== false || strpos($line, '+++++') !== false) {
            $changesInCurrentFile++;
        }
    }
    
    // Track last file if needed
    if ($currentFile && $changesInCurrentFile > 0) {
        $collector->trackFileModified($currentFile, $changesInCurrentFile);
    }
}

function analyzeExistingMetrics(string $filePath, MetricsCollector $collector): void
{
    $content = file_get_contents($filePath);
    
    // Extract automation rate
    if (preg_match('/Achieved Automation Rate:\s*(\d+)%/', $content, $matches)) {
        $automationRate = (int) $matches[1];
        // Use this to validate our calculations
    }
    
    // Extract manual intervention areas
    if (preg_match('/Manual Intervention Required:\s*(\d+)%/', $content, $matches)) {
        $manualRate = (int) $matches[1];
        // Track areas requiring manual work
        $manualChanges = (int) (($manualRate / 100) * 100); // Estimate based on percentage
        $collector->trackManualIntervention('manual_work', $manualChanges);
    }
}

function generateReport(MetricsCollector $collector, string $format): string
{
    switch (strtolower($format)) {
        case 'json':
            return $collector->exportAsJson();
            
        case 'csv':
            return $collector->exportAsCsv();
            
        case 'html':
            return generateHtmlReport($collector);
            
        case 'markdown':
        default:
            return $collector->exportAsMarkdown();
    }
}

function generateHtmlReport(MetricsCollector $collector): string
{
    $metrics = $collector->getMetrics();
    $summary = $collector->getSummaryStatistics();
    $timeSavings = $collector->getTimeSavingsMetrics();
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rector Metrics Report - HashId Bundle</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #3498db;
        }
        .metric-label {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Rector Metrics Report - HashId Bundle</h1>
    <p>Generated: {date}</p>
    
    <h2>Summary Statistics</h2>
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-value">{total_files}</div>
            <div class="metric-label">Total Files Processed</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{files_modified}</div>
            <div class="metric-label">Files Modified</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{total_changes}</div>
            <div class="metric-label">Total Changes</div>
        </div>
        <div class="metric-card">
            <div class="metric-value">{automation_rate}%</div>
            <div class="metric-label">Automation Rate</div>
        </div>
    </div>
    
    <h2>Automation Target Progress</h2>
    <div class="progress-bar">
        <div class="progress-fill" style="width: {automation_rate}%">
            {automation_rate}% / 70% Target
        </div>
    </div>
    <p class="{target_status_class}">
        {target_status_message}
    </p>
    
    <h2>Time Savings</h2>
    <table>
        <tr>
            <th>Metric</th>
            <th>Value</th>
        </tr>
        <tr>
            <td>Estimated Manual Time</td>
            <td>{manual_hours} hours</td>
        </tr>
        <tr>
            <td>Actual Execution Time</td>
            <td>{actual_minutes} minutes</td>
        </tr>
        <tr>
            <td>Time Saved</td>
            <td class="success">{time_saved_hours} hours ({time_savings_percentage}%)</td>
        </tr>
    </table>
    
    <h2>Rule Effectiveness</h2>
    <table>
        <tr>
            <th>Rule</th>
            <th>Applications</th>
            <th>Success Rate</th>
        </tr>
        {rules_table}
    </table>
</body>
</html>
HTML;
    
    // Replace placeholders
    $replacements = [
        '{date}' => date('Y-m-d H:i:s'),
        '{total_files}' => $summary['total_files'],
        '{files_modified}' => $summary['files_modified'],
        '{total_changes}' => $summary['total_changes'],
        '{automation_rate}' => $summary['automation_rate'],
        '{manual_hours}' => round($timeSavings['estimated_manual_time'] / 3600, 2),
        '{actual_minutes}' => round($timeSavings['actual_time'] / 60, 2),
        '{time_saved_hours}' => $timeSavings['time_saved_hours'],
        '{time_savings_percentage}' => $timeSavings['time_savings_percentage'],
    ];
    
    // Target status
    $targetMet = $collector->meetsAutomationTarget(70.0);
    $replacements['{target_status_class}'] = $targetMet ? 'success' : 'warning';
    $replacements['{target_status_message}'] = $targetMet 
        ? '✅ Automation target of 70% has been met!'
        : '⚠️ Below the 70% automation target';
    
    // Build rules table
    $rulesTable = '';
    foreach ($collector->getRuleEffectivenessReport() as $rule) {
        $ruleName = basename(str_replace('\\', '/', $rule['rule_name']));
        $successClass = $rule['success_rate'] >= 80 ? 'success' : ($rule['success_rate'] >= 50 ? 'warning' : 'error');
        $rulesTable .= sprintf(
            "<tr><td>%s</td><td>%d</td><td class='%s'>%.1f%%</td></tr>\n",
            htmlspecialchars($ruleName),
            $rule['total_applications'],
            $successClass,
            $rule['success_rate']
        );
    }
    $replacements['{rules_table}'] = $rulesTable ?: '<tr><td colspan="3">No rules applied</td></tr>';
    
    // Replace all placeholders
    foreach ($replacements as $placeholder => $value) {
        $html = str_replace($placeholder, (string) $value, $html);
    }
    
    return $html;
}