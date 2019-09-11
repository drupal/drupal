<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityForm
 * @group Entity
 */
class ContentEntityFormTest extends UnitTestCase {

  /**
   * Tests the constructor bc layer for injecting the entity manager.
   *
   * @group legacy
   * @expectedDeprecation Passing the entity.manager service to ContentEntityForm::__construct() is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Pass the entity.repository service instead. See https://www.drupal.org/node/2549139.
   * @expectedDeprecation EntityForm::entityManager is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use EntityForm::entityTypeManager instead. See https://www.drupal.org/node/2549139
   */
  public function testEntityManagerDeprecation() {
    $entity_manager = $this->prophesize(EntityManagerInterface::class)->reveal();
    $entity_type_bundle_info = $this->prophesize(EntityTypeBundleInfoInterface::class)->reveal();
    $time = $this->prophesize(TimeInterface::class)->reveal();
    $form = new ContentEntityForm($entity_manager, $entity_type_bundle_info, $time);
    $this->assertSame($form->entityManager, $entity_manager);
  }

}
