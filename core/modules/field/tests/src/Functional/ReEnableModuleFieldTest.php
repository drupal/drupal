<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the behavior of a field module after being disabled and re-enabled.
 */
#[Group('field')]
#[RunTestsInSeparateProcesses]
class ReEnableModuleFieldTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    // We use link module instead of test_field because test_field is
    // hidden and does not display on the admin/modules page.
    'link',
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
  public function testReEnabledField(): void {
    // Add a link field to the article content type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'type' => 'link',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Link',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('field_link', [
        'type' => 'link_default',
      ])
      ->save();

    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_link', [
        'type' => 'link',
        'weight' => 1,
      ])
      ->save();

    // Display the article node form and verify the link widget is present.
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldValueEquals("field_link[0][uri]", '');

    // Submit an article node with a link field so data exist for the
    // field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_link[0][uri]' => "https://www.example.com",
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('<a href="https://www.example.com">');

    // Test that the module can't be uninstalled from the UI while there is data
    // for its fields.
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains("The Link field type is used in the following field: node.field_link");

    // Add another link field to a different entity type in order to test
    // the message for the case when multiple fields are blocking the
    // uninstallation of a module.
    $field_storage2 = FieldStorageConfig::create([
      'field_name' => 'field_link_2',
      'entity_type' => 'user',
      'type' => 'link',
    ]);
    $field_storage2->save();
    FieldConfig::create([
      'field_storage' => $field_storage2,
      'bundle' => 'user',
      'label' => 'User Link',
    ])->save();

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains("The Link field type is used in the following fields: node.field_link, user.field_link_2");

    // Delete both fields.
    $field_storage->delete();
    $field_storage2->delete();

    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains('Uninstall');
    $this->assertSession()->pageTextContains('Fields pending deletion');
    $this->cronRun();
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains('Uninstall');
    $this->assertSession()->pageTextNotContains("The Link field type is used in the following field: node.field_link");
    $this->assertSession()->pageTextNotContains('Fields pending deletion');
  }

}
