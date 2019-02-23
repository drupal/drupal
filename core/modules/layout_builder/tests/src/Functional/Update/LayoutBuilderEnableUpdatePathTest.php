<?php

namespace Drupal\Tests\layout_builder\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for enabling Layout Builder.
 *
 * @see layout_builder_update_8601()
 *
 * @group layout_builder
 * @group legacy
 */
class LayoutBuilderEnableUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../fixtures/update/layout-builder-enable.php',
    ];
  }

  /**
   * Tests the upgrade path for enabling Layout Builder.
   */
  public function testRunUpdates() {
    $assert_session = $this->assertSession();

    $expected = [
      'sections' => [
        [
          'layout_id' => 'layout_onecol',
          'layout_settings' => [],
          'components' => [
            'some-uuid' => [
              'uuid' => 'some-uuid',
              'region' => 'content',
              'configuration' => [
                'id' => 'system_powered_by_block',
              ],
              'additional' => [],
              'weight' => 0,
            ],
          ],
          'third_party_settings' => [],
        ],
      ],
    ];
    $this->assertLayoutBuilderSettings($expected, 'block_content', 'basic', 'default');
    $this->assertLayoutBuilderSettings(NULL, 'node', 'page', 'default');

    $this->runUpdates();

    // The display with existing sections is enabled while the other is not.
    $expected['enabled'] = TRUE;
    $this->assertLayoutBuilderSettings($expected, 'block_content', 'basic', 'default');
    $this->assertLayoutBuilderSettings(NULL, 'node', 'page', 'default');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/structure/block/block-content/manage/basic/display');
    $assert_session->checkboxChecked('layout[enabled]');
    $this->drupalGet('admin/structure/types/manage/page/display');
    $assert_session->checkboxNotChecked('layout[enabled]');
  }

  /**
   * Asserts the Layout Builder settings for a given display.
   *
   * @param mixed $expected
   *   The expected value.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   */
  protected function assertLayoutBuilderSettings($expected, $entity_type_id, $bundle, $view_mode) {
    $this->assertEquals($expected, \Drupal::config("core.entity_view_display.$entity_type_id.$bundle.$view_mode")->get('third_party_settings.layout_builder'));
  }

}
