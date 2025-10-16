<?php

/**
 * @file
 * Tests deprecated include files.
 */

declare(strict_types=1);

/**
 * Template preprocess function for module_test_procedural_preprocess_theme.
 */
function template_preprocess_module_test_procedural_preprocess_theme(array $variables): void {
  $variables['test'] = 'test';
}
