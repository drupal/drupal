<?php

declare(strict_types=1);

namespace Drupal\taxonomy_overview_override_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\taxonomy_overview_override_test\Form\OverviewTermsOverride;

/**
 * Hook implementations for taxonomy_overview_override_test.
 */
class TaxonomyOverviewOverrideHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    $entity_types['taxonomy_vocabulary']->setFormClass('overview', OverviewTermsOverride::class);
  }

}
