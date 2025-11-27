<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay.
 */
#[CoversClass(LayoutBuilderEntityViewDisplay::class)]
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
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
    $this->sectionList->getSection(0)
      ->getComponent('10000000-0000-1000-a000-000000000000')
      ->setConfiguration(['id' => 'foo', 'bar' => 'baz']);
    $this->sectionList->save();
  }

  /**
 * Tests is layout builder enabled.
 */
  #[DataProvider('providerTestIsLayoutBuilderEnabled')]
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

    // Ensure Layout Builder is still enabled after setting Overridable to
    // FALSE.
    $this->sectionList->setOverridable(FALSE);
    $this->assertTrue($this->sectionList->isLayoutBuilderEnabled());
  }

  /**
   * Tests that enabling Layout Builder moves fields to hidden.
   */
  public function testFieldsMovedToHiddenOnEnable(): void {
    $display = LayoutBuilderEntityViewDisplay::load('entity_test.entity_test.default');
    $display->disableLayoutBuilder()->save();
    $display->trustData();
    $this->assertNotEmpty($display->get('content'));
    $this->assertNotContains('langcode', $display->get('hidden'));
    $this->assertNotContains('name', $display->get('hidden'));
    $display->enableLayoutBuilder()->save();
    $this->assertEmpty($display->get('content'));
    $this->assertEquals([
      'langcode' => TRUE,
      'name' => TRUE,
    ], $display->get('hidden'));
  }

  /**
   * Tests that buildMultiple doesn't build sections if storage isn't supported.
   */
  public function testBuildOnlyWhenSupported(): void {
    $display = LayoutBuilderEntityViewDisplay::load('entity_test.entity_test.default');
    $entity = EntityTest::create(['type' => 'entity_test', 'name' => 'test']);
    $entity->save();
    $buildList = $display->buildMultiple([$entity->id() => $entity]);
    $this->assertArrayHasKey('_layout_builder', $buildList[$entity->id()]);

    // Disable layout_builder for this display and check sections aren't
    // built.
    $display->disableLayoutBuilder()->save();
    $buildList = $display->buildMultiple([$entity->id() => $entity]);
    $this->assertArrayNotHasKey('_layout_builder', $buildList[$entity->id()]);
  }

}
