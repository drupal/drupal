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
    $this->expectException(SchemaIncompleteException::class);
    $this->sectionStorage->getSection(0)->getComponent('first-uuid')->setConfiguration(['id' => 'foo', 'bar' => 'baz']);
    $this->sectionStorage->save();
  }

  /**
   * @dataProvider providerTestIsLayoutBuilderEnabled
   */
  public function testIsLayoutBuilderEnabled($expected, $view_mode, $enabled) {
    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => $view_mode,
      'status' => TRUE,
      'third_party_settings' => [
        'layout_builder' => [
          'enabled' => $enabled,
        ],
      ],
    ]);
    $result = $display->isLayoutBuilderEnabled();
    $this->assertSame($expected, $result);
  }

  /**
   * Provides test data for ::testIsLayoutBuilderEnabled().
   */
  public function providerTestIsLayoutBuilderEnabled() {
    $data = [];
    $data['default enabled'] = [TRUE, 'default', TRUE];
    $data['default disabled'] = [FALSE, 'default', FALSE];
    $data['full enabled'] = [TRUE, 'full', TRUE];
    $data['full disabled'] = [FALSE, 'full', FALSE];
    $data['_custom enabled'] = [FALSE, '_custom', TRUE];
    $data['_custom disabled'] = [FALSE, '_custom', FALSE];
    return $data;
  }

  /**
   * Tests that setting overridable enables Layout Builder only when TRUE.
   */
  public function testSetOverridable() {
    // Disable Layout Builder.
    $this->sectionStorage->disableLayoutBuilder();

    // Set Overridable to TRUE and ensure Layout Builder is enabled.
    $this->sectionStorage->setOverridable();
    $this->assertTrue($this->sectionStorage->isLayoutBuilderEnabled());

    // Ensure Layout Builder is still enabled after setting Overridable to FALSE.
    $this->sectionStorage->setOverridable(FALSE);
    $this->assertTrue($this->sectionStorage->isLayoutBuilderEnabled());
  }

}
