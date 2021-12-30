<?php

namespace Drupal\Tests\node\Functional;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests the node entity preview functionality.
 *
 * @group node
 */
class PagePreviewTest extends NodeTestBase {

  use EntityReferenceTestTrait;
  use CommentTestTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Enable the comment, node and taxonomy modules to test on the preview.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'comment',
    'image',
    'file',
    'text',
    'node_test',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * The name of the created field.
   *
   * @var string
   */
  protected $fieldName;

  protected function setUp(): void {
    parent::setUp();
    $this->addDefaultCommentField('node', 'page');

    $web_user = $this->drupalCreateUser([
      'edit own page content',
      'create page content',
      'administer menu',
    ]);
    $this->drupalLogin($web_user);

    // Add a vocabulary so we can test different view modes.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->randomMachineName(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
    ]);
    $vocabulary->save();

    $this->vocabulary = $vocabulary;

    // Add a term to the vocabulary.
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();

    $this->term = $term;

    // Create an image field.
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => [],
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => 'field_image',
      'label' => 'Images',
      'entity_type' => 'node',
      'bundle' => 'page',
      'required' => FALSE,
      'settings' => [],
    ]);
    $field_config->save();

    // Create a field.
    $this->fieldName = mb_strtolower($this->randomMachineName());
    $handler_settings = [
      'target_bundles' => [
        $this->vocabulary->id() => $this->vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'page', $this->fieldName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', 'page')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();

    // Show on default display and teaser.
    $display_repository->getViewDisplay('node', 'page')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'page', 'teaser')
      ->setComponent($this->fieldName, [
        'type' => 'entity_reference_label',
      ])
      ->save();

    $display_repository->getFormDisplay('node', 'page')
      ->setComponent('field_image', [
        'type' => 'image_image',
        'settings' => [],
      ])
      ->save();

    $display_repository->getViewDisplay('node', 'page')
      ->setComponent('field_image')
      ->save();

    // Create a multi-value text field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test_multi',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'type' => 'text',
      'settings' => [
        'max_length' => 50,
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ])->save();

    $display_repository->getFormDisplay('node', 'page')
      ->setComponent('field_test_multi', [
        'type' => 'text_textfield',
      ])
      ->save();

    $display_repository->getViewDisplay('node', 'page')
      ->setComponent('field_test_multi', [
        'type' => 'string',
      ])
      ->save();
  }

  /**
   * Checks the node preview functionality.
   */
  public function testPagePreview() {
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $term_key = $this->fieldName . '[target_id]';

    // Fill in node creation form and preview node.
    $edit = [];
    $edit[$title_key] = '<em>' . $this->randomMachineName(8) . '</em>';
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$term_key] = $this->term->getName();

    // Upload an image.
    $test_image = current($this->drupalGetTestFiles('image', 39325));
    $edit['files[field_image_0][]'] = \Drupal::service('file_system')->realpath($test_image->uri);
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Upload');

    // Add an alt tag and preview the node.
    $this->submitForm(['field_image[0][alt]' => 'Picture of llamas'], 'Preview');

    // Check that the preview is displaying the title, body and term.
    $expected_title = $edit[$title_key] . ' | Drupal';
    $this->assertSession()->titleEquals($expected_title);
    $this->assertSession()->assertEscaped($edit[$title_key]);
    $this->assertSession()->pageTextContains($edit[$body_key]);
    $this->assertSession()->pageTextContains($edit[$term_key]);
    $this->assertSession()->linkExists('Back to content editing');

    // Check that we see the class of the node type on the body element.
    $body_class_element = $this->xpath("//body[contains(@class, 'page-node-type-page')]");
    $this->assertNotEmpty($body_class_element, 'Node type body class found.');

    // Get the UUID.
    $url = parse_url($this->getUrl());
    $paths = explode('/', $url['path']);
    $view_mode = array_pop($paths);
    $uuid = array_pop($paths);

    // Switch view mode. We'll remove the body from the teaser view mode.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'page', 'teaser')
      ->removeComponent('body')
      ->save();

    $view_mode_edit = ['view_mode' => 'teaser'];
    $this->drupalGet('node/preview/' . $uuid . '/full');
    $this->submitForm($view_mode_edit, 'Switch');
    $this->assertSession()->responseContains('view-mode-teaser');
    $this->assertSession()->pageTextNotContains($edit[$body_key]);

    // Check that the title, body and term fields are displayed with the
    // values after going back to the content edit page.
    $this->clickLink('Back to content editing');
    $this->assertSession()->fieldValueEquals($title_key, $edit[$title_key]);
    $this->assertSession()->fieldValueEquals($body_key, $edit[$body_key]);
    $this->assertSession()->fieldValueEquals($term_key, $edit[$term_key]);
    $this->assertSession()->fieldValueEquals('field_image[0][alt]', 'Picture of llamas');
    $this->getSession()->getPage()->pressButton('Add another item');
    $this->assertSession()->fieldExists('field_test_multi[0][value]');
    $this->assertSession()->fieldExists('field_test_multi[1][value]');

    // Return to page preview to check everything is as expected.
    $this->submitForm([], 'Preview');
    $this->assertSession()->titleEquals($expected_title);
    $this->assertSession()->assertEscaped($edit[$title_key]);
    $this->assertSession()->pageTextContains($edit[$body_key]);
    $this->assertSession()->pageTextContains($edit[$term_key]);
    $this->assertSession()->linkExists('Back to content editing');

    // Assert the content is kept when reloading the page.
    $this->drupalGet('node/add/page', ['query' => ['uuid' => $uuid]]);
    $this->assertSession()->fieldValueEquals($title_key, $edit[$title_key]);
    $this->assertSession()->fieldValueEquals($body_key, $edit[$body_key]);
    $this->assertSession()->fieldValueEquals($term_key, $edit[$term_key]);

    // Save the node - this is a new POST, so we need to upload the image.
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Upload');
    $this->submitForm(['field_image[0][alt]' => 'Picture of llamas'], 'Save');
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);

    // Check the term was displayed on the saved node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($edit[$term_key]);

    // Check the term appears again on the edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals($term_key, $edit[$term_key] . ' (' . $this->term->id() . ')');

    // Check with two new terms on the edit form, additionally to the existing
    // one.
    $edit = [];
    $newterm1 = $this->randomMachineName(8);
    $newterm2 = $this->randomMachineName(8);
    $edit[$term_key] = $this->term->getName() . ', ' . $newterm1 . ', ' . $newterm2;
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->responseContains('>' . $newterm1 . '<');
    $this->assertSession()->responseContains('>' . $newterm2 . '<');
    // The first term should be displayed as link, the others not.
    $this->assertSession()->linkExists($this->term->getName());
    $this->assertSession()->linkNotExists($newterm1);
    $this->assertSession()->linkNotExists($newterm2);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Check with one more new term, keeping old terms, removing the existing
    // one.
    $edit = [];
    $newterm3 = $this->randomMachineName(8);
    $edit[$term_key] = $newterm1 . ', ' . $newterm3 . ', ' . $newterm2;
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->responseContains('>' . $newterm1 . '<');
    $this->assertSession()->responseContains('>' . $newterm2 . '<');
    $this->assertSession()->responseContains('>' . $newterm3 . '<');
    $this->assertSession()->pageTextNotContains($this->term->getName());
    $this->assertSession()->linkExists($newterm1);
    $this->assertSession()->linkExists($newterm2);
    $this->assertSession()->linkNotExists($newterm3);

    // Check that editing an existing node after it has been previewed and not
    // saved doesn't remember the previous changes.
    $edit = [
      $title_key => $this->randomMachineName(8),
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->pageTextContains($edit[$title_key]);
    $this->clickLink('Back to content editing');
    $this->assertSession()->fieldValueEquals($title_key, $edit[$title_key]);
    // Navigate away from the node without saving.
    $this->drupalGet('<front>');
    // Go back to the edit form, the title should have its initial value.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals($title_key, $node->label());

    // Check with required preview.
    $node_type = NodeType::load('page');
    $node_type->setPreviewMode(DRUPAL_REQUIRED);
    $node_type->save();
    $this->drupalGet('node/add/page');
    $this->assertSession()->responseNotContains('edit-submit');
    $this->drupalGet('node/add/page');
    $this->submitForm([$title_key => 'Preview'], 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->responseContains('edit-submit');

    // Check that destination is remembered when clicking on preview. When going
    // back to the edit form and clicking save, we should go back to the
    // original destination, if set.
    $destination = 'node';
    $this->drupalGet($node->toUrl('edit-form'), ['query' => ['destination' => $destination]]);
    $this->submitForm([], 'Preview');
    $parameters = ['node_preview' => $node->uuid(), 'view_mode_id' => 'full'];
    $options = ['absolute' => TRUE, 'query' => ['destination' => $destination]];
    $this->assertSession()->addressEquals(Url::fromRoute('entity.node.preview', $parameters, $options));
    $this->submitForm(['view_mode' => 'teaser'], 'Switch');
    $this->clickLink('Back to content editing');
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals($destination);

    // Check that preview page works as expected without a destination set.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->submitForm([], 'Preview');
    $parameters = ['node_preview' => $node->uuid(), 'view_mode_id' => 'full'];
    $this->assertSession()->addressEquals(Url::fromRoute('entity.node.preview', $parameters));
    $this->submitForm(['view_mode' => 'teaser'], 'Switch');
    $this->clickLink('Back to content editing');
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    // Assert multiple items can be added and are not lost when previewing.
    $test_image_1 = current($this->drupalGetTestFiles('image', 39325));
    $edit_image_1['files[field_image_0][]'] = $file_system->realpath($test_image_1->uri);
    $test_image_2 = current($this->drupalGetTestFiles('image', 39325));
    $edit_image_2['files[field_image_1][]'] = $file_system->realpath($test_image_2->uri);
    $edit['field_image[0][alt]'] = 'Alt 1';

    $this->drupalGet('node/add/page');
    $this->submitForm($edit_image_1, 'Upload');
    $this->submitForm($edit, 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->fieldExists('files[field_image_1][]');
    $this->submitForm($edit_image_2, 'Upload');
    $this->assertSession()->fieldNotExists('files[field_image_1][]');

    $title = 'node_test_title';
    $example_text_1 = 'example_text_preview_1';
    $example_text_2 = 'example_text_preview_2';
    $example_text_3 = 'example_text_preview_3';
    $this->drupalGet('node/add/page');
    $edit = [
      'title[0][value]' => $title,
      'field_test_multi[0][value]' => $example_text_1,
    ];
    $this->assertSession()->pageTextContains('Storage is not set');
    $this->submitForm($edit, 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->pageTextContains('Storage is set');
    $this->assertSession()->fieldExists('field_test_multi[0][value]');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Basic page ' . $title . ' has been created.');
    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->pressButton('Add another item');
    $this->getSession()->getPage()->pressButton('Add another item');
    $edit = [
      'field_test_multi[1][value]' => $example_text_2,
      'field_test_multi[2][value]' => $example_text_3,
    ];
    $this->submitForm($edit, 'Preview');
    $this->clickLink('Back to content editing');
    $this->submitForm($edit, 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->fieldValueEquals('field_test_multi[0][value]', $example_text_1);
    $this->assertSession()->fieldValueEquals('field_test_multi[1][value]', $example_text_2);
    $this->assertSession()->fieldValueEquals('field_test_multi[2][value]', $example_text_3);

    // Now save the node and make sure all values got saved.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($example_text_1);
    $this->assertSession()->pageTextContains($example_text_2);
    $this->assertSession()->pageTextContains($example_text_3);

    // Edit again, change the menu_ui settings and click on preview.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'menu[enabled]' => TRUE,
      'menu[title]' => 'Changed title',
    ];
    $this->submitForm($edit, 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->checkboxChecked('edit-menu-enabled');
    $this->assertSession()->fieldValueEquals('menu[title]', 'Changed title');

    // Save, change the title while saving and make sure that it is correctly
    // saved.
    $edit = [
      'menu[enabled]' => TRUE,
      'menu[title]' => 'Second title change',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('menu[title]', 'Second title change');

  }

  /**
   * Checks the node preview functionality, when using revisions.
   */
  public function testPagePreviewWithRevisions() {
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $term_key = $this->fieldName . '[target_id]';
    // Force revision on "Basic page" content.
    $node_type = NodeType::load('page');
    $node_type->setNewRevision(TRUE);
    $node_type->save();

    // Fill in node creation form and preview node.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$term_key] = $this->term->id();
    $edit['revision_log[0][value]'] = $this->randomString(32);
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Preview');

    // Check that the preview is displaying the title, body and term.
    $this->assertSession()->titleEquals($edit[$title_key] . ' | Drupal');
    $this->assertSession()->pageTextContains($edit[$title_key]);
    $this->assertSession()->pageTextContains($edit[$body_key]);
    $this->assertSession()->pageTextContains($edit[$term_key]);

    // Check that the title and body fields are displayed with the correct
    // values after going back to the content edit page.
    $this->clickLink('Back to content editing');
    $this->assertSession()->fieldValueEquals($title_key, $edit[$title_key]);
    $this->assertSession()->fieldValueEquals($body_key, $edit[$body_key]);
    $this->assertSession()->fieldValueEquals($term_key, $edit[$term_key]);

    // Check that the revision log field has the correct value.
    $this->assertSession()->fieldValueEquals('revision_log[0][value]', $edit['revision_log[0][value]']);

    // Save the node after coming back from the preview page so we can create a
    // pending revision for it.
    $this->submitForm([], 'Save');
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);

    // Check that previewing a pending revision of a node works. This can not be
    // accomplished through the UI so we have to use API calls.
    // @todo Change this test to use the UI when we will be able to create
    // pending revisions in core.
    // @see https://www.drupal.org/node/2725533
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(FALSE);

    /** @var \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver */
    $controller_resolver = \Drupal::service('controller_resolver');
    $node_preview_controller = $controller_resolver->getControllerFromDefinition('\Drupal\node\Controller\NodePreviewController::view');
    $node_preview_controller($node, 'full');
  }

  /**
   * Checks the node preview accessible for simultaneous node editing.
   */
  public function testSimultaneousPreview() {
    $title_key = 'title[0][value]';
    $node = $this->drupalCreateNode([]);

    $edit = [$title_key => 'New page title'];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->pageTextContains($edit[$title_key]);

    $user2 = $this->drupalCreateUser(['edit any page content']);
    $this->drupalLogin($user2);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals($title_key, $node->label());

    $edit2 = [$title_key => 'Another page title'];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit2, 'Preview');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.node.preview', ['node_preview' => $node->uuid(), 'view_mode_id' => 'full']));
    $this->assertSession()->pageTextContains($edit2[$title_key]);
  }

  /**
   * Tests node preview with dynamic_page_cache and anonymous users.
   */
  public function testPagePreviewCache() {
    \Drupal::service('module_installer')->uninstall(['node_test']);
    $this->drupalLogout();
    $title_key = 'title[0][value]';
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['create page content', 'access content']);
    $edit = [
      $title_key => $this->randomMachineName(8),
    ];
    $this->drupalGet('/node/add/page');
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->pageTextContains($edit[$title_key]);
    $this->clickLink('Back to content editing');

    $edit = [
      $title_key => $this->randomMachineName(8),
    ];
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->pageTextContains($edit[$title_key]);
  }

}
