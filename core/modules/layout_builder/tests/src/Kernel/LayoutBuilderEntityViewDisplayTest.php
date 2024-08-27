<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * @coversDefaultClass \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay
 *
 * @group layout_builder
 */
class LayoutBuilderEntityViewDisplayTest extends SectionListTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionList(array $section_data) {
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
  public function testInvalidConfiguration(): void {
    $this->expectException(SchemaIncompleteException::class);
    $this->sectionList->getSection(0)->getComponent('10000000-0000-1000-a000-000000000000')->setConfiguration(['id' => 'foo', 'bar' => 'baz']);
    $this->sectionList->save();
  }

  /**
   * @dataProvider providerTestIsLayoutBuilderEnabled
   */
  public function testIsLayoutBuilderEnabled($expected, $view_mode, $enabled): void {
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
  public static function providerTestIsLayoutBuilderEnabled() {
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
  public function testSetOverridable(): void {
    // Disable Layout Builder.
    $this->sectionList->disableLayoutBuilder();

    // Set Overridable to TRUE and ensure Layout Builder is enabled.
    $this->sectionList->setOverridable();
    $this->assertTrue($this->sectionList->isLayoutBuilderEnabled());

    // Ensure Layout Builder is still enabled after setting Overridable to FALSE.
    $this->sectionList->setOverridable(FALSE);
    $this->assertTrue($this->sectionList->isLayoutBuilderEnabled());
  }

}
