<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * @coversDefaultClass \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay
 *
 * @group layout_builder
 */
class LayoutBuilderEntityViewDisplayTest extends SectionStorageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionStorage(array $section_data) {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'third_party_settings' => [
        'layout_builder' => [
          'enabled' => TRUE,
          'sections' => $section_data,
        ],
      ],
    ]);
    $display->save();
    return $display;
  }

  /**
   * Tests that configuration schema enforces valid values.
   */
  public function testInvalidConfiguration() {
    $this->setExpectedException(SchemaIncompleteException::class);
    $this->sectionStorage->getSection(0)->getComponent('first-uuid')->setConfiguration(['id' => 'foo', 'bar' => 'baz']);
    $this->sectionStorage->save();
  }

  /**
   * @covers ::getRuntimeSections
   * @group legacy
   * @expectedDeprecation \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay::getRuntimeSections() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface::findByContext() should be used instead. See https://www.drupal.org/node/3022574.
   */
  public function testGetRuntimeSections() {
    $this->container->get('current_user')->setAccount($this->createUser());

    $entity = EntityTest::create();
    $entity->save();

    $reflection = new \ReflectionMethod($this->sectionStorage, 'getRuntimeSections');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->sectionStorage, $entity);

    $this->assertEquals($this->sectionStorage->getSections(), $result);
  }

}
