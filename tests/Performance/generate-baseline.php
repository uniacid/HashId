#!/usr/bin/env php
<?php

/**
 * Generate baseline performance metrics for HashId Bundle.
 *
 * Run this script to create baseline-v3.json:
 * php tests/Performance/generate-baseline.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Pgs\HashIdBundle\Tests\Performance\Baseline\BaselineGenerator;

echo "HashId Bundle Performance Baseline Generator\n";
echo "=============================================\n\n";

$generator = new BaselineGenerator();
$generator->generate();
$generator->save(__DIR__ . '/baseline-v3.json');
$generator->printSummary();

echo "\n✅ Baseline generation complete!\n";