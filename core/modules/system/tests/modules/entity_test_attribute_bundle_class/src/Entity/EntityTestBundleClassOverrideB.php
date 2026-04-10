<?php

declare(strict_types=1);

namespace Drupal\entity_test_attribute_bundle_class\Entity;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Overrides bundle class for the entity_test entity type bundle_class_b bundle.
 */
#[Bundle(
  entityType: 'entity_test',
  bundle: 'bundle_class_b',
)]
class EntityTestBundleClassOverrideB extends EntityTest {}
