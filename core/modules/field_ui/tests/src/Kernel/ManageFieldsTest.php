<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Kernel;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\field_ui\Traits\FieldUiAssertTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Field UI "Manage fields" screen.
 */
#[Group('field_ui')]
#[RunTestsInSeparateProcesses]
class ManageFieldsTest extends KernelTestBase {

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use EntityReferenceFieldCreationTrait;
  use FieldUiAssertTrait;
  use UserCreationTrait {
    createUser as drupalCreateUser;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'field_test',
    'field_ui',
    'filter',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * The ID of the custom content type created for testing.
   *
   * @var string
   */
  protected string $contentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['field_ui', 'user']);

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'administer node fields',
    ]);
    $this->setCurrentUser($admin_user);

    // Create content type, with underscores.
    $type_name = $this->randomMachineName(8) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->contentType = $type->id();

    // Create Basic page and Article node types.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a vocabulary named "Tags".
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();

    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_' . $vocabulary->id(), 'Tags', 'taxonomy_term', 'default', $handler_settings);

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', 'article')
      ->setComponent('field_' . $vocabulary->id())
      ->save();
  }

  /**
   * Tests that Field UI respects locked fields.
   */
  public function testLockedField(): void {
    // Create a locked field and attach it to a bundle. We need to do this
    // programmatically as there's no way to create a locked field through UI.
    $field_name = $this->randomMachineName(8);
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'test_field',
      'cardinality' => 1,
      'locked' => TRUE,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->contentType,
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name, [
        'type' => 'test_field_widget',
      ])
      ->save();

    // Check that the links for edit and delete are not present.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields');
    $locked = $this->getNodeElementsByXpath('//tr[@id=:field_name]/td[3]', [':field_name' => $field_name]);
    $this->assertSame('Locked', $locked[0]->getHtml(), 'Field is marked as Locked in the UI');
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/node.' . $this->contentType . '.' . $field_name . '/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that Field UI respects the 'no_ui' flag in the field type definition.
   */
  public function testHiddenFields(): void {
    // Check that the field type is not available in the 'add new field' row.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields/add-field');
    $this->assertSession()->elementNotExists('xpath', "//a//span[text()='Hidden from UI test field']");
    $this->assertSession()->elementExists('xpath', "//a//span[text()='Shape']");

    // Create a field storage and a field programmatically.
    $field_name = 'hidden_test_field';
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_name,
    ])->save();
    $field = [
      'field_name' => $field_name,
      'bundle' => $this->contentType,
      'entity_type' => 'node',
      'label' => 'Hidden field',
    ];
    FieldConfig::create($field)->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->contentType)
      ->setComponent($field_name)
      ->save();
    $this->assertInstanceOf(FieldConfig::class, FieldConfig::load('node.' . $this->contentType . '.' . $field_name));

    // Check that the newly added field appears on the 'Manage Fields'
    // screen.
    $this->drupalGet('admin/structure/types/manage/' . $this->contentType . '/fields');
    $this->assertSession()->elementTextContains('xpath', '//table[@id="field-overview"]//tr[@id="hidden-test-field"]//td[1]', $field['label']);

    // Check that the field does not appear in the 're-use existing field' row
    // on other bundles.
    $this->drupalGet('admin/structure/types/manage/page/fields/reuse');
    $this->assertSession()->elementNotExists('css', ".js-reuse-table [data-field-id='{$field_name}']");
    $this->assertSession()->elementExists('css', '.js-reuse-table [data-field-id="field_tags"]');

    // Check that non-configurable fields are not available.
    $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    foreach ($field_types as $field_type => $definition) {
      $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
      $label = (string) $definition['label'];
      if (empty($definition['no_ui'])) {
        try {
          $this->assertSession()->elementExists('xpath', "//a//span[text()='$label']");
        }
        catch (ElementNotFoundException) {
          if ($group = $this->getFieldFromGroup($field_type)) {
            if ($group !== 'General') {
              $link = $this->assertSession()->elementExists('xpath', "//a[.//span[text()='$group']]");
              $link->click();
              $this->assertSession()
                ->elementExists('css', "[name='field_options_wrapper'][value='$field_type']");
            }
          }
        }
      }
      else {
        $this->assertSession()->elementNotExists('xpath', "//a//span[text()='$label']");
      }
    }
  }

}
