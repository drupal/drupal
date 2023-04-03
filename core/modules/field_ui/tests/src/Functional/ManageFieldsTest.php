<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Tests\BrowserTestBase;

// cSpell:ignore downlander

/**
 * Tests the Manage Display page of a fieldable entity type.
 *
 * @group field_ui
 */
class ManageFieldsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'field_ui_test',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->drupalCreateUser(['administer node fields']);
    $this->drupalLogin($account);
    $this->config('system.logging')
      ->set('error_level', ERROR_REPORTING_DISPLAY_ALL)
      ->save();
  }

  /**
   * Tests drop button operations on the manage fields page.
   */
  public function testFieldDropButtonOperations() {
    $assert_session = $this->assertSession();

    $node_type = $this->drupalCreateContentType();
    $bundle = $node_type->id();

    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'string',
        'field_name' => 'highlander',
        'entity_type' => 'node',
      ]);
    $storage->save();

    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $bundle,
      ])
      ->save();

    $this->drupalGet("/admin/structure/types/manage/{$bundle}/fields");

    // Check that the summary element for the string field type exists and has
    // the correct text (which comes from the FieldItemBase class).
    $element = $assert_session->elementExists('css', '#highlander');
    $summary = $assert_session->elementExists('css', '.field-settings-summary-cell > ul > li', $element);
    $field_label = $this->container->get('plugin.manager.field.field_type')->getDefinitions()['string']['label'];
    $this->assertEquals($field_label, $summary->getText());

    // Add an entity reference field, and check that its summary is custom.
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->create([
        'type' => 'entity_reference',
        'field_name' => 'downlander',
        'entity_type' => 'node',
        'settings' => [
          'target_type' => 'node',
        ],
      ]);
    $storage->save();

    $this->container->get('entity_type.manager')
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $bundle,
        'entity_type' => 'node',
        'settings' => [
          'handler_settings' => [
            'target_bundles' => [$bundle => $bundle],
          ],
        ],
      ])
      ->save();

    $this->drupalGet("/admin/structure/types/manage/{$bundle}/fields");
    $element = $assert_session->elementExists('css', '#downlander');
    $custom_summary_text = 'Reference type: Content';
    $allowed_bundles_text = "Content type: $bundle";
    $this->assertStringContainsString($custom_summary_text, $element->getText());
    $this->assertStringContainsString($allowed_bundles_text, $element->getText());
  }

}
