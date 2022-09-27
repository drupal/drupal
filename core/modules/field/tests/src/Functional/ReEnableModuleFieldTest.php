<?php

namespace Drupal\Tests\field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the behavior of a field module after being disabled and re-enabled.
 *
 * @group field
 */
class ReEnableModuleFieldTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'node',
    // We use telephone module instead of test_field because test_field is
    // hidden and does not display on the admin/modules page.
    'telephone',
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

    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalLogin($this->drupalCreateUser([
      'create article content',
      'edit own article content',
    ]));
  }

  /**
   * Tests the behavior of a field module after being disabled and re-enabled.
   *
   * @see field_system_info_alter()
   */
  public function testReEnabledField() {
    // Add a telephone field to the article content type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_telephone',
      'entity_type' => 'node',
      'type' => 'telephone',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Telephone Number',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('field_telephone', [
        'type' => 'telephone_default',
        'settings' => [
          'placeholder' => '123-456-7890',
        ],
      ])
      ->save();

    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_telephone', [
        'type' => 'telephone_link',
        'weight' => 1,
      ])
      ->save();

    // Display the article node form and verify the telephone widget is present.
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldValueEquals("field_telephone[0][value]", '');

    // Submit an article node with a telephone field so data exist for the
    // field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_telephone[0][value]' => "123456789",
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('<a href="tel:123456789">');

    // Test that the module can't be uninstalled from the UI while there is data
    // for its fields.
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains("The Telephone number field type is used in the following field: node.field_telephone");

    // Add another telephone field to a different entity type in order to test
    // the message for the case when multiple fields are blocking the
    // uninstallation of a module.
    $field_storage2 = FieldStorageConfig::create([
      'field_name' => 'field_telephone_2',
      'entity_type' => 'user',
      'type' => 'telephone',
    ]);
    $field_storage2->save();
    FieldConfig::create([
      'field_storage' => $field_storage2,
      'bundle' => 'user',
      'label' => 'User Telephone Number',
    ])->save();

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains("The Telephone number field type is used in the following fields: node.field_telephone, user.field_telephone_2");

    // Delete both fields.
    $field_storage->delete();
    $field_storage2->delete();

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains('Uninstall');
    $this->assertSession()->pageTextContains('Fields pending deletion');
    $this->cronRun();
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains('Uninstall');
    $this->assertSession()->pageTextNotContains("The Telephone number field type is used in the following field: node.field_telephone");
    $this->assertSession()->pageTextNotContains('Fields pending deletion');
  }

}
