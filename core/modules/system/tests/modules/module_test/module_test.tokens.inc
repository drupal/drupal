<?php

/**
 * @file
 * Tests system_hook_info.
 */

declare(strict_types=1);

/**
 * Implements hook_token_info().
 */
function module_test_token_info(): array {
  $testToken = [];
  $testToken['types']['token_info_invoked'] = [
    'name' => 'tokens.inc test',
    'description' => 'tokens.inc test',
  ];
  $testToken['tokens']['token_info_invoked']['token_test_1'] = [
    'type' => 'text',
    'name' => 'tokens.inc test 1',
  ];

  return $testToken;
}
