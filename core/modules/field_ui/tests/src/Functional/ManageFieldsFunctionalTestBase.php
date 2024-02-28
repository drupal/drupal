<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the Field UI "Manage fields" screen.
 */
class ManageFieldsFunctionalTestBase extends BrowserTestBase {

  use FieldUiTestTrait;
  use EntityReferenceFieldCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field_ui',
    'field_test',
    'taxonomy',
    'image',
    'block',
    'node_access_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The ID of the custom content type created for testing.
   *
   * @var string
   */
  protected $contentType;

  /**
   * The label for a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldLabel;

  /**
   * The input name of a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldNameInput;

  /**
   * The name of a random field to be created for testing.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create a test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'bypass node access',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer taxonomy',
      'administer taxonomy_term fields',
      'administer taxonomy_term display',
      'administer users',
      'administer account settings',
      'administer user display',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = $this->randomMachineName(8) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->contentType = $type->id();

    // Create random field name with markup to test escaping.
    $this->fieldLabel = '<em>' . $this->randomMachineName(8) . '</em>';
    $this->fieldNameInput = $this->randomMachineName(8);
    $this->fieldName = 'field_' . $this->fieldNameInput;

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

    // Create a vocabulary named "Kittens".
    Vocabulary::create([
      'name' => 'Kittens',
      'vid' => 'kittens',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ])->save();

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

    // Setup node access testing.
    node_access_rebuild();
    node_access_test_add_field(NodeType::load('article'));
    \Drupal::state()->set('node_access_test.private', TRUE);

  }

}
