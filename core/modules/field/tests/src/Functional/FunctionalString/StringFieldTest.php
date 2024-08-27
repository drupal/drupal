<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Functional\FunctionalString;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the creation of string fields.
 *
 * @group text
 */
class StringFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user without any special permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'access content',
    ]);
    $this->drupalLogin($this->webUser);
  }

  // Test fields.

  /**
   * Tests widgets.
   */
  public function testTextfieldWidgets(): void {
    $this->_testTextfieldWidgets('string', 'string_textfield');
    $this->_testTextfieldWidgets('string_long', 'string_textarea');
  }

  /**
   * Helper function for testTextfieldWidgets().
   */
  public function _testTextfieldWidgets($field_type, $widget_type) {
    // Create a field.
    $field_name = $this->randomMachineName();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => $field_type,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, [
        'type' => $widget_type,
        'settings' => [
          'placeholder' => 'A placeholder on ' . $widget_type,
        ],
      ])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($field_name)
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value]", '');
    $this->assertSession()->fieldNotExists("{$field_name}[0][format]");
    $this->assertSession()->responseContains(new FormattableMarkup('placeholder="A placeholder on @widget_type"', ['@widget_type' => $widget_type]));

    // Submit with some value.
    $value = $this->randomMachineName();
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertSession()->pageTextContains('entity_test ' . $id . ' has been created.');

    // Display the entity.
    $entity = EntityTest::load($id);
    $display = $display_repository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $rendered_entity = \Drupal::service('renderer')->renderRoot($content);
    $this->assertStringContainsString($value, (string) $rendered_entity);
  }

}
