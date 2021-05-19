<?php

namespace Drupal\Tests\text\Functional;

use Drupal\Component\Utility\Html;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\field\Functional\String\StringFieldTest;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the creation of text fields.
 *
 * @group text
 */
class TextFieldTest extends StringFieldTest {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * A user with relevant administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer filters']);
  }

  // Test fields.

  /**
   * Test text field validation.
   */
  public function testTextFieldValidation() {
    // Create a field with settings to validate.
    $max_length = 3;
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'text',
      'settings' => [
        'max_length' => $max_length,
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();

    // Test validation with valid and invalid values.
    $entity = EntityTest::create();
    for ($i = 0; $i <= $max_length + 2; $i++) {
      $entity->{$field_name}->value = str_repeat('x', $i);
      $violations = $entity->{$field_name}->validate();
      if ($i <= $max_length) {
        $this->assertCount(0, $violations, "Length $i does not cause validation error when max_length is $max_length");
      }
      else {
        $this->assertCount(1, $violations, "Length $i causes validation error when max_length is $max_length");
      }
    }
  }

  /**
   * Test required long text with file upload.
   */
  public function testRequiredLongTextWithFileUpload() {
    // Create a text field.
    $text_field_name = 'text_long';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $text_field_name,
      'entity_type' => 'entity_test',
      'type' => 'text_with_summary',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName() . '_label',
      'required' => TRUE,
    ])->save();

    // Create a file field.
    $file_field_name = 'file_field';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $file_field_name,
      'entity_type' => 'entity_test',
      'type' => 'file',
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
      ->setComponent($text_field_name, [
        'type' => 'text_textarea_with_summary',
      ])
      ->setComponent($file_field_name, [
        'type' => 'file_generic',
      ])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($text_field_name)
      ->setComponent($file_field_name)
      ->save();

    $test_file = current($this->drupalGetTestFiles('text'));
    $edit['files[file_field_0]'] = \Drupal::service('file_system')->realpath($test_file->uri);
    $this->drupalPostForm('entity_test/add', $edit, 'Upload');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'text_long[0][value]' => 'Long text',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('entity_test/1');
    $this->assertText('Long text');
  }

  /**
   * Test widgets.
   */
  public function testTextfieldWidgets() {
    $this->_testTextfieldWidgets('text', 'text_textfield');
    $this->_testTextfieldWidgets('text_long', 'text_textarea');
  }

  /**
   * Test widgets + 'formatted_text' setting.
   */
  public function testTextfieldWidgetsFormatted() {
    $this->_testTextfieldWidgetsFormatted('text', 'text_textfield');
    $this->_testTextfieldWidgetsFormatted('text_long', 'text_textarea');
  }

  /**
   * Helper function for testTextfieldWidgetsFormatted().
   */
  public function _testTextfieldWidgetsFormatted($field_type, $widget_type) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Create a field.
    $field_name = mb_strtolower($this->randomMachineName());
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
      ])
      ->save();
    $display_repository->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent($field_name)
      ->save();

    // Disable all text formats besides the plain text fallback format.
    $this->drupalLogin($this->adminUser);
    foreach (filter_formats() as $format) {
      if (!$format->isFallbackFormat()) {
        $this->drupalPostForm('admin/config/content/formats/manage/' . $format->id() . '/disable', [], 'Disable');
      }
    }
    $this->drupalLogin($this->webUser);

    // Display the creation form. Since the user only has access to one format,
    // no format selector will be displayed.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldValueEquals("{$field_name}[0][value]", '');
    $this->assertSession()->fieldNotExists("{$field_name}[0][format]");

    // Submit with data that should be filtered.
    $value = '<em>' . $this->randomMachineName() . '</em>';
    $edit = [
      "{$field_name}[0][value]" => $value,
    ];
    $this->submitForm($edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText('entity_test ' . $id . ' has been created.');

    // Display the entity.
    $entity = EntityTest::load($id);
    $display = $display_repository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $rendered_entity = \Drupal::service('renderer')->renderRoot($content);
    $this->assertStringNotContainsString($value, (string) $rendered_entity);
    $this->assertStringContainsString(Html::escape($value), (string) $rendered_entity);

    // Create a new text format that does not escape HTML, and grant the user
    // access to it.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'format' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('admin/config/content/formats/add', $edit, 'Save configuration');
    filter_formats_reset();
    $format = FilterFormat::load($edit['format']);
    $format_id = $format->id();
    $permission = $format->getPermissionName();
    $roles = $this->webUser->getRoles();
    $rid = $roles[0];
    user_role_grant_permissions($rid, [$permission]);
    $this->drupalLogin($this->webUser);

    // Display edition form.
    // We should now have a 'text format' selector.
    $this->drupalGet('entity_test/manage/' . $id . '/edit');
    $this->assertSession()->fieldExists("{$field_name}[0][value]");
    $this->assertSession()->fieldExists("{$field_name}[0][format]");

    // Edit and change the text format to the new one that was created.
    $edit = [
      "{$field_name}[0][format]" => $format_id,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertText('entity_test ' . $id . ' has been updated.');

    // Display the entity.
    $this->container->get('entity_type.manager')->getStorage('entity_test')->resetCache([$id]);
    $entity = EntityTest::load($id);
    $display = $display_repository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), 'full');
    $content = $display->build($entity);
    $rendered_entity = \Drupal::service('renderer')->renderRoot($content);
    $this->assertStringContainsString($value, (string) $rendered_entity);
  }

}
