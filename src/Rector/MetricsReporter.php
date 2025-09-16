<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Rector;

use InvalidArgumentException;

final class MetricsReporter
{
    private string $projectName = 'HashId Bundle';
    private string $projectVersion = 'v4.0';

    public function __construct(private readonly MetricsCollector $collector)
    {
    }

    public function generateHtmlDashboard(): string
    {
        $metrics = $this->collector->getMetrics();
        $summary = $this->collector->getSummaryStatistics();
        $timeSavings = $this->collector->getTimeSavingsMetrics();
        $ruleEffectiveness = $this->collector->getRuleEffectivenessReport();
        
        return $this->renderHtmlTemplate($metrics, $summary, $timeSavings, $ruleEffectiveness);
    }

    public function generateJsonReport(): string
    {
        return $this->collector->exportAsJson();
    }

    public function generateCsvReport(): string
    {
        $summary = $this->collector->getSummaryStatistics();
        $csv = "Rector Metrics Report - {$this->projectName} {$this->projectVersion}\n";
        $csv .= "Generated," . \date('Y-m-d H:i:s') . "\n\n";
        
        $csv .= "Summary Statistics\n";
        $csv .= $this->collector->exportAsCsv();
        $csv .= "\n";
        
        $csv .= "Rule Effectiveness\n";
        $csv .= "Rule Name,Total Applications,Successful,Failed,Success Rate\n";
        
        foreach ($this->collector->getRuleEffectivenessReport() as $rule) {
            $csv .= \sprintf(
                "%s,%d,%d,%d,%.2f\n",
                $rule['rule_name'],
                $rule['total_applications'],
                $rule['successful'],
                $rule['failed'],
                $rule['success_rate']
            );
        }
        
        return $csv;
    }

    public function generateMarkdownSummary(): string
    {
        return $this->collector->exportAsMarkdown();
    }

    public function generateExecutiveSummary(): array
    {
        $metrics = $this->collector->getMetrics();
        $summary = $this->collector->getSummaryStatistics();
        $timeSavings = $this->collector->getTimeSavingsMetrics();
        
        return [
            'project' => [
                'name' => $this->projectName,
                'version' => $this->projectVersion,
                'timestamp' => \date('Y-m-d H:i:s'),
            ],
            'highlights' => [
                'automation_rate' => $metrics['automation_percentage'],
                'target_met' => $this->collector->meetsAutomationTarget(70.0),
                'time_saved_hours' => $timeSavings['time_saved_hours'],
                'files_processed' => $summary['total_files'],
                'files_modified' => $summary['files_modified'],
            ],
            'key_metrics' => [
                'total_changes' => $summary['total_changes'],
                'automated_changes' => $metrics['automated_changes'],
                'manual_changes' => $metrics['manual_changes'],
                'rules_applied' => $summary['total_rules_used'],
                'error_rate' => $summary['error_rate'],
            ],
            'effectiveness' => [
                'time_savings_percentage' => $timeSavings['time_savings_percentage'],
                'modification_rate' => $summary['modification_rate'],
                'average_changes_per_file' => $summary['average_changes_per_file'],
            ],
        ];
    }

    public function saveReport(string $format, string $outputPath): bool
    {
        $content = match (\strtolower($format)) {
            'html' => $this->generateHtmlDashboard(),
            'json' => $this->generateJsonReport(),
            'csv' => $this->generateCsvReport(),
            'markdown', 'md' => $this->generateMarkdownSummary(),
            default => throw new InvalidArgumentException("Unsupported format: $format"),
        };
        
        $result = \file_put_contents($outputPath, $content);
        return $result !== false;
    }

    public function generateComparisonReport(array $previousMetrics): array
    {
        $currentMetrics = $this->collector->getMetrics();
        
        return [
            'automation_rate' => [
                'previous' => $previousMetrics['automation_percentage'] ?? 0,
                'current' => $currentMetrics['automation_percentage'],
                'improvement' => $currentMetrics['automation_percentage'] - ($previousMetrics['automation_percentage'] ?? 0),
            ],
            'files_processed' => [
                'previous' => $previousMetrics['total_files'] ?? 0,
                'current' => $currentMetrics['total_files'],
                'increase' => $currentMetrics['total_files'] - ($previousMetrics['total_files'] ?? 0),
            ],
            'error_rate' => [
                'previous' => $previousMetrics['error_rate'] ?? 0,
                'current' => $currentMetrics['error_rate'],
                'reduction' => ($previousMetrics['error_rate'] ?? 0) - $currentMetrics['error_rate'],
            ],
        ];
    }

    private function renderHtmlTemplate(array $metrics, array $summary, array $timeSavings, array $ruleEffectiveness): string
    {
        $automationRate = $metrics['automation_percentage'];
        $targetMet = $this->collector->meetsAutomationTarget(70.0);
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rector Metrics Dashboard - {$this->projectName}</title>
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: var(--dark);
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .card-title {
            font-size: 0.9em;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .card-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary);
        }
        
        .card-subtitle {
            font-size: 0.9em;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        .card.success .card-value {
            color: var(--success);
        }
        
        .card.warning .card-value {
            color: var(--warning);
        }
        
        .card.danger .card-value {
            color: var(--danger);
        }
        
        .progress-section {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .progress-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .progress-container {
            position: relative;
            height: 60px;
            background: var(--light);
            border-radius: 30px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.2em;
            font-weight: bold;
            transition: width 1s ease;
            animation: progressAnimation 1s ease;
        }
        
        @keyframes progressAnimation {
            from { width: 0; }
        }
        
        .progress-target {
            position: absolute;
            left: 70%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--danger);
            z-index: 1;
        }
        
        .progress-target::after {
            content: 'Target: 70%';
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--danger);
            font-size: 0.9em;
            white-space: nowrap;
        }
        
        .table-section {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .table-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        th {
            background: var(--primary);
            color: var(--white);
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        th:first-child {
            border-top-left-radius: 8px;
        }
        
        th:last-child {
            border-top-right-radius: 8px;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light);
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .badge.success {
            background: var(--success);
            color: var(--white);
        }
        
        .badge.warning {
            background: var(--warning);
            color: var(--white);
        }
        
        .badge.danger {
            background: var(--danger);
            color: var(--white);
        }
        
        .chart-container {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .time-savings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .time-item {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            color: var(--white);
        }
        
        .time-value {
            font-size: 2em;
            font-weight: bold;
        }
        
        .time-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        footer {
            text-align: center;
            color: var(--white);
            padding: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>{$this->projectName} - Rector Metrics Dashboard</h1>
            <div class="subtitle">Version {$this->projectVersion} | Generated: {date}</div>
        </header>
        
        <div class="dashboard">
            <div class="card">
                <div class="card-title">Total Files</div>
                <div class="card-value">{$summary['total_files']}</div>
                <div class="card-subtitle">Processed</div>
            </div>
            
            <div class="card">
                <div class="card-title">Files Modified</div>
                <div class="card-value">{$summary['files_modified']}</div>
                <div class="card-subtitle">{modification_rate}% modification rate</div>
            </div>
            
            <div class="card success">
                <div class="card-title">Total Changes</div>
                <div class="card-value">{$summary['total_changes']}</div>
                <div class="card-subtitle">Applied automatically</div>
            </div>
            
            <div class="card {automation_class}">
                <div class="card-title">Automation Rate</div>
                <div class="card-value">{automation_rate}%</div>
                <div class="card-subtitle">{automation_status}</div>
            </div>
        </div>
        
        <div class="progress-section">
            <h2 class="progress-title">Automation Target Progress</h2>
            <div class="progress-container">
                <div class="progress-target"></div>
                <div class="progress-bar" style="width: {automation_rate}%">
                    {automation_rate}% Achieved
                </div>
            </div>
        </div>
        
        <div class="chart-container">
            <h2 class="table-title">Time Savings Analysis</h2>
            <div class="time-savings">
                <div class="time-item">
                    <div class="time-value">{manual_hours}h</div>
                    <div class="time-label">Estimated Manual Time</div>
                </div>
                <div class="time-item">
                    <div class="time-value">{actual_minutes}m</div>
                    <div class="time-label">Actual Time</div>
                </div>
                <div class="time-item">
                    <div class="time-value">{time_saved_hours}h</div>
                    <div class="time-label">Time Saved</div>
                </div>
                <div class="time-item">
                    <div class="time-value">{time_savings_percentage}%</div>
                    <div class="time-label">Efficiency Gain</div>
                </div>
            </div>
        </div>
        
        <div class="table-section">
            <h2 class="table-title">Rule Effectiveness Report</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rule Name</th>
                        <th>Applications</th>
                        <th>Successful</th>
                        <th>Failed</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    {rules_tbody}
                </tbody>
            </table>
        </div>
        
        <footer>
            <p>Generated by Rector Metrics Collection System</p>
        </footer>
    </div>
</body>
</html>
HTML;

        // Prepare replacements
        $replacements = [
            '{date}' => \date('Y-m-d H:i:s'),
            '{automation_rate}' => \number_format($automationRate, 2),
            '{modification_rate}' => \number_format($summary['modification_rate'], 2),
            '{manual_hours}' => \number_format($timeSavings['estimated_manual_time'] / 3600, 2),
            '{actual_minutes}' => \number_format($timeSavings['actual_time'] / 60, 2),
            '{time_saved_hours}' => \number_format($timeSavings['time_saved_hours'], 2),
            '{time_savings_percentage}' => \number_format($timeSavings['time_savings_percentage'], 1),
            '{automation_class}' => $targetMet ? 'success' : 'warning',
            '{automation_status}' => $targetMet ? 'Target Met ✓' : 'Below Target',
        ];

        // Build rules table body
        $rulesBody = '';
        foreach ($ruleEffectiveness as $rule) {
            $ruleName = \basename(\str_replace('\\', '/', $rule['rule_name']));
            $badgeClass = $rule['success_rate'] >= 80 ? 'success' : ($rule['success_rate'] >= 50 ? 'warning' : 'danger');
            $rulesBody .= \sprintf(
                '<tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td><td><span class="badge %s">%.1f%%</span></td></tr>',
                \htmlspecialchars($ruleName),
                $rule['total_applications'],
                $rule['successful'],
                $rule['failed'],
                $badgeClass,
                $rule['success_rate']
            );
        }
        $replacements['{rules_tbody}'] = $rulesBody ?: '<tr><td colspan="5" style="text-align:center">No rules have been applied yet</td></tr>';

        // Apply replacements
        foreach ($replacements as $key => $value) {
            $html = \str_replace($key, (string) $value, $html);
        }

        // Replace remaining placeholders with data from arrays
        foreach ($summary as $key => $value) {
            $html = \str_replace('{' . $key . '}', (string) $value, $html);
        }

        return $html;
    }
}