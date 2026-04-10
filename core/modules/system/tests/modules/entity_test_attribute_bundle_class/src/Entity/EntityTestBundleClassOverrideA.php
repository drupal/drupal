<?php

declare(strict_types=1);

namespace Drupal\entity_test_attribute_bundle_class\Entity;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Overrides bundle class for the entity_test entity type bundle_class_a bundle.
 */
#[Bundle(
  entityType: 'entity_test',
  bundle: 'bundle_class_a',
  label: new TranslatableMarkup('Bundle class A label set by attribute'),
  translatable: FALSE,
)]
class EntityTestBundleClassOverrideA extends EntityTest {}
