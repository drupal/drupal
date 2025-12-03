<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * Prevents procedural requirements hook from executing.
 *
 * This allows the use of the legacy hook_requirements() and
 * hook_requirements_alter() alongside the OOP replacements.
 *
 * Marking requirements hooks as #LegacyRequirementsHook will prevent them
 * from running on Drupal 11.3.0 and later.
 *
 * Note that Drupal 11.2 supports both legacy and new OOP requirements hooks
 * and will invoke both as this attribute is not recognized there.
 *
 * On older versions of Drupal which are not aware of the new requirement hooks,
 * only the legacy hook implementation is executed.
 *
 * Adding this attribute will also skip deprecation messages on Drupal 11.3 and
 * later.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class LegacyRequirementsHook {}
