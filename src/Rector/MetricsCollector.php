<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Rector;

final class MetricsCollector
{
    private int $totalFiles = 0;
    private int $filesModified = 0;
    private int $totalChanges = 0;
    private int $automatedChanges = 0;
    private int $manualChanges = 0;
    private array $rulesApplied = [];
    private array $errors = [];
    private int $errorsCount = 0;
    private array $processedFiles = [];
    private array $modifiedFiles = [];
    private float $startTime = 0;
    private float $executionTime = 0;

    public function trackFileProcessed(string $filePath): void
    {
        if (!\in_array($filePath, $this->processedFiles, true)) {
            $this->processedFiles[] = $filePath;
            ++$this->totalFiles;
        }
    }

    public function trackFileModified(string $filePath, int $changesCount): void
    {
        if (!\in_array($filePath, $this->modifiedFiles, true)) {
            $this->modifiedFiles[] = $filePath;
            ++$this->filesModified;
        }
        $this->totalChanges += $changesCount;
        $this->automatedChanges += $changesCount;
    }

    public function trackManualIntervention(string $filePath, int $changesCount): void
    {
        $this->manualChanges += $changesCount;
        $this->totalChanges += $changesCount;
    }

    public function trackRuleApplied(string $ruleName, string $filePath, bool $success): void
    {
        if (!isset($this->rulesApplied[$ruleName])) {
            $this->rulesApplied[$ruleName] = [
                'total_applications' => 0,
                'successful' => 0,
                'failed' => 0,
                'files' => [],
            ];
        }

        ++$this->rulesApplied[$ruleName]['total_applications'];
        if ($success) {
            ++$this->rulesApplied[$ruleName]['successful'];
        } else {
            ++$this->rulesApplied[$ruleName]['failed'];
        }

        $this->rulesApplied[$ruleName]['files'][] = $filePath;
        $this->calculateRuleSuccessRate($ruleName);
    }

    private function calculateRuleSuccessRate(string $ruleName): void
    {
        $rule = &$this->rulesApplied[$ruleName];
        if ($rule['total_applications'] > 0) {
            $rule['success_rate'] = \round(
                ($rule['successful'] / $rule['total_applications']) * 100,
                2
            );
        } else {
            $rule['success_rate'] = 0.0;
        }
    }

    public function trackError(string $filePath, string $message, ?string $ruleName = null, ?string $category = null): void
    {
        $this->errors[] = [
            'file' => $filePath,
            'message' => $message,
            'rule_name' => $ruleName,
            'category' => $category ?? $this->categorizeError($message),
            'timestamp' => \microtime(true),
            'stack_trace' => $this->captureStackTrace(),
            'context' => $this->gatherErrorContext($filePath, $message),
        ];
        ++$this->errorsCount;
    }

    /**
     * Capture a condensed stack trace for debugging.
     *
     * @return array<int, string> Stack trace lines
     */
    private function captureStackTrace(): array
    {
        $trace = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $stackTrace = [];

        foreach ($trace as $frame) {
            if (isset($frame['file'], $frame['line'])) {
                $file = \basename($frame['file']);
                $stackTrace[] = \sprintf('%s:%d %s',
                    $file,
                    $frame['line'],
                    $frame['function'] ?? 'unknown'
                );
            }
        }

        return $stackTrace;
    }

    /**
     * Categorize error based on message content.
     *
     * @param string $message Error message
     * @return string Error category
     */
    private function categorizeError(string $message): string
    {
        $message = \strtolower($message);

        if (\str_contains($message, 'syntax') || \str_contains($message, 'parse')) {
            return 'syntax';
        }

        if (\str_contains($message, 'config') || \str_contains($message, 'configuration')) {
            return 'configuration';
        }

        if (\str_contains($message, 'annotation') || \str_contains($message, 'attribute')) {
            return 'annotation';
        }

        if (\str_contains($message, 'class') || \str_contains($message, 'method') || \str_contains($message, 'property')) {
            return 'reflection';
        }

        if (\str_contains($message, 'type') || \str_contains($message, 'argument')) {
            return 'type_error';
        }

        return 'unknown';
    }

    /**
     * Gather additional context for the error.
     *
     * @param string $filePath File where error occurred
     * @param string $message Error message
     * @return array<string, mixed> Error context
     */
    private function gatherErrorContext(string $filePath, string $message): array
    {
        $context = [
            'file_size' => \file_exists($filePath) ? \filesize($filePath) : null,
            'file_extension' => \pathinfo($filePath, PATHINFO_EXTENSION),
            'memory_usage' => \memory_get_usage(true),
            'peak_memory' => \memory_get_peak_usage(true),
        ];

        // Add PHP version info for compatibility issues
        if (\str_contains($message, 'php') || \str_contains($message, 'version')) {
            $context['php_version'] = PHP_VERSION;
            $context['php_major_version'] = PHP_MAJOR_VERSION;
        }

        return $context;
    }

    public function startTiming(): void
    {
        $this->startTime = \microtime(true);
    }

    public function stopTiming(): void
    {
        if ($this->startTime > 0) {
            $this->executionTime = \microtime(true) - $this->startTime;
        }
    }

    public function setExecutionTime(float $seconds): void
    {
        $this->executionTime = $seconds;
    }

    public function getMetrics(): array
    {
        $automationPercentage = 0.0;
        if ($this->totalChanges > 0) {
            $automationPercentage = \round(
                ($this->automatedChanges / $this->totalChanges) * 100,
                2
            );
        }

        $errorRate = 0.0;
        if ($this->totalFiles > 0) {
            $errorRate = \round(
                ($this->errorsCount / $this->totalFiles) * 100,
                2
            );
        }

        return [
            'total_files' => $this->totalFiles,
            'files_modified' => $this->filesModified,
            'total_changes' => $this->totalChanges,
            'automated_changes' => $this->automatedChanges,
            'manual_changes' => $this->manualChanges,
            'automation_percentage' => $automationPercentage,
            'rules_applied' => $this->rulesApplied,
            'errors_count' => $this->errorsCount,
            'error_rate' => $errorRate,
            'errors' => $this->errors,
            'execution_time' => $this->executionTime,
        ];
    }

    public function getRuleEffectivenessReport(): array
    {
        $report = [];
        foreach ($this->rulesApplied as $ruleName => $metrics) {
            $report[] = [
                'rule_name' => $ruleName,
                'total_applications' => $metrics['total_applications'],
                'successful' => $metrics['successful'],
                'failed' => $metrics['failed'],
                'success_rate' => $metrics['success_rate'],
                'files_count' => \count(\array_unique($metrics['files'])),
            ];
        }

        // Sort by success rate descending
        \usort($report, function ($a, $b) {
            return $b['success_rate'] <=> $a['success_rate'];
        });

        return $report;
    }

    public function calculateTimeSavings(int $secondsPerManualChange): array
    {
        $estimatedManualTime = $this->automatedChanges * $secondsPerManualChange;
        $timeSaved = $estimatedManualTime - $this->executionTime;
        $timeSavingsPercentage = 0.0;

        if ($estimatedManualTime > 0) {
            $timeSavingsPercentage = \round(
                ($timeSaved / $estimatedManualTime) * 100,
                1
            );
        }

        return [
            'estimated_manual_time' => $estimatedManualTime,
            'actual_time' => $this->executionTime,
            'time_saved_seconds' => $timeSaved,
            'time_saved_hours' => \round($timeSaved / 3600, 2),
            'time_savings_percentage' => $timeSavingsPercentage,
        ];
    }

    public function getTimeSavingsMetrics(): array
    {
        // Default to 5 minutes per change if not specified
        return $this->calculateTimeSavings(5 * 60);
    }

    public function getSummaryStatistics(): array
    {
        $avgChangesPerFile = 0.0;
        if ($this->filesModified > 0) {
            $avgChangesPerFile = $this->automatedChanges / $this->filesModified;
        }

        $metrics = $this->getMetrics();

        return [
            'total_files' => $this->totalFiles,
            'files_modified' => $this->filesModified,
            'modification_rate' => $this->totalFiles > 0 
                ? \round(($this->filesModified / $this->totalFiles) * 100, 2) 
                : 0.0,
            'total_changes' => $this->totalChanges,
            'automation_rate' => $metrics['automation_percentage'],
            'average_changes_per_file' => \round($avgChangesPerFile, 2),
            'total_rules_used' => \count($this->rulesApplied),
            'error_rate' => $metrics['error_rate'],
            'execution_time_seconds' => \round($this->executionTime, 2),
        ];
    }

    public function meetsAutomationTarget(float $targetPercentage): bool
    {
        $metrics = $this->getMetrics();
        return $metrics['automation_percentage'] >= $targetPercentage;
    }

    public function reset(): void
    {
        $this->totalFiles = 0;
        $this->filesModified = 0;
        $this->totalChanges = 0;
        $this->automatedChanges = 0;
        $this->manualChanges = 0;
        $this->rulesApplied = [];
        $this->errors = [];
        $this->errorsCount = 0;
        $this->processedFiles = [];
        $this->modifiedFiles = [];
        $this->startTime = 0;
        $this->executionTime = 0;
    }

    /**
     * Get error statistics grouped by category.
     *
     * @return array<string, array<string, mixed>> Error statistics by category
     */
    public function getErrorsByCategory(): array
    {
        $categories = [];

        foreach ($this->errors as $error) {
            $category = $error['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'count' => 0,
                    'files' => [],
                    'rules' => [],
                    'examples' => [],
                ];
            }

            ++$categories[$category]['count'];
            $categories[$category]['files'][] = $error['file'];

            if (!empty($error['rule_name'])) {
                $categories[$category]['rules'][] = $error['rule_name'];
            }

            // Keep first 3 examples for each category
            if (\count($categories[$category]['examples']) < 3) {
                $categories[$category]['examples'][] = [
                    'file' => \basename($error['file']),
                    'message' => $error['message'],
                    'rule' => $error['rule_name'],
                ];
            }
        }

        // Remove duplicates and add statistics
        foreach ($categories as $category => &$data) {
            $data['unique_files'] = \count(\array_unique($data['files']));
            $data['unique_rules'] = \count(\array_unique(\array_filter($data['rules'])));
            unset($data['files'], $data['rules']);
        }

        return $categories;
    }

    /**
     * Get error statistics grouped by rule name.
     *
     * @return array<string, array<string, mixed>> Error statistics by rule
     */
    public function getErrorsByRule(): array
    {
        $rules = [];

        foreach ($this->errors as $error) {
            $ruleName = $error['rule_name'] ?? 'unknown';
            if (!isset($rules[$ruleName])) {
                $rules[$ruleName] = [
                    'count' => 0,
                    'categories' => [],
                    'files' => [],
                    'latest_error' => null,
                ];
            }

            ++$rules[$ruleName]['count'];
            $rules[$ruleName]['categories'][] = $error['category'];
            $rules[$ruleName]['files'][] = $error['file'];
            $rules[$ruleName]['latest_error'] = [
                'message' => $error['message'],
                'file' => \basename($error['file']),
                'timestamp' => $error['timestamp'],
            ];
        }

        // Add statistics
        foreach ($rules as $ruleName => &$data) {
            $data['unique_files'] = \count(\array_unique($data['files']));
            $data['unique_categories'] = \count(\array_unique($data['categories']));
            $data['most_common_category'] = !empty($data['categories'])
                ? \array_count_values($data['categories'])
                : [];

            if (!empty($data['most_common_category'])) {
                \arsort($data['most_common_category']);
                $data['most_common_category'] = \array_key_first($data['most_common_category']);
            } else {
                $data['most_common_category'] = 'unknown';
            }

            unset($data['categories'], $data['files']);
        }

        return $rules;
    }

    /**
     * Get enhanced error report with detailed context.
     *
     * @return array<string, mixed> Detailed error analysis
     */
    public function getDetailedErrorReport(): array
    {
        return [
            'total_errors' => $this->errorsCount,
            'by_category' => $this->getErrorsByCategory(),
            'by_rule' => $this->getErrorsByRule(),
            'most_problematic_files' => $this->getMostProblematicFiles(),
            'error_trends' => $this->getErrorTrends(),
        ];
    }

    /**
     * Get files with the most errors.
     *
     * @return array<string, mixed> Files sorted by error count
     */
    private function getMostProblematicFiles(): array
    {
        $fileErrors = [];

        foreach ($this->errors as $error) {
            $file = $error['file'];
            if (!isset($fileErrors[$file])) {
                $fileErrors[$file] = [
                    'count' => 0,
                    'categories' => [],
                    'rules' => [],
                ];
            }

            ++$fileErrors[$file]['count'];
            $fileErrors[$file]['categories'][] = $error['category'];
            if (!empty($error['rule_name'])) {
                $fileErrors[$file]['rules'][] = $error['rule_name'];
            }
        }

        // Sort by error count and take top 10
        \uasort($fileErrors, fn($a, $b) => $b['count'] <=> $a['count']);
        $fileErrors = \array_slice($fileErrors, 0, 10, true);

        // Add statistics
        foreach ($fileErrors as $file => &$data) {
            $data['unique_categories'] = \count(\array_unique($data['categories']));
            $data['unique_rules'] = \count(\array_unique(\array_filter($data['rules'])));
            $data['basename'] = \basename($file);
            unset($data['categories'], $data['rules']);
        }

        return $fileErrors;
    }

    /**
     * Get error trends over time.
     *
     * @return array<string, mixed> Error trend analysis
     */
    private function getErrorTrends(): array
    {
        if (empty($this->errors)) {
            return ['trend' => 'no_data'];
        }

        $timestamps = \array_column($this->errors, 'timestamp');
        \sort($timestamps);

        $firstError = $timestamps[0];
        $lastError = $timestamps[\count($timestamps) - 1];
        $duration = $lastError - $firstError;

        return [
            'first_error_time' => $firstError,
            'last_error_time' => $lastError,
            'duration_seconds' => $duration,
            'errors_per_second' => $duration > 0 ? $this->errorsCount / $duration : 0,
            'trend' => $this->errorsCount > 10 ? 'high_volume' : 'manageable',
        ];
    }

    public function exportAsJson(): string
    {
        $metrics = $this->getMetrics();
        $metrics['summary'] = $this->getSummaryStatistics();
        $metrics['rule_effectiveness'] = $this->getRuleEffectivenessReport();
        $metrics['time_savings'] = $this->getTimeSavingsMetrics();
        $metrics['detailed_errors'] = $this->getDetailedErrorReport();
        $metrics['timestamp'] = \date('Y-m-d H:i:s');

        return \json_encode($metrics, JSON_PRETTY_PRINT);
    }

    public function exportAsCsv(): string
    {
        $summary = $this->getSummaryStatistics();
        $csv = "Metric,Value\n";
        
        foreach ($summary as $key => $value) {
            $csv .= \sprintf("%s,%s\n", $key, $value);
        }
        
        return $csv;
    }

    public function exportAsMarkdown(): string
    {
        $metrics = $this->getMetrics();
        $summary = $this->getSummaryStatistics();
        $timeSavings = $this->getTimeSavingsMetrics();
        
        $markdown = "# Rector Automation Metrics Report\n\n";
        $markdown .= \sprintf("Generated: %s\n\n", \date('Y-m-d H:i:s'));
        
        $markdown .= "## Summary Statistics\n\n";
        $markdown .= "| Metric | Value |\n";
        $markdown .= "|--------|-------|\n";
        $markdown .= \sprintf("| Total Files Processed | %d |\n", $summary['total_files']);
        $markdown .= \sprintf("| Files Modified | %d |\n", $summary['files_modified']);
        $markdown .= \sprintf("| Total Changes | %d |\n", $summary['total_changes']);
        $markdown .= \sprintf("| Automation Rate | %.2f%% |\n", $summary['automation_rate']);
        $markdown .= \sprintf("| Average Changes per File | %.2f |\n", $summary['average_changes_per_file']);
        $markdown .= \sprintf("| Execution Time | %.2f seconds |\n", $summary['execution_time_seconds']);
        
        if ($timeSavings['time_saved_hours'] > 0) {
            $markdown .= "\n## Time Savings\n\n";
            $markdown .= \sprintf("- **Time Saved**: %.2f hours (%.1f%%)\n", 
                $timeSavings['time_saved_hours'], 
                $timeSavings['time_savings_percentage']
            );
            $markdown .= \sprintf("- **Estimated Manual Time**: %.2f hours\n", 
                $timeSavings['estimated_manual_time'] / 3600
            );
            $markdown .= \sprintf("- **Actual Time**: %.2f minutes\n", 
                $timeSavings['actual_time'] / 60
            );
        }
        
        if (!empty($this->rulesApplied)) {
            $markdown .= "\n## Rule Effectiveness\n\n";
            $markdown .= "| Rule | Applications | Success Rate |\n";
            $markdown .= "|------|--------------|-------------|\n";
            
            foreach ($this->getRuleEffectivenessReport() as $rule) {
                $ruleName = \basename(\str_replace('\\', '/', $rule['rule_name']));
                $markdown .= \sprintf("| %s | %d | %.1f%% |\n", 
                    $ruleName,
                    $rule['total_applications'],
                    $rule['success_rate']
                );
            }
        }
        
        $markdown .= "\n## Automation Target\n\n";
        $targetMet = $this->meetsAutomationTarget(70.0);
        $markdown .= \sprintf("- **Target**: 70%% automation\n");
        $markdown .= \sprintf("- **Achieved**: %.2f%%\n", $metrics['automation_percentage']);
        $markdown .= \sprintf("- **Status**: %s\n", $targetMet ? '✅ Target Met' : '❌ Below Target');
        
        return $markdown;
    }
}