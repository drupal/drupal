<?php

declare(strict_types=1);

namespace Drupal\Core\Hook\Attribute;

/**
 * This class will not have an effect until Drupal 11.1.0.
 *
 * This class is included in earlier Drupal versions to prevent phpstan errors
 * for modules implementing object oriented hooks using the #Hook and
 * #LegacyHook attributes.
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
class LegacyHook {

}
