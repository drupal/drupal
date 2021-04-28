<?php

namespace Drupal\Tests\media_library\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the media library field widget.
 *
 * @group media_library
 */
class FieldWidgetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_library',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests saving a required media library field without a value.
   */
  public function testEmptyValue() {
    $node_type = $this->drupalCreateContentType()->id();

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_media',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'bundle' => $node_type,
      'field_storage' => $field_storage,
      'required' => TRUE,
    ])->save();

    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', $node_type)
      ->setComponent('field_media', [
        'type' => 'media_library_widget',
      ])
      ->save();

    $account = $this->drupalCreateUser(["create $node_type content"]);
    $this->drupalLogin($account);
    $this->drupalGet("/node/add/$node_type");
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextNotContains('This value should not be null');
    $this->assertSession()->pageTextContains('field_media field is required.');
    $this->assertSession()->elementExists('css', 'fieldset.error');
  }

}
