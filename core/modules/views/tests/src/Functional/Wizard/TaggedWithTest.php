<?php

namespace Drupal\Tests\views\Functional\Wizard;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests the ability of the views wizard to create views filtered by taxonomy.
 *
 * @group views
 */
class TaggedWithTest extends WizardTestBase {

  use EntityReferenceTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy'];

  /**
   * Node type with an autocomplete tagging field.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeTypeWithTags;

  /**
   * Node type without an autocomplete tagging field.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeTypeWithoutTags;

  /**
   * The vocabulary used for the test tag field.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $tagVocabulary;

  /**
   * Holds the field storage for test tag field.
   *
   * @var \Drupal\field\FieldStorageConfigInterface
   */
  protected $tagFieldStorage;

  /**
   * Name of the test tag field.
   *
   * @var string
   */
  protected $tagFieldName;

  /**
   * Field definition for the test tag field.
   *
   * @var array
   */
  protected $tagField;

  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Create two content types. One will have an autocomplete tagging field,
    // and one won't.
    $this->nodeTypeWithTags = $this->drupalCreateContentType();
    $this->nodeTypeWithoutTags = $this->drupalCreateContentType();

    // Create the vocabulary for the tag field.
    $this->tagVocabulary = Vocabulary::create([
      'name' => 'Views testing tags',
      'vid' => 'views_testing_tags',
    ]);
    $this->tagVocabulary->save();

    // Create the tag field itself.
    $this->tagFieldName = 'field_views_testing_tags';

    $handler_settings = [
      'target_bundles' => [
        $this->tagVocabulary->id() => $this->tagVocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', $this->nodeTypeWithTags->id(), $this->tagFieldName, NULL, 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', $this->nodeTypeWithTags->id())
      ->setComponent($this->tagFieldName, [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();

    $display_repository->getViewDisplay('node', $this->nodeTypeWithTags->id())
      ->setComponent($this->tagFieldName, [
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->save();
    $display_repository->getViewDisplay('node', $this->nodeTypeWithTags->id(), 'teaser')
      ->setComponent('field_views_testing_tags', [
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->save();
  }

  /**
   * Tests the "tagged with" functionality.
   */
  public function testTaggedWith() {
    // In this test we will only create nodes that have an instance of the tag
    // field.
    $node_add_path = 'node/add/' . $this->nodeTypeWithTags->id();

    // Create three nodes, with different tags.
    $edit = [];
    $edit['title[0][value]'] = $node_tag1_title = $this->randomMachineName();
    $edit[$this->tagFieldName . '[target_id]'] = 'tag1';
    $this->drupalPostForm($node_add_path, $edit, t('Save'));
    $edit = [];
    $edit['title[0][value]'] = $node_tag1_tag2_title = $this->randomMachineName();
    $edit[$this->tagFieldName . '[target_id]'] = 'tag1, tag2';
    $this->drupalPostForm($node_add_path, $edit, t('Save'));
    $edit = [];
    $edit['title[0][value]'] = $node_no_tags_title = $this->randomMachineName();
    $this->drupalPostForm($node_add_path, $edit, t('Save'));

    // Create a view that filters by taxonomy term "tag1". It should show only
    // the two nodes from above that are tagged with "tag1".
    $view1 = [];
    // First select the node type and update the form so the correct tag field
    // is used.
    $view1['show[type]'] = $this->nodeTypeWithTags->id();
    $this->drupalPostForm('admin/structure/views/add', $view1, t('Update "of type" choice'));
    // Now resubmit the entire form to the same URL.
    $view1['label'] = $this->randomMachineName(16);
    $view1['id'] = strtolower($this->randomMachineName(16));
    $view1['description'] = $this->randomMachineName(16);
    $view1['show[tagged_with]'] = 'tag1';
    $view1['page[create]'] = 1;
    $view1['page[title]'] = $this->randomMachineName(16);
    $view1['page[path]'] = $this->randomMachineName(16);
    $this->drupalPostForm(NULL, $view1, t('Save and edit'));
    // Visit the page and check that the nodes we expect are present and the
    // ones we don't expect are absent.
    $this->drupalGet($view1['page[path]']);
    $this->assertResponse(200);
    $this->assertText($node_tag1_title);
    $this->assertText($node_tag1_tag2_title);
    $this->assertNoText($node_no_tags_title);

    // Create a view that filters by taxonomy term "tag2". It should show only
    // the one node from above that is tagged with "tag2".
    $view2 = [];
    $view2['show[type]'] = $this->nodeTypeWithTags->id();
    $this->drupalPostForm('admin/structure/views/add', $view2, t('Update "of type" choice'));
    $this->assertResponse(200);
    $view2['label'] = $this->randomMachineName(16);
    $view2['id'] = strtolower($this->randomMachineName(16));
    $view2['description'] = $this->randomMachineName(16);
    $view2['show[tagged_with]'] = 'tag2';
    $view2['page[create]'] = 1;
    $view2['page[title]'] = $this->randomMachineName(16);
    $view2['page[path]'] = $this->randomMachineName(16);
    $this->drupalPostForm(NULL, $view2, t('Save and edit'));
    $this->assertResponse(200);
    $this->drupalGet($view2['page[path]']);
    $this->assertNoText($node_tag1_title);
    $this->assertText($node_tag1_tag2_title);
    $this->assertNoText($node_no_tags_title);
  }

  /**
   * Tests that the "tagged with" form element only shows for node types that support it.
   */
  public function testTaggedWithByNodeType() {
    // The tagging field is associated with one of our node types only. So the
    // "tagged with" form element on the view wizard should appear on the form
    // by default (when the wizard is configured to display all content) and
    // also when the node type that has the tagging field is selected, but not
    // when the node type that doesn't have the tagging field is selected.
    $tags_xpath = '//input[@name="show[tagged_with]"]';
    $this->drupalGet('admin/structure/views/add');
    $this->assertFieldByXpath($tags_xpath);
    $view['show[type]'] = $this->nodeTypeWithTags->id();
    $this->drupalPostForm('admin/structure/views/add', $view, t('Update "of type" choice'));
    $this->assertFieldByXpath($tags_xpath);
    $view['show[type]'] = $this->nodeTypeWithoutTags->id();
    $this->drupalPostForm(NULL, $view, t('Update "of type" choice (2)'));
    $this->assertNoFieldByXpath($tags_xpath);

    // If we add an instance of the tagging field to the second node type, the
    // "tagged with" form element should not appear for it too.
    FieldConfig::create([
      'field_name' => $this->tagFieldName,
      'entity_type' => 'node',
      'bundle' => $this->nodeTypeWithoutTags->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $this->tagVocabulary->id() => $this->tagVocabulary->id(),
          ],
          'auto_create' => TRUE,
        ],
      ],
    ])->save();
    \Drupal::service('entity_display.repository')
      ->getFormDisplay('node', $this->nodeTypeWithoutTags->id())
      ->setComponent($this->tagFieldName, [
        'type' => 'entity_reference_autocomplete_tags',
      ])
      ->save();

    $view['show[type]'] = $this->nodeTypeWithTags->id();
    $this->drupalPostForm('admin/structure/views/add', $view, t('Update "of type" choice'));
    $this->assertFieldByXpath($tags_xpath);
    $view['show[type]'] = $this->nodeTypeWithoutTags->id();
    $this->drupalPostForm(NULL, $view, t('Update "of type" choice (2)'));
    $this->assertFieldByXpath($tags_xpath);
  }

}
