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
   * @group legacy
   * @expectedDeprecation Passing the entity.manager service to ContentEntityForm::__construct() is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Pass the entity.repository service instead. See https://www.drupal.org/node/2549139.
   */
  public function testEntityManagerDeprecation() {
    $entity_manager = $this->prophesize(EntityManagerInterface::class)->reveal();
    $entity_type_bundle_info = $this->prophesize(EntityTypeBundleInfoInterface::class)->reveal();
    $time = $this->prophesize(TimeInterface::class)->reveal();
    $form = new ContentEntityForm($entity_manager, $entity_type_bundle_info, $time);

    $reflected_form = new \ReflectionClass($form);
    $entity_manager_property = $reflected_form->getProperty('entityManager');
    $entity_manager_property->setAccessible(TRUE);
    $this->assertTrue($entity_manager_property->getValue($form) === $entity_manager);
  }

}
