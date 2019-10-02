<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Deprecated EntityDisplayBase functionality.
 *
 * @group Entity
 * @group legacy
 */
class LegacyEntityDisplayBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'entity_test_third_party',
    'field',
    'system',
    'comment',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests legacy handling of 'type' => 'hidden'.
   *
   * @group legacy
   *
   * @expectedDeprecation Support for using 'type' => 'hidden' in a component is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use 'region' => 'hidden' instead. See https://www.drupal.org/node/2801513
   */
  public function testLegacyPreSave() {
    $entity_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'foo' => ['type' => 'visible'],
        'bar' => ['type' => 'hidden'],
        'name' => ['type' => 'hidden', 'region' => 'content'],
      ],
    ]);

    // Ensure that no region is set on the component.
    $this->assertArrayNotHasKey('region', $entity_display->getComponent('foo'));
    $this->assertArrayNotHasKey('region', $entity_display->getComponent('bar'));

    // Ensure that a region is set on the component after saving.
    $entity_display->save();

    // The component with a visible type has been assigned a region.
    $component = $entity_display->getComponent('foo');
    $this->assertArrayHasKey('region', $component);
    $this->assertSame('content', $component['region']);

    // The component with a hidden type has been removed.
    $this->assertNull($entity_display->getComponent('bar'));

    // The component with a valid region and hidden type is unchanged.
    $component = $entity_display->getComponent('name');
    $this->assertArrayHasKey('region', $component);
    $this->assertSame('content', $component['region']);
  }

  /**
   * Tests the deprecated ::handleHiddenType() method.
   *
   * @expectedDeprecation Drupal\Core\Entity\EntityDisplayBase::handleHiddenType is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. No replacement is provided. See https://www.drupal.org/node/2801513
   */
  public function testHandleHiddenType() {
    $entity_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'foo' => ['type' => 'visible'],
        'bar' => ['type' => 'hidden'],
        'name' => ['type' => 'hidden', 'region' => 'content'],
      ],
    ]);
    $method = new \ReflectionMethod($entity_display, 'handleHiddenType');
    $method->setAccessible(TRUE);
    $this->assertSame(['type' => 'hidden'], $entity_display->getComponent('bar'));
    $method->invoke($entity_display, 'bar', ['type' => 'hidden']);
    $this->assertNull($entity_display->getComponent('bar'));
  }

}
