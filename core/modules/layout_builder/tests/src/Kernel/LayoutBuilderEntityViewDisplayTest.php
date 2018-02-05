<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
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

}
