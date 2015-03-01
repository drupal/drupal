<?php

/**
 * @file
 * Definition of \Drupal\views\Tests\Handler\FieldEntityOperationsTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the core Drupal\views\Plugin\views\field\EntityOperations handler.
 *
 * @group views
 */
class FieldEntityOperationsTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_operations');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test');

  /**
   * Tests entity operations field.
   */
  public function testEntityOperations() {
    // Create some test entities.
    for ($i = 0; $i < 5; $i++) {
      EntityTest::create(array(
        'name' => $this->randomString(),
      ))->save();
    }
    $entities = EntityTest::loadMultiple();

    $admin_user = $this->drupalCreateUser(array('access administration pages', 'administer entity_test content'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('test-entity-operations');

    foreach ($entities as $entity) {
      $operations = \Drupal::entityManager()->getListBuilder('entity_test')->getOperations($entity);
      foreach ($operations as $operation) {
        $expected_destination = Url::fromUri('internal:/test-entity-operations')->toString();
        $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', array(':path' => $operation['url']->toString() . '?destination=' . $expected_destination, ':title' => $operation['title']));
        $this->assertEqual(count($result), 1, t('Found entity @operation link with destination parameter.', array('@operation' => $operation['title'])));
      }
    }
  }

}
