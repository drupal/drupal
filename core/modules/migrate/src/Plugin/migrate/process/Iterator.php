<?php

namespace Drupal\migrate\Plugin\migrate\process;

@trigger_error('The ' . __NAMESPACE__ . '\Iterator is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\SubProcess', E_USER_DEPRECATED);

/**
 * Iterates and processes an associative array.
 *
 * @deprecated in Drupal 8.4.x and will be removed in Drupal 9.0.x. Use
 *   \Drupal\migrate\Plugin\migrate\process\SubProcess instead.
 *
 * @see https://www.drupal.org/node/2880427
 *
 * @MigrateProcessPlugin(
 *   id = "iterator",
 *   handle_multiples = TRUE
 * )
 */
class Iterator extends SubProcess {}
