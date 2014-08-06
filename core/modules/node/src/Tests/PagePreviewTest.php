<?php

/**
 * @file
 * Definition of Drupal\node\Tests\PagePreviewTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\LanguageInterface;

/**
 * Tests the node entity preview functionality.
 *
 * @group node
 */
class PagePreviewTest extends NodeTestBase {

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
  protected $field_name;

  function setUp() {
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

    // Set up a field and instance.
    $this->field_name = drupal_strtolower($this->randomMachineName());
    entity_create('field_storage_config', array(
      'name' => $this->field_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => '0',
          ),
        ),
      ),
      'cardinality' => '-1',
    ))->save();
    entity_create('field_instance_config', array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
    ))->save();

    entity_get_form_display('node', 'page', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_autocomplete',
      ))
      ->save();

    // Show on default display and teaser.
    entity_get_display('node', 'page', 'default')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
    entity_get_display('node', 'page', 'teaser')
      ->setComponent($this->field_name, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();
  }

  /**
   * Checks the node preview functionality.
   */
  function testPagePreview() {
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $term_key = $this->field_name;

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$term_key] = $this->term->getName();
    $this->drupalPostForm('node/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title, body and term.
    $this->assertTitle(t('Preview | Drupal'), 'Basic page title is preview.');
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$body_key], 'Body displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check that the title, body and term fields are displayed with the
    // correct values.
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');

    // Save the node.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);

    // Check the term was displayed on the saved node.
    $this->drupalGet('node/' . $node->id());
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check the term appears again on the edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');

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

    $this->drupalPostForm(NULL, $edit, t('Save'));

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
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }

  /**
   * Checks the node preview functionality, when using revisions.
   */
  function testPagePreviewWithRevisions() {
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $term_key = $this->field_name;
    // Force revision on "Basic page" content.
    $this->container->get('config.factory')->get('node.type.page')->set('settings.node.options', array('status', 'revision'))->save();

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$term_key] = $this->term->id();
    $edit['revision_log'] = $this->randomMachineName(32);
    $this->drupalPostForm('node/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title, body and term.
    $this->assertTitle(t('Preview | Drupal'), 'Basic page title is preview.');
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$body_key], 'Body displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check that the title, body and term fields are displayed with the correct values.
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');

    // Check that the revision log field has the correct value.
    $this->assertFieldByName('revision_log', $edit['revision_log'], 'Revision log field displayed.');
  }

}
