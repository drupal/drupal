<?php

/**
 * @file
 * Definition of Drupal\path\Tests\PathAliasTest.
 */

namespace Drupal\path\Tests;

/**
 * Tests path alias functionality.
 */
class PathAliasTest extends PathTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Path alias functionality',
      'description' => 'Add, edit, delete, and change alias and verify its consistency in the database.',
      'group' => 'Path',
    );
  }

  function setUp() {
    parent::setUp('path');

    // Create test user and login.
    $web_user = $this->drupalCreateUser(array('create page content', 'edit own page content', 'administer url aliases', 'create url aliases'));
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the path cache.
   */
  function testPathCache() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = array();
    $edit['source'] = 'node/' . $node1->nid;
    $edit['alias'] = $this->randomName(8);
    $this->drupalPost('admin/config/search/path/add', $edit, t('Save'));

    // Visit the system path for the node and confirm a cache entry is
    // created.
    cache('path')->flush();
    $this->drupalGet($edit['source']);
    $this->assertTrue(cache('path')->get($edit['source']), t('Cache entry was created.'));

    // Visit the alias for the node and confirm a cache entry is created.
    cache('path')->flush();
    $this->drupalGet($edit['alias']);
    $this->assertTrue(cache('path')->get($edit['source']), t('Cache entry was created.'));
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  function testAdminAlias() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = array();
    $edit['source'] = 'node/' . $node1->nid;
    $edit['alias'] = $this->randomName(8);
    $this->drupalPost('admin/config/search/path/add', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['alias']);
    $this->assertText($node1->title, 'Alias works.');
    $this->assertResponse(200);

    // Change alias to one containing "exotic" characters.
    $pid = $this->getPID($edit['alias']);

    $previous = $edit['alias'];
    $edit['alias'] = "- ._~!$'\"()*@[]?&+%#,;=:" . // "Special" ASCII characters.
      "%23%25%26%2B%2F%3F" . // Characters that look like a percent-escaped string.
      "éøïвβ中國書۞"; // Characters from various non-ASCII alphabets.
    $this->drupalPost('admin/config/search/path/edit/' . $pid, $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['alias']);
    $this->assertText($node1->title, 'Changed alias works.');
    $this->assertResponse(200);

    drupal_static_reset('drupal_lookup_path');
    // Confirm that previous alias no longer works.
    $this->drupalGet($previous);
    $this->assertNoText($node1->title, 'Previous alias no longer works.');
    $this->assertResponse(404);

    // Create second test node.
    $node2 = $this->drupalCreateNode();

    // Set alias to second test node.
    $edit['source'] = 'node/' . $node2->nid;
    // leave $edit['alias'] the same
    $this->drupalPost('admin/config/search/path/add', $edit, t('Save'));

    // Confirm no duplicate was created.
    $this->assertRaw(t('The alias %alias is already in use in this language.', array('%alias' => $edit['alias'])), 'Attempt to move alias was rejected.');

    // Delete alias.
    $this->drupalPost('admin/config/search/path/edit/' . $pid, array(), t('Delete'));
    $this->drupalPost(NULL, array(), t('Confirm'));

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['alias']);
    $this->assertNoText($node1->title, 'Alias was successfully deleted.');
    $this->assertResponse(404);

    // Create a really long alias.
    $edit = array();
    $edit['source'] = 'node/' . $node1->nid;
    $alias = $this->randomName(128);
    $edit['alias'] = $alias;
    // The alias is shortened to 50 characters counting the elipsis.
    $truncated_alias = substr($alias, 0, 47);
    $this->drupalPost('admin/config/search/path/add', $edit, t('Save'));
    $this->assertNoText($alias, 'The untruncated alias was not found.');
    // The 'truncated' alias will always be found.
    $this->assertText($truncated_alias, 'The truncated alias was found.');
  }

  /**
   * Tests alias functionality through the node interfaces.
   */
  function testNodeAlias() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = array();
    $edit['path[alias]'] = $this->randomName(8);
    $this->drupalPost('node/' . $node1->nid . '/edit', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['path[alias]']);
    $this->assertText($node1->title, 'Alias works.');
    $this->assertResponse(200);

    // Change alias to one containing "exotic" characters.
    $previous = $edit['path[alias]'];
    $edit['path[alias]'] = "- ._~!$'\"()*@[]?&+%#,;=:" . // "Special" ASCII characters.
      "%23%25%26%2B%2F%3F" . // Characters that look like a percent-escaped string.
      "éøïвβ中國書۞"; // Characters from various non-ASCII alphabets.
    $this->drupalPost('node/' . $node1->nid . '/edit', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['path[alias]']);
    $this->assertText($node1->title, 'Changed alias works.');
    $this->assertResponse(200);

    // Make sure that previous alias no longer works.
    $this->drupalGet($previous);
    $this->assertNoText($node1->title, 'Previous alias no longer works.');
    $this->assertResponse(404);

    // Create second test node.
    $node2 = $this->drupalCreateNode();

    // Set alias to second test node.
    // Leave $edit['path[alias]'] the same.
    $this->drupalPost('node/' . $node2->nid . '/edit', $edit, t('Save'));

    // Confirm that the alias didn't make a duplicate.
    $this->assertText(t('The alias is already in use.'), 'Attempt to moved alias was rejected.');

    // Delete alias.
    $this->drupalPost('node/' . $node1->nid . '/edit', array('path[alias]' => ''), t('Save'));

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['path[alias]']);
    $this->assertNoText($node1->title, 'Alias was successfully deleted.');
    $this->assertResponse(404);
  }

  /**
   * Returns the path ID.
   *
   * @param $alias
   *   A string containing an aliased path.
   *
   * @return int
   *   Integer representing the path ID.
   */
  function getPID($alias) {
    return db_query("SELECT pid FROM {url_alias} WHERE alias = :alias", array(':alias' => $alias))->fetchField();
  }

  /**
   * Tests that duplicate aliases fail validation.
   */
  function testDuplicateNodeAlias() {
    // Create one node with a random alias.
    $node_one = $this->drupalCreateNode();
    $edit = array();
    $edit['path[alias]'] = $this->randomName();
    $this->drupalPost('node/' . $node_one->nid . '/edit', $edit, t('Save'));

    // Now create another node and try to set the same alias.
    $node_two = $this->drupalCreateNode();
    $this->drupalPost('node/' . $node_two->nid . '/edit', $edit, t('Save'));
    $this->assertText(t('The alias is already in use.'));
    $this->assertFieldByXPath("//input[@name='path[alias]' and contains(@class, 'error')]", $edit['path[alias]'], 'Textfield exists and has the error class.');
  }
}
