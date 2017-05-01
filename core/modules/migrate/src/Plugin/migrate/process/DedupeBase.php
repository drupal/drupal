<?php

namespace Drupal\migrate\Plugin\migrate\process;

@trigger_error('The ' . __NAMESPACE__ . ' \DedupeEntityBase is deprecated in
Drupal 8.4.x and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . ' \MakeUniqueEntityFieldBase', E_USER_DEPRECATED);

/**
 * This abstract base contains the dedupe logic.
 *
 * These plugins avoid duplication at the destination. For example, when
 * creating filter format names, the current value is checked against the
 * existing filter format names and if it exists, a numeric postfix is added
 * and incremented until a unique value is created.
 *
 * @link https://www.drupal.org/node/2345929 Online handbook documentation for dedupebase process plugin @endlink
 *
 * @deprecated in Drupal 8.4.x and will be removed in Drupal 9.0.x. Use
 *   \Drupal\migrate\Plugin\migrate\process\MakeUniqueBase instead.
 *
 * @see https://www.drupal.org/node/2873762
 */
abstract class DedupeBase extends MakeUniqueBase {
}
