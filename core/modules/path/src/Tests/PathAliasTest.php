<?php

/**
 * @file
 * Definition of Drupal\path\Tests\PathAliasTest.
 */

namespace Drupal\path\Tests;

/**
 * Add, edit, delete, and change alias and verify its consistency in the
 * database.
 *
 * @group path
 */
class PathAliasTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('path');

  protected function setUp() {
    parent::setUp();

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
    $edit['source'] = 'node/' . $node1->id();
    $edit['alias'] = $this->randomMachineName(8);
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Check the path alias whitelist cache.
    $whitelist = \Drupal::cache()->get('path_alias_whitelist');
    $this->assertTrue($whitelist->data['node']);
    $this->assertFalse($whitelist->data['admin']);

    // Visit the system path for the node and confirm a cache entry is
    // created.
    \Drupal::cache('data')->deleteAll();
    // Make sure the path is not converted to the alias.
    $this->drupalGet($edit['source'], array('alias' => TRUE));
    $this->assertTrue(\Drupal::cache('data')->get('preload-paths:' . $edit['source']), 'Cache entry was created.');

    // Visit the alias for the node and confirm a cache entry is created.
    \Drupal::cache('data')->deleteAll();
    $this->drupalGet($edit['alias']);
    $this->assertTrue(\Drupal::cache('data')->get('preload-paths:' .  $edit['source']), 'Cache entry was created.');
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  function testAdminAlias() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = array();
    $edit['source'] = 'node/' . $node1->id();
    $edit['alias'] = $this->randomMachineName(8);
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['alias']);
    $this->assertText($node1->label(), 'Alias works.');
    $this->assertResponse(200);

    // Change alias to one containing "exotic" characters.
    $pid = $this->getPID($edit['alias']);

    $previous = $edit['alias'];
    $edit['alias'] = "- ._~!$'\"()*@[]?&+%#,;=:" . // "Special" ASCII characters.
      "%23%25%26%2B%2F%3F" . // Characters that look like a percent-escaped string.
      "éøïвβ中國書۞"; // Characters from various non-ASCII alphabets.
    $this->drupalPostForm('admin/config/search/path/edit/' . $pid, $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['alias']);
    $this->assertText($node1->label(), 'Changed alias works.');
    $this->assertResponse(200);

    $this->container->get('path.alias_manager')->cacheClear();
    // Confirm that previous alias no longer works.
    $this->drupalGet($previous);
    $this->assertNoText($node1->label(), 'Previous alias no longer works.');
    $this->assertResponse(404);

    // Create second test node.
    $node2 = $this->drupalCreateNode();

    // Set alias to second test node.
    $edit['source'] = 'node/' . $node2->id();
    // leave $edit['alias'] the same
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Confirm no duplicate was created.
    $this->assertRaw(t('The alias %alias is already in use in this language.', array('%alias' => $edit['alias'])), 'Attempt to move alias was rejected.');

    // Delete alias.
    $this->drupalPostForm('admin/config/search/path/edit/' . $pid, array(), t('Delete'));
    $this->drupalPostForm(NULL, array(), t('Confirm'));

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['alias']);
    $this->assertNoText($node1->label(), 'Alias was successfully deleted.');
    $this->assertResponse(404);

    // Create a really long alias.
    $edit = array();
    $edit['source'] = 'node/' . $node1->id();
    $alias = $this->randomMachineName(128);
    $edit['alias'] = $alias;
    // The alias is shortened to 50 characters counting the elipsis.
    $truncated_alias = substr($alias, 0, 47);
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));
    $this->assertNoText($alias, 'The untruncated alias was not found.');
    // The 'truncated' alias will always be found.
    $this->assertText($truncated_alias, 'The truncated alias was found.');

    // Create third test node.
    $node3 = $this->drupalCreateNode();

    // Create absolute path alias.
    $edit = array();
    $edit['source'] = 'node/' . $node3->id();
    $node3_alias = $this->randomMachineName(8);
    $edit['alias'] = '/' . $node3_alias;
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Confirm that the alias was converted to a relative path.
    $this->assertNoText($edit['alias'], 'The absolute alias was not found.');
    // The 'relative' alias will always be found.
    $this->assertText(trim($edit['alias'], '/'), 'The relative alias was found.');

    // Create fourth test node.
    $node4 = $this->drupalCreateNode();

    // Create alias with trailing slash.
    $edit = array();
    $edit['source'] = 'node/' . $node4->id();
    $node4_alias = $this->randomMachineName(8);
    $edit['alias'] = $node4_alias . '/';
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Confirm that the alias with trailing slash is not found.
    $this->assertNoText($edit['alias'], 'The absolute alias was not found.');
    // The alias without trailing flash is found.
    $this->assertText(trim($edit['alias'], '/'), 'The alias without trailing slash was found.');

    // Update an existing alias to point to a different source.
    $pid = $this->getPID($node4_alias);
    $edit = [];
    $edit['alias'] = $node4_alias;
    $edit['source'] = 'node/' . $node2->id();
    $this->drupalPostForm('admin/config/search/path/edit/' . $pid, $edit, t('Save'));
    $this->assertText('The alias has been saved.');
    $this->drupalGet($edit['alias']);
    $this->assertNoText($node4->label(), 'Previous alias no longer works.');
    $this->assertText($node2->label(), 'Alias works.');
    $this->assertResponse(200);

    // Update an existing alias to use a duplicate alias.
    $pid = $this->getPID($node3_alias);
    $edit = [];
    $edit['alias'] = $node4_alias;
    $edit['source'] = 'node/' . $node3->id();
    $this->drupalPostForm('admin/config/search/path/edit/' . $pid, $edit, t('Save'));
    $this->assertRaw(t('The alias %alias is already in use in this language.', array('%alias' => $edit['alias'])));

  }

  /**
   * Tests alias functionality through the node interfaces.
   */
  function testNodeAlias() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = array();
    $edit['path[0][alias]'] = $this->randomMachineName(8);
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertText($node1->label(), 'Alias works.');
    $this->assertResponse(200);

    // Confirm the 'canonical' and 'shortlink' URLs.
    $elements = $this->xpath("//link[contains(@rel, 'canonical') and contains(@href, '" . $edit['path[0][alias]'] . "')]");
    $this->assertTrue(!empty($elements), 'Page contains canonical link URL.');
    $elements = $this->xpath("//link[contains(@rel, 'shortlink') and contains(@href, 'node/" . $node1->id() . "')]");
    $this->assertTrue(!empty($elements), 'Page contains shortlink URL.');

    // Change alias to one containing "exotic" characters.
    $previous = $edit['path[0][alias]'];
    $edit['path[0][alias]'] = "- ._~!$'\"()*@[]?&+%#,;=:" . // "Special" ASCII characters.
      "%23%25%26%2B%2F%3F" . // Characters that look like a percent-escaped string.
      "éøïвβ中國書۞"; // Characters from various non-ASCII alphabets.
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertText($node1->label(), 'Changed alias works.');
    $this->assertResponse(200);

    // Make sure that previous alias no longer works.
    $this->drupalGet($previous);
    $this->assertNoText($node1->label(), 'Previous alias no longer works.');
    $this->assertResponse(404);

    // Create second test node.
    $node2 = $this->drupalCreateNode();

    // Set alias to second test node.
    // Leave $edit['path[0][alias]'] the same.
    $this->drupalPostForm('node/' . $node2->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias didn't make a duplicate.
    $this->assertText(t('The alias is already in use.'), 'Attempt to moved alias was rejected.');

    // Delete alias.
    $this->drupalPostForm('node/' . $node1->id() . '/edit', array('path[0][alias]' => ''), t('Save'));

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertNoText($node1->label(), 'Alias was successfully deleted.');
    $this->assertResponse(404);

    // Create third test node.
    $node3 = $this->drupalCreateNode();

    // Set its path alias to an absolute path.
    $edit = array('path[0][alias]' => '/' . $this->randomMachineName(8));
    $this->drupalPostForm('node/' . $node3->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias was converted to a relative path.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertText($node3->label(), 'Alias became relative.');
    $this->assertResponse(200);

    // Create fourth test node.
    $node4 = $this->drupalCreateNode();

    // Set its path alias to have a trailing slash.
    $edit = array('path[0][alias]' => $this->randomMachineName(8) . '/');
    $this->drupalPostForm('node/' . $node4->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias was converted to a relative path.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertText($node4->label(), 'Alias trimmed trailing slash.');
    $this->assertResponse(200);
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
    $edit['path[0][alias]'] = $this->randomMachineName();
    $this->drupalPostForm('node/' . $node_one->id() . '/edit', $edit, t('Save'));

    // Now create another node and try to set the same alias.
    $node_two = $this->drupalCreateNode();
    $this->drupalPostForm('node/' . $node_two->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('The alias is already in use.'));
    $this->assertFieldByXPath("//input[@name='path[0][alias]' and contains(@class, 'error')]", $edit['path[0][alias]'], 'Textfield exists and has the error class.');
  }
}
