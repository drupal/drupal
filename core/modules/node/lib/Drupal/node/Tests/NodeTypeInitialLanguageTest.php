<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTypeInitalLanguageTest.
 */

namespace Drupal\node\Tests;

/**
 * Tests related to node type initial language.
 */
class NodeTypeInitialLanguageTest extends NodeTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Node type initial language',
      'description' => 'Tests node type initial language settings.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp(array('language'));
    $web_user = $this->drupalCreateUser(array('bypass node access', 'administer content types', 'administer languages'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the node type initial language defaults, and modify them.
   *
   * The default initial language must be the site's default, and the language
   * locked option must be on.
   */
  function testNodeTypeInitialLanguageDefaults() {
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertOptionSelected('edit-node-type-language-default', 'site_default', 'The default inital language is the site default.');
    $this->assertFieldChecked('edit-node-type-language-hidden', 'Language selector is hidden by default.');

    $this->drupalGet('node/add/article');
    $this->assertNoField('langcode', 'Language is not selectable on node add/edit page by default.');

    // Adds a new language and set it as default.
    $edit = array(
      'predefined_langcode' => 'hu',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    $edit = array(
      'site_default' => 'hu',
    );
    $this->drupalPost('admin/config/regional/language', $edit, t('Save configuration'));

    // Tests the initial language after changing the site default language.
    // First unhide the language selector
    $edit = array(
      'node_type_language_hidden' => FALSE,
    );
    $this->drupalPost('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->drupalGet('node/add/article');
    $this->assertField('langcode', 'Language is selectable on node add/edit page when language not hidden.');
    $this->assertOptionSelected('edit-langcode', 'hu', 'The inital language is the site default on the node add page after the site default language is changed.');

    // Changes the inital language settings.
    $edit = array(
      'node_type_language_default' => 'en',
    );
    $this->drupalPost('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->drupalGet('node/add/article');
    $this->assertOptionSelected('edit-langcode', 'en', 'The inital language is the defined language.');
  }
}
