<?php

/**
 * @file
 * Definition of Drupal\node\Tests\PagePreviewTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_reference\Tests\EntityReferenceTestTrait;
use Drupal\node\Entity\NodeType;

/**
 * Tests the node entity preview functionality.
 *
 * @group node
 */
class PagePreviewTest extends NodeTestBase {

  use EntityReferenceTestTrait;

  /**
   * Enable the node and taxonomy modules to test both on the preview.
   *
   * @var array
   */
  public static $modules = array('node', 'taxonomy');

  /**
   * The name of the created field.
   *
   * @var string
   */
  protected $fieldName;

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('edit own page content', 'create page content'));
    $this->drupalLogin($web_user);

    // Add a vocabulary so we can test different view modes.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->randomMachineName(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'help' => '',
    ));
    $vocabulary->save();

    $this->vocabulary = $vocabulary;

    // Add a term to the vocabulary.
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));
    $term->save();

    $this->term = $term;

    // Create a field.
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    $handler_settings = array(
      'target_bundles' => array(
        $this->vocabulary->id() => $this->vocabulary->id(),
      ),
      'auto_create' => TRUE,
    );
    $this->createEntityReferenceField('node', 'page', $this->fieldName, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'entity_reference_autocomplete_tags',
      ))
      ->save();

    // Show on default display and teaser.
    entity_get_display('node', 'page', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'entity_reference_label',
      ))
      ->save();
    entity_get_display('node', 'page', 'teaser')
      ->setComponent($this->fieldName, array(
        'type' => 'entity_reference_label',
      ))
      ->save();
  }

  /**
   * Checks the node preview functionality.
   */
  function testPagePreview() {
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $term_key = $this->fieldName . '[target_id]';

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$term_key] = $this->term->getName();
    $this->drupalPostForm('node/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title, body and term.
    $this->assertTitle(t('@title | Drupal', array('@title' => $edit[$title_key])), 'Basic page title is preview.');
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$body_key], 'Body displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');
    $this->assertLink(t('Back to content editing'));

    // Get the UUID.
    $url = parse_url($this->getUrl());
    $paths = explode('/', $url['path']);
    $view_mode = array_pop($paths);
    $uuid = array_pop($paths);

    // Switch view mode. We'll remove the body from the teaser view mode.
    entity_get_display('node', 'page', 'teaser')
      ->removeComponent('body')
      ->save();

    $view_mode_edit = array('view_mode' => 'teaser');
    $this->drupalPostForm('node/preview/' . $uuid . '/default', $view_mode_edit, t('Switch'));
    $this->assertRaw('view-mode-teaser', 'View mode teaser class found.');
    $this->assertNoText($edit[$body_key], 'Body not displayed.');

    // Check that the title, body and term fields are displayed with the
    // values after going back to the content edit page.
    $this->clickLink(t('Back to content editing'));
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key] . ' (' . $this->term->id() . ')', 'Term field displayed.');

    // Assert the content is kept when reloading the page.
    $this->drupalGet('node/add/page', array('query' => array('uuid' => $uuid)));
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key] . ' (' . $this->term->id() . ')', 'Term field displayed.');

    // Save the node.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);

    // Check the term was displayed on the saved node.
    $this->drupalGet('node/' . $node->id());
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check the term appears again on the edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName($term_key, $edit[$term_key] . ' (' . $this->term->id() . ')', 'Term field displayed.');

    // Check with two new terms on the edit form, additionally to the existing
    // one.
    $edit = array();
    $newterm1 = $this->randomMachineName(8);
    $newterm2 = $this->randomMachineName(8);
    $edit[$term_key] = $this->term->getName() . ', ' . $newterm1 . ', ' . $newterm2;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Preview'));
    $this->assertRaw('>' . $newterm1 . '<', 'First new term displayed.');
    $this->assertRaw('>' . $newterm2 . '<', 'Second new term displayed.');
    // The first term should be displayed as link, the others not.
    $this->assertLink($this->term->getName());
    $this->assertNoLink($newterm1);
    $this->assertNoLink($newterm2);

    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save'));

    // Check with one more new term, keeping old terms, removing the existing
    // one.
    $edit = array();
    $newterm3 = $this->randomMachineName(8);
    $edit[$term_key] = $newterm1 . ', ' . $newterm3 . ', ' . $newterm2;
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Preview'));
    $this->assertRaw('>' . $newterm1 . '<', 'First existing term displayed.');
    $this->assertRaw('>' . $newterm2 . '<', 'Second existing term displayed.');
    $this->assertRaw('>' . $newterm3 . '<', 'Third new term displayed.');
    $this->assertNoText($this->term->getName());
    $this->assertLink($newterm1);
    $this->assertLink($newterm2);
    $this->assertNoLink($newterm3);

    // Check that editing an existing node after it has been previewed and not
    // saved doesn't remember the previous changes.
    $edit = array(
      $title_key => $this->randomMachineName(8),
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Preview'));
    $this->assertText($edit[$title_key], 'New title displayed.');
    $this->clickLink(t('Back to content editing'));
    $this->assertFieldByName($title_key, $edit[$title_key], 'New title value displayed.');
    // Navigate away from the node without saving.
    $this->drupalGet('<front>');
    // Go back to the edit form, the title should have its initial value.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName($title_key, $node->label(), 'Correct title value displayed.');

    // Check with required preview.
    $node_type = NodeType::load('page');
    $node_type->setPreviewMode(DRUPAL_REQUIRED);
    $node_type->save();
    $this->drupalGet('node/add/page');
    $this->assertNoRaw('edit-submit');
    $this->drupalPostForm('node/add/page', array($title_key => 'Preview'), t('Preview'));
    $this->clickLink(t('Back to content editing'));
    $this->assertRaw('edit-submit');
  }

  /**
   * Checks the node preview functionality, when using revisions.
   */
  function testPagePreviewWithRevisions() {
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $term_key = $this->fieldName . '[target_id]';
    // Force revision on "Basic page" content.
    $node_type = NodeType::load('page');
    $node_type->setNewRevision(TRUE);
    $node_type->save();

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$term_key] = $this->term->id();
    $edit['revision_log[0][value]'] = $this->randomString(32);
    $this->drupalPostForm('node/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title, body and term.
    $this->assertTitle(t('@title | Drupal', array('@title' => $edit[$title_key])), 'Basic page title is preview.');
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$body_key], 'Body displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check that the title and body fields are displayed with the correct
    // values after going back to the content edit page.
    $this->clickLink(t('Back to content editing'));    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');

    // Check that the revision log field has the correct value.
    $this->assertFieldByName('revision_log[0][value]', $edit['revision_log[0][value]'], 'Revision log field displayed.');
  }

  /**
   * Checks the node preview accessible for simultaneous node editing.
   */
  public function testSimultaneousPreview() {
    $title_key = 'title[0][value]';
    $node = $this->drupalCreateNode(array());

    $edit = array($title_key => 'New page title');
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Preview'));
    $this->assertText($edit[$title_key]);

    $user2 = $this->drupalCreateUser(array('edit any page content'));
    $this->drupalLogin($user2);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName($title_key, $node->label(), 'No title leaked from previous user.');

    $edit2 = array($title_key => 'Another page title');
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit2, t('Preview'));
    $this->assertUrl(\Drupal::url('entity.node.preview', ['node_preview' => $node->uuid(), 'view_mode_id' => 'default'], ['absolute' => TRUE]));
    $this->assertText($edit2[$title_key]);
  }

}
