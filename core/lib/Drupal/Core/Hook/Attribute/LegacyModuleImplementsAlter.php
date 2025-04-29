<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Prevents procedural hook_module_implements_alter from executing.
 *
 * This allows the use of the legacy hook_module_implements_alter alongside
 * attribute-based ordering. Providing support for versions of Drupal older
 * than 11.2.0.
 *
 * Marking hook_module_implements_alter as #LegacyModuleImplementsAlter will
 * prevent hook_module_implements_alter from running when attribute-based
 * ordering is available.
 *
 * On older versions of Drupal which are not aware of attribute-based ordering,
 * only the legacy hook implementation is executed.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class LegacyModuleImplementsAlter {}
