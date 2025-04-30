<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Attribute for defining a class method as a preprocess function.
 *
 * Pass no arguments for hook_preprocess `#[Preprocess]`.
 * For `hook_preprocess_HOOK` pass the `HOOK` without the `hook_preprocess`
 * portion `#[Preprocess('HOOK')]`.
 *
 * See \Drupal\Core\Hook\Attribute\Hook for additional information.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Preprocess extends Hook {
  /**
   * {@inheritdoc}
   */
  public const string PREFIX = 'preprocess';

}
