<?php

namespace Drupal\Core\Theme;

/**
 * Enum for supported extension types.
 *
 * @todo Replace this enum with Drupal\Core\Extension\ExtensionTypeInterface.
 * @see https://www.drupal.org/i/3352546
 *
 * @internal
 */
enum ExtensionType: string {
  case Module = 'module';
  case Theme = 'theme';
}
