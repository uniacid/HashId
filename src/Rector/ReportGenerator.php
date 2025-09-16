<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Rector;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ReportGenerator
{
    private readonly Filesystem $filesystem;
    private readonly string $reportDirectory;

    public function __construct(
        private readonly MetricsCollector $metricsCollector,
        ?string $reportDirectory = null
    ) {
        $this->filesystem = new Filesystem();
        $this->reportDirectory = $reportDirectory ?? __DIR__ . '/../../var/rector-reports';
    }

    public function generateReport(string $format = 'all', ?OutputInterface $output = null): array
    {
        $this->ensureReportDirectoryExists();

        $timestamp = date('Y-m-d-His');
        $reports = [];

        if ($format === 'json' || $format === 'all') {
            $reports['json'] = $this->generateJsonReport($timestamp);
            $output?->writeln('<info>Generated JSON report: ' . $reports['json'] . '</info>');
        }

        if ($format === 'html' || $format === 'all') {
            $reports['html'] = $this->generateHtmlReport($timestamp);
            $output?->writeln('<info>Generated HTML report: ' . $reports['html'] . '</info>');
        }

        if ($format === 'markdown' || $format === 'all') {
            $reports['markdown'] = $this->generateMarkdownReport($timestamp);
            $output?->writeln('<info>Generated Markdown report: ' . $reports['markdown'] . '</info>');
        }

        if ($format === 'csv' || $format === 'all') {
            $reports['csv'] = $this->generateCsvReport($timestamp);
            $output?->writeln('<info>Generated CSV report: ' . $reports['csv'] . '</info>');
        }

        // Generate summary report
        $summaryFile = $this->generateSummaryReport($timestamp);
        $reports['summary'] = $summaryFile;
        $output?->writeln('<info>Generated Summary report: ' . $summaryFile . '</info>');

        return $reports;
    }

    public function generateJsonReport(string $timestamp): string
    {
        $metrics = $this->collectAllMetrics();
        $filename = sprintf('%s/rector-metrics-%s.json', $this->reportDirectory, $timestamp);

        $this->filesystem->dumpFile($filename, json_encode($metrics, JSON_PRETTY_PRINT));

        return $filename;
    }

    public function generateHtmlReport(string $timestamp): string
    {
        $metrics = $this->collectAllMetrics();
        $filename = sprintf('%s/rector-metrics-%s.html', $this->reportDirectory, $timestamp);

        $html = $this->renderHtmlTemplate($metrics);
        $this->filesystem->dumpFile($filename, $html);

        return $filename;
    }

    public function generateMarkdownReport(string $timestamp): string
    {
        $metrics = $this->collectAllMetrics();
        $filename = sprintf('%s/rector-metrics-%s.md', $this->reportDirectory, $timestamp);

        $markdown = $this->renderMarkdownTemplate($metrics);
        $this->filesystem->dumpFile($filename, $markdown);

        return $filename;
    }

    public function generateCsvReport(string $timestamp): string
    {
        $metrics = $this->collectAllMetrics();
        $filename = sprintf('%s/rector-metrics-%s.csv', $this->reportDirectory, $timestamp);

        $csv = $this->renderCsvTemplate($metrics);
        $this->filesystem->dumpFile($filename, $csv);

        return $filename;
    }

    public function generateSummaryReport(string $timestamp): string
    {
        $metrics = $this->collectAllMetrics();
        $filename = sprintf('%s/rector-summary-%s.txt', $this->reportDirectory, $timestamp);

        $summary = $this->renderSummaryTemplate($metrics);
        $this->filesystem->dumpFile($filename, $summary);

        return $filename;
    }

    private function collectAllMetrics(): array
    {
        $baseMetrics = $this->metricsCollector->getMetrics();
        $summaryStats = $this->metricsCollector->getSummaryStatistics();
        $ruleEffectiveness = $this->metricsCollector->getRuleEffectivenessReport();
        $timeSavings = $this->metricsCollector->getTimeSavingsMetrics();

        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'base_metrics' => $baseMetrics,
            'summary_statistics' => $summaryStats,
            'rule_effectiveness' => $ruleEffectiveness,
            'time_savings' => $timeSavings,
            'automation_targets' => $this->getAutomationTargets($baseMetrics),
            'performance_grade' => $this->calculatePerformanceGrade($baseMetrics),
            'recommendations' => $this->generateRecommendations($baseMetrics),
        ];
    }

    private function getAutomationTargets(array $metrics): array
    {
        return [
            'overall_automation' => [
                'target' => 70.0,
                'achieved' => $metrics['automation_percentage'],
                'met' => $metrics['automation_percentage'] >= 70.0,
            ],
            'rule_success_rates' => [
                'hash_annotation' => [
                    'target' => 90.0,
                    'achieved' => $this->getRuleSuccessRate($metrics, 'HashAnnotationToAttributeRule'),
                    'met' => $this->getRuleSuccessRate($metrics, 'HashAnnotationToAttributeRule') >= 90.0,
                ],
                'constructor_promotion' => [
                    'target' => 85.0,
                    'achieved' => $this->getRuleSuccessRate($metrics, 'ConstructorPropertyPromotionRule'),
                    'met' => $this->getRuleSuccessRate($metrics, 'ConstructorPropertyPromotionRule') >= 85.0,
                ],
                'readonly_properties' => [
                    'target' => 85.0,
                    'achieved' => $this->getRuleSuccessRate($metrics, 'ReadOnlyPropertyRule'),
                    'met' => $this->getRuleSuccessRate($metrics, 'ReadOnlyPropertyRule') >= 85.0,
                ],
            ],
            'time_reduction' => [
                'target' => 50.0,
                'achieved' => $metrics['time_savings']['time_savings_percentage'] ?? 0,
                'met' => ($metrics['time_savings']['time_savings_percentage'] ?? 0) >= 50.0,
            ],
            'error_reduction' => [
                'target' => 80.0,
                'achieved' => $this->calculateErrorReduction($metrics),
                'met' => $this->calculateErrorReduction($metrics) >= 80.0,
            ],
        ];
    }

    private function getRuleSuccessRate(array $metrics, string $ruleName): float
    {
        if (!isset($metrics['rules_applied'][$ruleName])) {
            return 0.0;
        }

        return $metrics['rules_applied'][$ruleName]['success_rate'] ?? 0.0;
    }

    private function calculateErrorReduction(array $metrics): float
    {
        // Simplified calculation based on error rate
        $baseErrorRate = 25.0; // Baseline manual error rate
        $currentErrorRate = $metrics['error_rate'] ?? 0.0;

        if ($baseErrorRate <= 0) {
            return 0.0;
        }

        return (($baseErrorRate - $currentErrorRate) / $baseErrorRate) * 100;
    }

    private function calculatePerformanceGrade(array $metrics): string
    {
        $score = 0;
        $maxScore = 100;

        // Automation percentage (40 points)
        $automation = $metrics['automation_percentage'] ?? 0;
        if ($automation >= 90) $score += 40;
        elseif ($automation >= 80) $score += 35;
        elseif ($automation >= 70) $score += 30;
        elseif ($automation >= 60) $score += 20;
        else $score += 10;

        // Time savings (20 points)
        $timeSavings = $metrics['time_savings']['time_savings_percentage'] ?? 0;
        if ($timeSavings >= 75) $score += 20;
        elseif ($timeSavings >= 50) $score += 15;
        elseif ($timeSavings >= 25) $score += 10;
        else $score += 5;

        // Error rate (20 points)
        $errorRate = $metrics['error_rate'] ?? 0;
        if ($errorRate <= 1) $score += 20;
        elseif ($errorRate <= 5) $score += 15;
        elseif ($errorRate <= 10) $score += 10;
        else $score += 5;

        // Rule effectiveness (20 points)
        $avgRuleSuccess = $this->calculateAverageRuleSuccess($metrics);
        if ($avgRuleSuccess >= 90) $score += 20;
        elseif ($avgRuleSuccess >= 80) $score += 15;
        elseif ($avgRuleSuccess >= 70) $score += 10;
        else $score += 5;

        // Convert score to grade
        $percentage = ($score / $maxScore) * 100;

        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }

    private function calculateAverageRuleSuccess(array $metrics): float
    {
        if (empty($metrics['rules_applied'])) {
            return 0.0;
        }

        $totalSuccess = 0;
        $count = 0;

        foreach ($metrics['rules_applied'] as $rule) {
            $totalSuccess += $rule['success_rate'] ?? 0;
            $count++;
        }

        return $count > 0 ? $totalSuccess / $count : 0.0;
    }

    private function generateRecommendations(array $metrics): array
    {
        $recommendations = [];

        // Check automation percentage
        if ($metrics['automation_percentage'] < 70) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => 'Automation below target (70%). Review and enhance Rector rules.',
                'action' => 'Add more specific rules for common patterns in your codebase.',
            ];
        } elseif ($metrics['automation_percentage'] < 80) {
            $recommendations[] = [
                'severity' => 'medium',
                'message' => 'Good automation level. Consider optimizing for higher coverage.',
                'action' => 'Analyze manual interventions to identify patterns for new rules.',
            ];
        }

        // Check error rate
        if ($metrics['error_rate'] > 10) {
            $recommendations[] = [
                'severity' => 'high',
                'message' => 'High error rate detected. Rules may need refinement.',
                'action' => 'Review error logs and adjust problematic rules.',
            ];
        }

        // Check time savings
        $timeSavings = $metrics['time_savings']['time_savings_percentage'] ?? 0;
        if ($timeSavings < 50) {
            $recommendations[] = [
                'severity' => 'medium',
                'message' => 'Time savings below expected threshold.',
                'action' => 'Consider running rules in parallel or optimizing rule performance.',
            ];
        }

        // Check specific rules
        foreach ($metrics['rules_applied'] as $ruleName => $ruleMetrics) {
            if ($ruleMetrics['success_rate'] < 70) {
                $recommendations[] = [
                    'severity' => 'medium',
                    'message' => sprintf('Rule %s has low success rate (%.1f%%)',
                        basename(str_replace('\\', '/', $ruleName)),
                        $ruleMetrics['success_rate']
                    ),
                    'action' => 'Review and improve this specific rule implementation.',
                ];
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'severity' => 'info',
                'message' => 'All metrics meet or exceed targets. Ready for production.',
                'action' => 'Continue monitoring and maintain current configuration.',
            ];
        }

        return $recommendations;
    }

    private function renderHtmlTemplate(array $metrics): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rector Automation Metrics Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }
        .grade {
            display: inline-block;
            background: white;
            color: #333;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1.2em;
            margin-top: 10px;
        }
        .grade.A { background: #4caf50; color: white; }
        .grade.B { background: #8bc34a; color: white; }
        .grade.C { background: #ffeb3b; color: #333; }
        .grade.D { background: #ff9800; color: white; }
        .grade.F { background: #f44336; color: white; }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .metric-card h3 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 1.1em;
        }
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        .metric-label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .target-met {
            color: #4caf50;
            font-weight: bold;
        }
        .target-not-met {
            color: #f44336;
            font-weight: bold;
        }
        .recommendations {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .recommendation {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .recommendation.high {
            border-color: #f44336;
            background: #ffebee;
        }
        .recommendation.medium {
            border-color: #ff9800;
            background: #fff3e0;
        }
        .recommendation.info {
            border-color: #2196f3;
            background: #e3f2fd;
        }
        .footer {
            text-align: center;
            color: #666;
            margin-top: 50px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rector Automation Metrics Report</h1>
        <p>Generated: {$metrics['timestamp']}</p>
        <span class="grade {$metrics['performance_grade']}">Grade: {$metrics['performance_grade']}</span>
    </div>

    <div class="metrics-grid">
        <div class="metric-card">
            <h3>Automation Rate</h3>
            <div class="metric-value">{$metrics['base_metrics']['automation_percentage']}%</div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {$metrics['base_metrics']['automation_percentage']}%"></div>
            </div>
            <div class="metric-label">Target: 70%</div>
        </div>

        <div class="metric-card">
            <h3>Files Processed</h3>
            <div class="metric-value">{$metrics['base_metrics']['total_files']}</div>
            <div class="metric-label">Modified: {$metrics['base_metrics']['files_modified']}</div>
        </div>

        <div class="metric-card">
            <h3>Time Saved</h3>
            <div class="metric-value">{$metrics['time_savings']['time_saved_hours']}h</div>
            <div class="metric-label">{$metrics['time_savings']['time_savings_percentage']}% reduction</div>
        </div>

        <div class="metric-card">
            <h3>Error Rate</h3>
            <div class="metric-value">{$metrics['base_metrics']['error_rate']}%</div>
            <div class="metric-label">{$metrics['base_metrics']['errors_count']} errors</div>
        </div>
    </div>

HTML;

        // Add targets table
        $html .= $this->renderTargetsTable($metrics['automation_targets']);

        // Add rule effectiveness table
        if (!empty($metrics['rule_effectiveness'])) {
            $html .= $this->renderRuleEffectivenessTable($metrics['rule_effectiveness']);
        }

        // Add recommendations
        if (!empty($metrics['recommendations'])) {
            $html .= $this->renderRecommendations($metrics['recommendations']);
        }

        $html .= <<<HTML
    <div class="footer">
        <p>HashId Bundle v4.0 - Rector Automation Report</p>
    </div>
</body>
</html>
HTML;

        return $html;
    }

    private function renderTargetsTable(array $targets): string
    {
        $html = '<div class="table"><table>';
        $html .= '<thead><tr><th>Target</th><th>Required</th><th>Achieved</th><th>Status</th></tr></thead><tbody>';

        foreach ($targets as $name => $target) {
            if (is_array($target) && isset($target['target'])) {
                $status = $target['met'] ? '✅ Met' : '❌ Not Met';
                $statusClass = $target['met'] ? 'target-met' : 'target-not-met';
                $label = ucwords(str_replace('_', ' ', $name));

                $html .= sprintf(
                    '<tr><td>%s</td><td>%.1f%%</td><td>%.1f%%</td><td class="%s">%s</td></tr>',
                    $label,
                    $target['target'],
                    $target['achieved'],
                    $statusClass,
                    $status
                );
            }
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    private function renderRuleEffectivenessTable(array $rules): string
    {
        $html = '<div class="table"><h2>Rule Effectiveness</h2><table>';
        $html .= '<thead><tr><th>Rule</th><th>Applications</th><th>Success Rate</th></tr></thead><tbody>';

        foreach ($rules as $rule) {
            $ruleName = basename(str_replace('\\', '/', $rule['rule_name']));
            $html .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%.1f%%</td></tr>',
                $ruleName,
                $rule['total_applications'],
                $rule['success_rate']
            );
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    private function renderRecommendations(array $recommendations): string
    {
        $html = '<div class="recommendations"><h2>Recommendations</h2>';

        foreach ($recommendations as $rec) {
            $html .= sprintf(
                '<div class="recommendation %s"><strong>%s</strong><p>%s</p><em>Action: %s</em></div>',
                $rec['severity'],
                ucfirst($rec['severity']),
                $rec['message'],
                $rec['action']
            );
        }

        $html .= '</div>';
        return $html;
    }

    private function renderMarkdownTemplate(array $metrics): string
    {
        $markdown = "# Rector Automation Metrics Report\n\n";
        $markdown .= "**Generated:** {$metrics['timestamp']}\n";
        $markdown .= "**Performance Grade:** {$metrics['performance_grade']}\n\n";

        // Summary section
        $markdown .= "## Summary Statistics\n\n";
        $markdown .= "| Metric | Value |\n";
        $markdown .= "|--------|-------|\n";
        $markdown .= sprintf("| Total Files Processed | %d |\n", $metrics['base_metrics']['total_files']);
        $markdown .= sprintf("| Files Modified | %d |\n", $metrics['base_metrics']['files_modified']);
        $markdown .= sprintf("| Total Changes | %d |\n", $metrics['base_metrics']['total_changes']);
        $markdown .= sprintf("| Automated Changes | %d |\n", $metrics['base_metrics']['automated_changes']);
        $markdown .= sprintf("| Manual Changes | %d |\n", $metrics['base_metrics']['manual_changes']);
        $markdown .= sprintf("| **Automation Rate** | **%.2f%%** |\n", $metrics['base_metrics']['automation_percentage']);
        $markdown .= sprintf("| Error Rate | %.2f%% |\n", $metrics['base_metrics']['error_rate']);
        $markdown .= sprintf("| Execution Time | %.2f seconds |\n", $metrics['base_metrics']['execution_time']);

        // Time savings
        $markdown .= "\n## Time Savings\n\n";
        $markdown .= sprintf("- **Time Saved:** %.2f hours (%.1f%%)\n",
            $metrics['time_savings']['time_saved_hours'],
            $metrics['time_savings']['time_savings_percentage']
        );
        $markdown .= sprintf("- **Estimated Manual Time:** %.2f hours\n",
            $metrics['time_savings']['estimated_manual_time'] / 3600
        );

        // Automation targets
        $markdown .= "\n## Automation Targets\n\n";
        $markdown .= "| Target | Required | Achieved | Status |\n";
        $markdown .= "|--------|----------|----------|--------|\n";

        foreach ($metrics['automation_targets'] as $name => $target) {
            if (is_array($target) && isset($target['target'])) {
                $status = $target['met'] ? '✅ Met' : '❌ Not Met';
                $label = ucwords(str_replace('_', ' ', $name));
                $markdown .= sprintf("| %s | %.1f%% | %.1f%% | %s |\n",
                    $label,
                    $target['target'],
                    $target['achieved'],
                    $status
                );
            }
        }

        // Rule effectiveness
        if (!empty($metrics['rule_effectiveness'])) {
            $markdown .= "\n## Rule Effectiveness\n\n";
            $markdown .= "| Rule | Applications | Success Rate |\n";
            $markdown .= "|------|--------------|-------------|\n";

            foreach ($metrics['rule_effectiveness'] as $rule) {
                $ruleName = basename(str_replace('\\', '/', $rule['rule_name']));
                $markdown .= sprintf("| %s | %d | %.1f%% |\n",
                    $ruleName,
                    $rule['total_applications'],
                    $rule['success_rate']
                );
            }
        }

        // Recommendations
        if (!empty($metrics['recommendations'])) {
            $markdown .= "\n## Recommendations\n\n";

            foreach ($metrics['recommendations'] as $rec) {
                $icon = match($rec['severity']) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    default => 'ℹ️'
                };
                $markdown .= sprintf("### %s %s\n\n", $icon, $rec['message']);
                $markdown .= sprintf("**Action:** %s\n\n", $rec['action']);
            }
        }

        $markdown .= "\n---\n";
        $markdown .= "*Report generated by HashId Bundle v4.0 Rector Automation*\n";

        return $markdown;
    }

    private function renderCsvTemplate(array $metrics): string
    {
        $csv = "Metric,Value\n";
        $csv .= sprintf("Generated,%s\n", $metrics['timestamp']);
        $csv .= sprintf("Performance Grade,%s\n", $metrics['performance_grade']);
        $csv .= sprintf("Total Files Processed,%d\n", $metrics['base_metrics']['total_files']);
        $csv .= sprintf("Files Modified,%d\n", $metrics['base_metrics']['files_modified']);
        $csv .= sprintf("Total Changes,%d\n", $metrics['base_metrics']['total_changes']);
        $csv .= sprintf("Automated Changes,%d\n", $metrics['base_metrics']['automated_changes']);
        $csv .= sprintf("Manual Changes,%d\n", $metrics['base_metrics']['manual_changes']);
        $csv .= sprintf("Automation Percentage,%.2f\n", $metrics['base_metrics']['automation_percentage']);
        $csv .= sprintf("Error Rate,%.2f\n", $metrics['base_metrics']['error_rate']);
        $csv .= sprintf("Execution Time,%.2f\n", $metrics['base_metrics']['execution_time']);
        $csv .= sprintf("Time Saved (hours),%.2f\n", $metrics['time_savings']['time_saved_hours']);
        $csv .= sprintf("Time Savings Percentage,%.1f\n", $metrics['time_savings']['time_savings_percentage']);

        // Add rule effectiveness
        if (!empty($metrics['rule_effectiveness'])) {
            $csv .= "\nRule,Applications,Success Rate\n";
            foreach ($metrics['rule_effectiveness'] as $rule) {
                $ruleName = basename(str_replace('\\', '/', $rule['rule_name']));
                $csv .= sprintf("%s,%d,%.1f\n",
                    $ruleName,
                    $rule['total_applications'],
                    $rule['success_rate']
                );
            }
        }

        return $csv;
    }

    private function renderSummaryTemplate(array $metrics): string
    {
        $summary = "RECTOR AUTOMATION METRICS SUMMARY\n";
        $summary .= str_repeat('=', 50) . "\n\n";
        $summary .= "Generated: {$metrics['timestamp']}\n";
        $summary .= "Performance Grade: {$metrics['performance_grade']}\n\n";

        $summary .= "KEY METRICS:\n";
        $summary .= str_repeat('-', 30) . "\n";
        $summary .= sprintf("Automation Rate: %.2f%% (Target: 70%%)\n", $metrics['base_metrics']['automation_percentage']);
        $summary .= sprintf("Files Processed: %d\n", $metrics['base_metrics']['total_files']);
        $summary .= sprintf("Files Modified: %d\n", $metrics['base_metrics']['files_modified']);
        $summary .= sprintf("Time Saved: %.2f hours (%.1f%%)\n",
            $metrics['time_savings']['time_saved_hours'],
            $metrics['time_savings']['time_savings_percentage']
        );
        $summary .= sprintf("Error Rate: %.2f%%\n", $metrics['base_metrics']['error_rate']);

        $summary .= "\nTARGET ACHIEVEMENT:\n";
        $summary .= str_repeat('-', 30) . "\n";

        $allTargetsMet = true;
        foreach ($metrics['automation_targets'] as $name => $target) {
            if (is_array($target) && isset($target['met'])) {
                $status = $target['met'] ? '[✓]' : '[✗]';
                $label = ucwords(str_replace('_', ' ', $name));
                $summary .= sprintf("%s %s: %.1f%% (Target: %.1f%%)\n",
                    $status,
                    $label,
                    $target['achieved'],
                    $target['target']
                );
                if (!$target['met']) {
                    $allTargetsMet = false;
                }
            }
        }

        $summary .= "\nOVERALL STATUS: ";
        if ($allTargetsMet && $metrics['performance_grade'] !== 'F') {
            $summary .= "✅ READY FOR PRODUCTION\n";
        } else {
            $summary .= "⚠️ NEEDS IMPROVEMENT\n";
        }

        if (!empty($metrics['recommendations'])) {
            $summary .= "\nTOP RECOMMENDATIONS:\n";
            $summary .= str_repeat('-', 30) . "\n";
            foreach (array_slice($metrics['recommendations'], 0, 3) as $rec) {
                $summary .= "• {$rec['message']}\n";
            }
        }

        return $summary;
    }

    private function ensureReportDirectoryExists(): void
    {
        if (!$this->filesystem->exists($this->reportDirectory)) {
            $this->filesystem->mkdir($this->reportDirectory, 0755);
        }
    }
}