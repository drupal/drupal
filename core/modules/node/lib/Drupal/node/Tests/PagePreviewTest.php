<?php

/**
 * @file
 * Definition of Drupal\node\Tests\PagePreviewTest.
 */

namespace Drupal\node\Tests;

class PagePreviewTest extends NodeTestBase {
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
  }

  /**
   * Check the node preview functionality.
   */
  function testPagePreview() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $title_key = "title";
    $body_key = "body[$langcode][0][value]";

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomName(8);
    $edit[$body_key] = $this->randomName(16);
    $this->drupalPost('node/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title and body.
    $this->assertTitle(t('Preview | Drupal'), t('Basic page title is preview.'));
    $this->assertText($edit[$title_key], t('Title displayed.'));
    $this->assertText($edit[$body_key], t('Body displayed.'));

    // Check that the title and body fields are displayed with the correct values.
    $this->assertFieldByName($title_key, $edit[$title_key], t('Title field displayed.'));
    $this->assertFieldByName($body_key, $edit[$body_key], t('Body field displayed.'));
  }

  /**
   * Check the node preview functionality, when using revisions.
   */
  function testPagePreviewWithRevisions() {
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $title_key = "title";
    $body_key = "body[$langcode][0][value]";
    // Force revision on "Basic page" content.
    variable_set('node_options_page', array('status', 'revision'));

    // Fill in node creation form and preview node.
    $edit = array();
    $edit[$title_key] = $this->randomName(8);
    $edit[$body_key] = $this->randomName(16);
    $edit['log'] = $this->randomName(32);
    $this->drupalPost('node/add/page', $edit, t('Preview'));

    // Check that the preview is displaying the title and body.
    $this->assertTitle(t('Preview | Drupal'), t('Basic page title is preview.'));
    $this->assertText($edit[$title_key], t('Title displayed.'));
    $this->assertText($edit[$body_key], t('Body displayed.'));

    // Check that the title and body fields are displayed with the correct values.
    $this->assertFieldByName($title_key, $edit[$title_key], t('Title field displayed.'));
    $this->assertFieldByName($body_key, $edit[$body_key], t('Body field displayed.'));

    // Check that the log field has the correct value.
    $this->assertFieldByName('log', $edit['log'], t('Log field displayed.'));
  }
}
