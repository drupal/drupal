<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Defines a ExtensionFileIsConverted attribute object.
 *
 * This prevents deprecation messages for .theme or .module files to allow
 * legacy tests or supporting multiple versions of core. It must be on the
 * first function in the .theme or .module file. This must only be used for
 * legacy support, the extension must fully work without the file in versions
 * >=11.3.0. If the minimum supported version of core is 11.3.0 then this
 * attribute must not be used.
 *
 * @see https://www.drupal.org/node/3581222
 * @see https://www.drupal.org/node/3551652
 * @see https://www.drupal.org/node/3619765
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class ExtensionFileIsConverted {}
