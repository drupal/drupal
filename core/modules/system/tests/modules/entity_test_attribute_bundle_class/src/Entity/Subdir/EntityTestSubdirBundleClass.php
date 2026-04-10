<?php

declare(strict_types=1);

namespace Drupal\entity_test_attribute_bundle_class\Entity\Subdir;

use Drupal\Core\Entity\Attribute\Bundle;
use Drupal\entity_test\Entity\EntityTest;

/**
 * A bundle class that is in a subdirectory.
 */
#[Bundle(
  entityType: 'entity_test',
  bundle: 'subdir_bundle_class',
)]
class EntityTestSubdirBundleClass extends EntityTest {}
