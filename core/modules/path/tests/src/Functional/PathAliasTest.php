<?php

namespace Drupal\Tests\path\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;

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
  public static $modules = ['path'];

  protected function setUp() {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser(['create page content', 'edit own page content', 'administer url aliases', 'create url aliases']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the path cache.
   */
  public function testPathCache() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = [];
    $edit['source'] = '/node/' . $node1->id();
    $edit['alias'] = '/' . $this->randomMachineName(8);
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Check the path alias whitelist cache.
    $whitelist = \Drupal::cache('bootstrap')->get('path_alias_whitelist');
    $this->assertTrue($whitelist->data['node']);
    $this->assertFalse($whitelist->data['admin']);

    // Visit the system path for the node and confirm a cache entry is
    // created.
    \Drupal::cache('data')->deleteAll();
    // Make sure the path is not converted to the alias.
    $this->drupalGet(trim($edit['source'], '/'), ['alias' => TRUE]);
    $this->assertTrue(\Drupal::cache('data')->get('preload-paths:' . $edit['source']), 'Cache entry was created.');

    // Visit the alias for the node and confirm a cache entry is created.
    \Drupal::cache('data')->deleteAll();
    // @todo Remove this once https://www.drupal.org/node/2480077 lands.
    Cache::invalidateTags(['rendered']);
    $this->drupalGet(trim($edit['alias'], '/'));
    $this->assertTrue(\Drupal::cache('data')->get('preload-paths:' . $edit['source']), 'Cache entry was created.');
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  public function testAdminAlias() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = [];
    $edit['source'] = '/node/' . $node1->id();
    $edit['alias'] = '/' . $this->getRandomGenerator()->word(8);
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet($edit['alias']);
    $this->assertText($node1->label(), 'Alias works.');
    $this->assertResponse(200);
    // Confirm that the alias works in a case-insensitive way.
    $this->assertTrue(ctype_lower(ltrim($edit['alias'], '/')));
    $this->drupalGet($edit['alias']);
    $this->assertText($node1->label(), 'Alias works lower case.');
    $this->assertResponse(200);
    $this->drupalGet(mb_strtoupper($edit['alias']));
    $this->assertText($node1->label(), 'Alias works upper case.');
    $this->assertResponse(200);

    // Change alias to one containing "exotic" characters.
    $pid = $this->getPID($edit['alias']);

    $previous = $edit['alias'];
    // Lower-case letters.
    $edit['alias'] = '/alias' .
      // "Special" ASCII characters.
      "- ._~!$'\"()*@[]?&+%#,;=:" .
      // Characters that look like a percent-escaped string.
      "%23%25%26%2B%2F%3F" .
      // Characters from various non-ASCII alphabets.
      "中國書۞";
    $connection = Database::getConnection();
    if ($connection->databaseType() != 'sqlite') {
      // When using LIKE for case-insensitivity, the SQLite driver is
      // currently unable to find the upper-case versions of non-ASCII
      // characters.
      // @todo fix this in https://www.drupal.org/node/2607432
      $edit['alias'] .= "ïвβéø";
    }
    $this->drupalPostForm('admin/config/search/path/edit/' . $pid, $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet(mb_strtoupper($edit['alias']));
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
    $edit['source'] = '/node/' . $node2->id();
    // leave $edit['alias'] the same
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Confirm no duplicate was created.
    $this->assertRaw(t('The alias %alias is already in use in this language.', ['%alias' => $edit['alias']]), 'Attempt to move alias was rejected.');

    $edit_upper = $edit;
    $edit_upper['alias'] = mb_strtoupper($edit['alias']);
    $this->drupalPostForm('admin/config/search/path/add', $edit_upper, t('Save'));
    $this->assertRaw(t('The alias %alias could not be added because it is already in use in this language with different capitalization: %stored_alias.', [
      '%alias' => $edit_upper['alias'],
      '%stored_alias' => $edit['alias'],
    ]), 'Attempt to move upper-case alias was rejected.');

    // Delete alias.
    $this->drupalGet('admin/config/search/path/edit/' . $pid);
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete path alias %name?', ['%name' => $edit['alias']]));
    $this->drupalPostForm(NULL, [], t('Confirm'));

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['alias']);
    $this->assertNoText($node1->label(), 'Alias was successfully deleted.');
    $this->assertResponse(404);

    // Create a really long alias.
    $edit = [];
    $edit['source'] = '/node/' . $node1->id();
    $alias = '/' . $this->randomMachineName(128);
    $edit['alias'] = $alias;
    // The alias is shortened to 50 characters counting the ellipsis.
    $truncated_alias = substr($alias, 0, 47);
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));
    $this->assertNoText($alias, 'The untruncated alias was not found.');
    // The 'truncated' alias will always be found.
    $this->assertText($truncated_alias, 'The truncated alias was found.');

    // Create third test node.
    $node3 = $this->drupalCreateNode();

    // Create absolute path alias.
    $edit = [];
    $edit['source'] = '/node/' . $node3->id();
    $node3_alias = '/' . $this->randomMachineName(8);
    $edit['alias'] = $node3_alias;
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    // Create fourth test node.
    $node4 = $this->drupalCreateNode();

    // Create alias with trailing slash.
    $edit = [];
    $edit['source'] = '/node/' . $node4->id();
    $node4_alias = '/' . $this->randomMachineName(8);
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
    $edit['source'] = '/node/' . $node2->id();
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
    $edit['source'] = '/node/' . $node3->id();
    $this->drupalPostForm('admin/config/search/path/edit/' . $pid, $edit, t('Save'));
    $this->assertRaw(t('The alias %alias is already in use in this language.', ['%alias' => $edit['alias']]));

    // Create an alias without a starting slash.
    $node5 = $this->drupalCreateNode();

    $edit = [];
    $edit['source'] = 'node/' . $node5->id();
    $node5_alias = $this->randomMachineName(8);
    $edit['alias'] = $node5_alias . '/';
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $this->assertUrl('admin/config/search/path/add');
    $this->assertText('The source path has to start with a slash.');
    $this->assertText('The alias path has to start with a slash.');
  }

  /**
   * Tests alias functionality through the node interfaces.
   */
  public function testNodeAlias() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = [];
    $edit['path[0][alias]'] = '/' . $this->randomMachineName(8);
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

    $previous = $edit['path[0][alias]'];
    // Change alias to one containing "exotic" characters.
    // Lower-case letters.
    $edit['path[0][alias]'] = '/alias' .
      // "Special" ASCII characters.
      "- ._~!$'\"()*@[]?&+%#,;=:" .
      // Characters that look like a percent-escaped string.
      "%23%25%26%2B%2F%3F" .
      // Characters from various non-ASCII alphabets.
      "中國書۞";
    $connection = Database::getConnection();
    if ($connection->databaseType() != 'sqlite') {
      // When using LIKE for case-insensitivity, the SQLite driver is
      // currently unable to find the upper-case versions of non-ASCII
      // characters.
      // @todo fix this in https://www.drupal.org/node/2607432
      $edit['path[0][alias]'] .= "ïвβéø";
    }
    $this->drupalPostForm('node/' . $node1->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias works.
    $this->drupalGet(mb_strtoupper($edit['path[0][alias]']));
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
    $this->drupalPostForm('node/' . $node1->id() . '/edit', ['path[0][alias]' => ''], t('Save'));

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertNoText($node1->label(), 'Alias was successfully deleted.');
    $this->assertResponse(404);

    // Create third test node.
    $node3 = $this->drupalCreateNode();

    // Set its path alias to an absolute path.
    $edit = ['path[0][alias]' => '/' . $this->randomMachineName(8)];
    $this->drupalPostForm('node/' . $node3->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias was converted to a relative path.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertText($node3->label(), 'Alias became relative.');
    $this->assertResponse(200);

    // Create fourth test node.
    $node4 = $this->drupalCreateNode();

    // Set its path alias to have a trailing slash.
    $edit = ['path[0][alias]' => '/' . $this->randomMachineName(8) . '/'];
    $this->drupalPostForm('node/' . $node4->id() . '/edit', $edit, t('Save'));

    // Confirm that the alias was converted to a relative path.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertText($node4->label(), 'Alias trimmed trailing slash.');
    $this->assertResponse(200);

    // Create fifth test node.
    $node5 = $this->drupalCreateNode();

    // Set a path alias.
    $edit = ['path[0][alias]' => '/' . $this->randomMachineName(8)];
    $this->drupalPostForm('node/' . $node5->id() . '/edit', $edit, t('Save'));

    // Delete the node and check that the path alias is also deleted.
    $node5->delete();
    $path_alias = \Drupal::service('path.alias_storage')->lookupPathAlias('/node/' . $node5->id(), $node5->language()->getId());
    $this->assertFalse($path_alias, 'Alias was successfully deleted when the referenced node was deleted.');
  }

  /**
   * Returns the path ID.
   *
   * @param string $alias
   *   A string containing an aliased path.
   *
   * @return int
   *   Integer representing the path ID.
   */
  public function getPID($alias) {
    return db_query("SELECT pid FROM {url_alias} WHERE alias = :alias", [':alias' => $alias])->fetchField();
  }

  /**
   * Tests that duplicate aliases fail validation.
   */
  public function testDuplicateNodeAlias() {
    // Create one node with a random alias.
    $node_one = $this->drupalCreateNode();
    $edit = [];
    $edit['path[0][alias]'] = '/' . $this->randomMachineName();
    $this->drupalPostForm('node/' . $node_one->id() . '/edit', $edit, t('Save'));

    // Now create another node and try to set the same alias.
    $node_two = $this->drupalCreateNode();
    $this->drupalPostForm('node/' . $node_two->id() . '/edit', $edit, t('Save'));
    $this->assertText(t('The alias is already in use.'));
    $this->assertFieldByXPath("//input[@name='path[0][alias]' and contains(@class, 'error')]", $edit['path[0][alias]'], 'Textfield exists and has the error class.');

    // Behavior here differs with the inline_form_errors module enabled.
    // Enable the inline_form_errors module and try this again. This module
    // improves validation with a link in the error message(s) to the fields
    // which have invalid input.
    $this->assertTrue($this->container->get('module_installer')->install(['inline_form_errors'], TRUE), 'Installed inline_form_errors.');
    // Attempt to edit the second node again, as before.
    $this->drupalPostForm('node/' . $node_two->id() . '/edit', $edit, t('Preview'));
    // This error should still be present next to the field.
    $this->assertSession()->pageTextContains(t('The alias is already in use.'), 'Field error found with expected text.');
    // The validation error set for the page should include this text.
    $this->assertSession()->pageTextContains(t('1 error has been found: URL alias'), 'Form error found with expected text.');
    // The text 'URL alias' should be a link.
    $this->assertSession()->linkExists('URL alias');
    // The link should be to the ID of the URL alias field.
    $this->assertSession()->linkByHrefExists('#edit-path-0-alias');
  }

}
