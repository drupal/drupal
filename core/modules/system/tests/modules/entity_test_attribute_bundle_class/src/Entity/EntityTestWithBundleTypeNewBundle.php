<?php

declare(strict_types=1);

namespace Drupal\entity_test_attribute_bundle_class\Entity;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_test\Entity\EntityTestWithBundle;

/**
 * Sets bundle class for 'new_bundle' bundle for 'entity_test_with_bundle' type.
 */
#[Bundle(
  entityType: 'entity_test_with_bundle',
  bundle: 'new_bundle',
  label: new TranslatableMarkup('A new bundle for an entity type with a bundle entity type'),
  translatable: FALSE,
)]
class EntityTestWithBundleTypeNewBundle extends EntityTestWithBundle {}
