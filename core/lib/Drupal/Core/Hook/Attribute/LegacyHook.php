<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Defines a LegacyHook attribute object.
 *
 * This allows contrib and core to maintain legacy hook implementations
 * alongside the new attribute-based hooks. This means that a contrib module can
 * simultaneously support Drupal 11 and older versions of Drupal that only
 * support procedural hooks.
 *
 * Marking a procedural hook as #LegacyHook will prevent duplicate executions of
 * attribute-based hooks.
 *
 * On older versions of Drupal which are not aware of attribute-based hooks,
 * only the legacy hook implementation is executed.
 *
 * For more information, see https://www.drupal.org/node/3442349.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class LegacyHook {

}
