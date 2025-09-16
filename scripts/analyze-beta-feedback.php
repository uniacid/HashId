#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Beta Feedback Analysis Script
 * Processes GitHub issues and generates insights
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\Table;

class BetaFeedbackAnalyzer
{
    private ConsoleOutput $output;
    private array $metrics = [];
    private array $issues = [];
    private array $recommendations = [];

    public function __construct()
    {
        $this->output = new ConsoleOutput();
    }

    public function run(): void
    {
        $this->output->writeln('<info>HashId v4.0 Beta Feedback Analysis</info>');
        $this->output->writeln('=====================================');

        $this->loadFeedbackData();
        $this->analyzeAutomationMetrics();
        $this->analyzePerformanceMetrics();
        $this->analyzeIssuePatterns();
        $this->generateRecommendations();
        $this->outputReport();
    }

    private function loadFeedbackData(): void
    {
        $this->output->writeln("\n<comment>Loading feedback data...</comment>");

        // Load from GitHub API or local cache
        $feedbackFile = __DIR__ . '/../var/beta-feedback.json';
        if (file_exists($feedbackFile)) {
            $data = json_decode(file_get_contents($feedbackFile), true);
            $this->issues = $data['issues'] ?? [];
            $this->metrics = $data['metrics'] ?? [];
        } else {
            // Simulate data for demonstration
            $this->simulateFeedbackData();
        }

        $this->output->writeln(sprintf('Loaded %d feedback items', count($this->issues)));
    }

    private function analyzeAutomationMetrics(): void
    {
        $this->output->writeln("\n<comment>Analyzing automation metrics...</comment>");

        $automationRates = [];
        $migrationTimes = [];
        $manualInterventions = [];

        foreach ($this->issues as $issue) {
            if (isset($issue['automation_percentage'])) {
                $automationRates[] = $issue['automation_percentage'];
            }
            if (isset($issue['migration_time_minutes'])) {
                $migrationTimes[] = $issue['migration_time_minutes'];
            }
            if (isset($issue['manual_interventions'])) {
                $manualInterventions[] = $issue['manual_interventions'];
            }
        }

        if (!empty($automationRates)) {
            $avgAutomation = array_sum($automationRates) / count($automationRates);
            $minAutomation = min($automationRates);
            $maxAutomation = max($automationRates);

            $this->metrics['automation'] = [
                'average' => round($avgAutomation, 2),
                'min' => $minAutomation,
                'max' => $maxAutomation,
                'target_met' => $avgAutomation >= 70,
                'samples' => count($automationRates),
            ];

            $this->output->writeln(sprintf(
                'Average automation: <fg=%s>%.2f%%</> (Target: 70%%)',
                $avgAutomation >= 70 ? 'green' : 'red',
                $avgAutomation
            ));
        }

        if (!empty($migrationTimes)) {
            $avgTime = array_sum($migrationTimes) / count($migrationTimes);
            $this->metrics['migration_time'] = [
                'average_minutes' => round($avgTime, 2),
                'total_projects' => count($migrationTimes),
            ];

            $this->output->writeln(sprintf('Average migration time: %.1f minutes', $avgTime));
        }

        if (!empty($manualInterventions)) {
            $avgInterventions = array_sum($manualInterventions) / count($manualInterventions);
            $this->metrics['manual_work'] = [
                'average_interventions' => round($avgInterventions, 2),
                'zero_intervention_rate' => count(array_filter($manualInterventions, fn($i) => $i === 0)) / count($manualInterventions) * 100,
            ];
        }
    }

    private function analyzePerformanceMetrics(): void
    {
        $this->output->writeln("\n<comment>Analyzing performance metrics...</comment>");

        $performanceImprovements = [];
        foreach ($this->issues as $issue) {
            if (isset($issue['performance_impact'])) {
                $performanceImprovements[] = $issue['performance_impact'];
            }
        }

        if (!empty($performanceImprovements)) {
            $improvements = array_count_values($performanceImprovements);
            $this->metrics['performance'] = $improvements;

            $positive = ($improvements['significant_improvement'] ?? 0) + ($improvements['moderate_improvement'] ?? 0);
            $total = array_sum($improvements);
            $positiveRate = $total > 0 ? ($positive / $total) * 100 : 0;

            $this->output->writeln(sprintf(
                'Performance improvement rate: <fg=green>%.1f%%</>',
                $positiveRate
            ));
        }
    }

    private function analyzeIssuePatterns(): void
    {
        $this->output->writeln("\n<comment>Analyzing issue patterns...</comment>");

        $issueTypes = [];
        $failedRules = [];
        $manualFixTypes = [];

        foreach ($this->issues as $issue) {
            if (isset($issue['issue_type'])) {
                $issueTypes[] = $issue['issue_type'];
            }
            if (isset($issue['failed_rules'])) {
                foreach ($issue['failed_rules'] as $rule) {
                    $failedRules[] = $rule;
                }
            }
            if (isset($issue['manual_fix_types'])) {
                foreach ($issue['manual_fix_types'] as $type) {
                    $manualFixTypes[] = $type;
                }
            }
        }

        if (!empty($issueTypes)) {
            $this->metrics['issue_patterns'] = [
                'types' => array_count_values($issueTypes),
                'total' => count($issueTypes),
            ];
        }

        if (!empty($failedRules)) {
            $ruleCounts = array_count_values($failedRules);
            arsort($ruleCounts);
            $this->metrics['problematic_rules'] = array_slice($ruleCounts, 0, 5, true);

            $this->output->writeln('Top problematic rules:');
            foreach ($this->metrics['problematic_rules'] as $rule => $count) {
                $this->output->writeln(sprintf('  - %s: %d failures', $rule, $count));
            }
        }

        if (!empty($manualFixTypes)) {
            $fixCounts = array_count_values($manualFixTypes);
            arsort($fixCounts);
            $this->metrics['manual_fix_patterns'] = array_slice($fixCounts, 0, 5, true);
        }
    }

    private function generateRecommendations(): void
    {
        $this->output->writeln("\n<comment>Generating recommendations...</comment>");

        // Automation recommendations
        if (isset($this->metrics['automation']['average'])) {
            if ($this->metrics['automation']['average'] < 70) {
                $this->recommendations[] = [
                    'type' => 'critical',
                    'area' => 'automation',
                    'message' => 'Automation target not met. Focus on improving Rector rules for common patterns.',
                ];
            } elseif ($this->metrics['automation']['average'] < 80) {
                $this->recommendations[] = [
                    'type' => 'improvement',
                    'area' => 'automation',
                    'message' => 'Good automation rate. Consider adding rules for edge cases to reach 80%+.',
                ];
            }
        }

        // Performance recommendations
        if (isset($this->metrics['performance'])) {
            $degradations = ($this->metrics['performance']['slight_degradation'] ?? 0) +
                          ($this->metrics['performance']['significant_degradation'] ?? 0);
            if ($degradations > 0) {
                $this->recommendations[] = [
                    'type' => 'warning',
                    'area' => 'performance',
                    'message' => sprintf('%d projects reported performance degradation. Investigate and optimize.', $degradations),
                ];
            }
        }

        // Rule recommendations
        if (isset($this->metrics['problematic_rules'])) {
            foreach ($this->metrics['problematic_rules'] as $rule => $count) {
                if ($count > 5) {
                    $this->recommendations[] = [
                        'type' => 'improvement',
                        'area' => 'rector_rules',
                        'message' => sprintf('Rule "%s" has %d failures. Consider improving or providing manual migration guide.', $rule, $count),
                    ];
                }
            }
        }

        // Manual intervention recommendations
        if (isset($this->metrics['manual_fix_patterns'])) {
            foreach ($this->metrics['manual_fix_patterns'] as $pattern => $count) {
                if ($count > 10) {
                    $this->recommendations[] = [
                        'type' => 'improvement',
                        'area' => 'documentation',
                        'message' => sprintf('Pattern "%s" requires manual fixes in %d cases. Add to migration guide.', $pattern, $count),
                    ];
                }
            }
        }
    }

    private function outputReport(): void
    {
        $this->output->writeln("\n<info>Beta Testing Report Summary</info>");
        $this->output->writeln('============================');

        // Automation Summary
        if (isset($this->metrics['automation'])) {
            $this->output->writeln("\n<comment>Automation Metrics:</comment>");
            $table = new Table($this->output);
            $table->setHeaders(['Metric', 'Value', 'Status']);
            $table->addRow([
                'Average Automation',
                sprintf('%.2f%%', $this->metrics['automation']['average']),
                $this->metrics['automation']['target_met'] ? '<fg=green>✓</>' : '<fg=red>✗</>',
            ]);
            $table->addRow([
                'Range',
                sprintf('%.0f%% - %.0f%%', $this->metrics['automation']['min'], $this->metrics['automation']['max']),
                '',
            ]);
            $table->addRow([
                'Sample Size',
                $this->metrics['automation']['samples'],
                '',
            ]);
            $table->render();
        }

        // Issue Summary
        if (isset($this->metrics['issue_patterns'])) {
            $this->output->writeln("\n<comment>Issue Distribution:</comment>");
            $table = new Table($this->output);
            $table->setHeaders(['Issue Type', 'Count', 'Percentage']);
            $total = $this->metrics['issue_patterns']['total'];
            foreach ($this->metrics['issue_patterns']['types'] as $type => $count) {
                $table->addRow([
                    $type,
                    $count,
                    sprintf('%.1f%%', ($count / $total) * 100),
                ]);
            }
            $table->render();
        }

        // Recommendations
        if (!empty($this->recommendations)) {
            $this->output->writeln("\n<comment>Recommendations:</comment>");
            foreach ($this->recommendations as $rec) {
                $color = match($rec['type']) {
                    'critical' => 'red',
                    'warning' => 'yellow',
                    'improvement' => 'cyan',
                    default => 'white',
                };
                $this->output->writeln(sprintf(
                    '  <fg=%s>[%s]</> %s',
                    $color,
                    strtoupper($rec['type']),
                    $rec['message']
                ));
            }
        }

        // Overall Status
        $this->output->writeln("\n<comment>Overall Status:</comment>");
        $readyForRelease = $this->calculateReleaseReadiness();
        $this->output->writeln(sprintf(
            'Release Readiness: <fg=%s>%s</>',
            $readyForRelease ? 'green' : 'yellow',
            $readyForRelease ? 'READY' : 'NEEDS IMPROVEMENT'
        ));

        // Export detailed report
        $this->exportDetailedReport();
    }

    private function calculateReleaseReadiness(): bool
    {
        $criteria = [
            'automation_target' => ($this->metrics['automation']['average'] ?? 0) >= 70,
            'no_critical_issues' => !$this->hasCriticalIssues(),
            'performance_acceptable' => $this->isPerformanceAcceptable(),
            'sufficient_feedback' => ($this->metrics['automation']['samples'] ?? 0) >= 10,
        ];

        return !in_array(false, $criteria, true);
    }

    private function hasCriticalIssues(): bool
    {
        foreach ($this->recommendations as $rec) {
            if ($rec['type'] === 'critical') {
                return true;
            }
        }
        return false;
    }

    private function isPerformanceAcceptable(): bool
    {
        if (!isset($this->metrics['performance'])) {
            return true;
        }

        $degradations = ($this->metrics['performance']['significant_degradation'] ?? 0);
        $total = array_sum($this->metrics['performance']);

        return $total === 0 || ($degradations / $total) < 0.1;
    }

    private function exportDetailedReport(): void
    {
        $reportFile = __DIR__ . '/../var/beta-analysis-report.yaml';
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'metrics' => $this->metrics,
            'recommendations' => $this->recommendations,
            'release_ready' => $this->calculateReleaseReadiness(),
        ];

        file_put_contents($reportFile, Yaml::dump($report, 6));
        $this->output->writeln(sprintf("\n<info>Detailed report saved to: %s</info>", $reportFile));
    }

    private function simulateFeedbackData(): void
    {
        // Simulate realistic beta feedback data for testing
        $this->issues = [
            [
                'id' => 1,
                'automation_percentage' => 75,
                'migration_time_minutes' => 15,
                'manual_interventions' => 3,
                'performance_impact' => 'moderate_improvement',
                'issue_type' => 'rector_conversion',
                'failed_rules' => ['ComplexAnnotationRector'],
                'manual_fix_types' => ['doctrine_integration'],
            ],
            [
                'id' => 2,
                'automation_percentage' => 82,
                'migration_time_minutes' => 8,
                'manual_interventions' => 1,
                'performance_impact' => 'significant_improvement',
                'issue_type' => 'success',
            ],
            [
                'id' => 3,
                'automation_percentage' => 68,
                'migration_time_minutes' => 25,
                'manual_interventions' => 7,
                'performance_impact' => 'no_change',
                'issue_type' => 'rector_conversion',
                'failed_rules' => ['ComplexAnnotationRector', 'PropertyPromotionRector'],
                'manual_fix_types' => ['custom_processors', 'doctrine_integration'],
            ],
            [
                'id' => 4,
                'automation_percentage' => 90,
                'migration_time_minutes' => 5,
                'manual_interventions' => 0,
                'performance_impact' => 'significant_improvement',
                'issue_type' => 'success',
            ],
            [
                'id' => 5,
                'automation_percentage' => 71,
                'migration_time_minutes' => 18,
                'manual_interventions' => 4,
                'performance_impact' => 'moderate_improvement',
                'issue_type' => 'documentation',
                'manual_fix_types' => ['route_configuration'],
            ],
        ];
    }
}

// Run the analyzer
$analyzer = new BetaFeedbackAnalyzer();
$analyzer->run();