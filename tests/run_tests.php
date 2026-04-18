<?php

require_once __DIR__ . '/LatteEngineTest.php';

use ProcessWire\LatteEngineTest;

$testClass = new LatteEngineTest();
$methods = get_class_methods($testClass);

$passed = 0;
$failed = 0;

foreach ($methods as $method) {
    if (strpos($method, 'test') === 0) {
        echo "Running $method... ";
        try {
            // Call setUp if it exists
            if (method_exists($testClass, 'setUp')) {
                $testClass->setUp();
            }

            if ($testClass->$method()) {
                echo "PASSED\n";
                $passed++;
            } else {
                echo "FAILED\n";
                $failed++;
            }
        } catch (\Throwable $e) {
            echo "FAILED with exception: " . $e->getMessage() . "\n";
            // echo $e->getTraceAsString() . "\n";
            $failed++;
        }
    }
}

echo "\nTests completed: $passed passed, $failed failed.\n";

exit($failed > 0 ? 1 : 0);
