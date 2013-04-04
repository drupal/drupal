<?php

/**
 * @file
 * Definition of Drupal\node\Tests\PagePreviewTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests the node entity preview functionality.
 */
class PagePreviewTest extends NodeTestBase {

  /**
   * Enable the node and taxonomy modules to test both on the preview.
   *
   * @var array
   */
  public static $modules = array('node', 'taxonomy');

  public static function getInfo() {
    return array(
      'name' => 'Node preview',
      'description' => 'Test node preview functionality.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('edit own page content', 'create page content'));
    $this->drupalLogin($web_user);

    // Add a vocabulary so we can test different view modes.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      'vid' => $this->randomName(),
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'help' => '',
    ));
    $vocabulary->save();

    $this->vocabulary = $vocabulary;

    // Add a term to the vocabulary.
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      'vid' => $this->vocabulary->id(),
      'langcode' => LANGUAGE_NOT_SPECIFIED,
    ));
    $term->save();

    $this->term = $term;

    // Set up a field and instance.
    $this->field_name = drupal_strtolower($this->randomName());
    $this->field = array(
      'field_name' => $this->field_name,
      'type' => 'taxonomy_term_reference',
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $this->vocabulary->id(),
            'parent' => '0',
          ),
        ),
      )
    );

    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field_name,
      'entity_type' => 'node',
      'bundle' => 'page',
      'widget' => array(
        'type' => 'options_select',
      ),
      // Hide on full display but render on teaser.
      'display' => array(
        'default' => array(
          'type' => 'hidden',
        ),
        'teaser' => array(
          'type' => 'taxonomy_term_reference_link',
        ),
      ),
    );
    field_create_instance($this->instance);
  }

  /**
   * Checks the node preview functionality.
   */
  function testPagePreview() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $title_key = "title";
    $body_key = "body[$langcode][0][value]";
    $term_key = "{$this->field_name}[$langcode]";

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomName(8);
    $edit[$body_key] = $this->randomName(16);
    $edit[$term_key] = $this->term->id();
    $this->drupalPost('node/add/page', $edit, t('Preview'));

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
  }

  /**
   * Checks the node preview functionality, when using revisions.
   */
  function testPagePreviewWithRevisions() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $title_key = "title";
    $body_key = "body[$langcode][0][value]";
    $term_key = "{$this->field_name}[$langcode]";
    // Force revision on "Basic page" content.
    variable_set('node_options_page', array('status', 'revision'));

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomName(8);
    $edit[$body_key] = $this->randomName(16);
    $edit[$term_key] = $this->term->id();
    $edit['log'] = $this->randomName(32);
    $this->drupalPost('node/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title, body and term.
    $this->assertTitle(t('Preview | Drupal'), 'Basic page title is preview.');
    $this->assertText($edit[$title_key], 'Title displayed.');
    $this->assertText($edit[$body_key], 'Body displayed.');
    $this->assertText($edit[$term_key], 'Term displayed.');

    // Check that the title, body and term fields are displayed with the correct values.
    $this->assertFieldByName($title_key, $edit[$title_key], 'Title field displayed.');
    $this->assertFieldByName($body_key, $edit[$body_key], 'Body field displayed.');
    $this->assertFieldByName($term_key, $edit[$term_key], 'Term field displayed.');

    // Check that the log field has the correct value.
    $this->assertFieldByName('log', $edit['log'], 'Log field displayed.');
  }
}
