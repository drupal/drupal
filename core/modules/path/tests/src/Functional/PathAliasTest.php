<?php

namespace Drupal\Tests\path\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

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
  protected static $modules = ['path'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'administer url aliases',
      'create url aliases',
      'access content overview',
    ]);
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
    $edit['path[0][value]'] = '/node/' . $node1->id();
    $edit['alias[0][value]'] = '/' . $this->randomMachineName(8);
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    // Check the path alias whitelist cache.
    $whitelist = \Drupal::cache('bootstrap')->get('path_alias_whitelist');
    $this->assertTrue($whitelist->data['node']);
    $this->assertFalse($whitelist->data['admin']);

    // Visit the system path for the node and confirm a cache entry is
    // created.
    \Drupal::cache('data')->deleteAll();
    // Make sure the path is not converted to the alias.
    $this->drupalGet(trim($edit['path[0][value]'], '/'), ['alias' => TRUE]);
    $this->assertNotEmpty(\Drupal::cache('data')->get('preload-paths:' . $edit['path[0][value]']), 'Cache entry was created.');

    // Visit the alias for the node and confirm a cache entry is created.
    \Drupal::cache('data')->deleteAll();
    // @todo Remove this once https://www.drupal.org/node/2480077 lands.
    Cache::invalidateTags(['rendered']);
    $this->drupalGet(trim($edit['alias[0][value]'], '/'));
    $this->assertNotEmpty(\Drupal::cache('data')->get('preload-paths:' . $edit['path[0][value]']), 'Cache entry was created.');
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  public function testAdminAlias() {
    // Create test node.
    $node1 = $this->drupalCreateNode();

    // Create alias.
    $edit = [];
    $edit['path[0][value]'] = '/node/' . $node1->id();
    $edit['alias[0][value]'] = '/' . $this->getRandomGenerator()->word(8);
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias works.
    $this->drupalGet($edit['alias[0][value]']);
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that the alias works in a case-insensitive way.
    $this->assertTrue(ctype_lower(ltrim($edit['alias[0][value]'], '/')));
    $this->drupalGet($edit['alias[0][value]']);
    // Lower case.
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet(mb_strtoupper($edit['alias[0][value]']));
    // Upper case.
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->statusCodeEquals(200);

    // Change alias to one containing "exotic" characters.
    $pid = $this->getPID($edit['alias[0][value]']);

    $previous = $edit['alias[0][value]'];
    // Lower-case letters.
    $edit['alias[0][value]'] = '/alias' .
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
      // cSpell:disable-next-line
      $edit['alias[0][value]'] .= "ïвβéø";
    }
    $this->drupalGet('admin/config/search/path/edit/' . $pid);
    $this->submitForm($edit, 'Save');

    // Confirm that the alias works.
    $this->drupalGet(mb_strtoupper($edit['alias[0][value]']));
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->statusCodeEquals(200);

    $this->container->get('path_alias.manager')->cacheClear();
    // Confirm that previous alias no longer works.
    $this->drupalGet($previous);
    $this->assertSession()->pageTextNotContains($node1->label());
    $this->assertSession()->statusCodeEquals(404);

    // Create second test node.
    $node2 = $this->drupalCreateNode();

    // Set alias to second test node.
    $edit['path[0][value]'] = '/node/' . $node2->id();
    // leave $edit['alias'] the same
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    // Confirm no duplicate was created.
    $this->assertSession()->pageTextContains("The alias {$edit['alias[0][value]']} is already in use in this language.");

    $edit_upper = $edit;
    $edit_upper['alias[0][value]'] = mb_strtoupper($edit['alias[0][value]']);
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit_upper, 'Save');
    $this->assertSession()->pageTextContains("The alias {$edit_upper['alias[0][value]']} could not be added because it is already in use in this language with different capitalization: {$edit['alias[0][value]']}.");

    // Delete alias.
    $this->drupalGet('admin/config/search/path/edit/' . $pid);
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the URL alias {$edit['alias[0][value]']}?");
    $this->submitForm([], 'Delete');

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['alias[0][value]']);
    $this->assertSession()->pageTextNotContains($node1->label());
    $this->assertSession()->statusCodeEquals(404);

    // Create a really long alias.
    $edit = [];
    $edit['path[0][value]'] = '/node/' . $node1->id();
    $alias = '/' . $this->randomMachineName(128);
    $edit['alias[0][value]'] = $alias;
    // The alias is shortened to 50 characters counting the ellipsis.
    $truncated_alias = substr($alias, 0, 47);
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');
    // The untruncated alias should not be found.
    $this->assertSession()->pageTextNotContains($alias);
    // The 'truncated' alias will always be found.
    $this->assertSession()->pageTextContains($truncated_alias);

    // Create third test node.
    $node3 = $this->drupalCreateNode();

    // Create absolute path alias.
    $edit = [];
    $edit['path[0][value]'] = '/node/' . $node3->id();
    $node3_alias = '/' . $this->randomMachineName(8);
    $edit['alias[0][value]'] = $node3_alias;
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    // Create fourth test node.
    $node4 = $this->drupalCreateNode();

    // Create alias with trailing slash.
    $edit = [];
    $edit['path[0][value]'] = '/node/' . $node4->id();
    $node4_alias = '/' . $this->randomMachineName(8);
    $edit['alias[0][value]'] = $node4_alias . '/';
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias with trailing slash is not found.
    $this->assertSession()->pageTextNotContains($edit['alias[0][value]']);
    // The alias without trailing flash is found.
    $this->assertSession()->pageTextContains(trim($edit['alias[0][value]'], '/'));

    // Update an existing alias to point to a different source.
    $pid = $this->getPID($node4_alias);
    $edit = [];
    $edit['alias[0][value]'] = $node4_alias;
    $edit['path[0][value]'] = '/node/' . $node2->id();
    $this->drupalGet('admin/config/search/path/edit/' . $pid);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The alias has been saved.');
    $this->drupalGet($edit['alias[0][value]']);
    // Previous alias should no longer work.
    $this->assertSession()->pageTextNotContains($node4->label());
    // Alias should work.
    $this->assertSession()->pageTextContains($node2->label());
    $this->assertSession()->statusCodeEquals(200);

    // Update an existing alias to use a duplicate alias.
    $pid = $this->getPID($node3_alias);
    $edit = [];
    $edit['alias[0][value]'] = $node4_alias;
    $edit['path[0][value]'] = '/node/' . $node3->id();
    $this->drupalGet('admin/config/search/path/edit/' . $pid);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("The alias {$edit['alias[0][value]']} is already in use in this language.");

    // Create an alias without a starting slash.
    $node5 = $this->drupalCreateNode();

    $edit = [];
    $edit['path[0][value]'] = 'node/' . $node5->id();
    $node5_alias = $this->randomMachineName(8);
    $edit['alias[0][value]'] = $node5_alias . '/';
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->addressEquals('admin/config/search/path/add');
    $this->assertSession()->pageTextContains('The source path has to start with a slash.');
    $this->assertSession()->pageTextContains('The alias path has to start with a slash.');
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
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->statusCodeEquals(200);

    // Confirm the 'canonical' and 'shortlink' URLs.
    $elements = $this->xpath("//link[contains(@rel, 'canonical') and contains(@href, '" . $edit['path[0][alias]'] . "')]");
    $this->assertNotEmpty($elements, 'Page contains canonical link URL.');
    $elements = $this->xpath("//link[contains(@rel, 'shortlink') and contains(@href, 'node/" . $node1->id() . "')]");
    $this->assertNotEmpty($elements, 'Page contains shortlink URL.');

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
      // cSpell:disable-next-line
      $edit['path[0][alias]'] .= "ïвβéø";
    }
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias works.
    $this->drupalGet(mb_strtoupper($edit['path[0][alias]']));
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that previous alias no longer works.
    $this->drupalGet($previous);
    $this->assertSession()->pageTextNotContains($node1->label());
    $this->assertSession()->statusCodeEquals(404);

    // Create second test node.
    $node2 = $this->drupalCreateNode();

    // Set alias to second test node.
    // Leave $edit['path[0][alias]'] the same.
    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias didn't make a duplicate.
    $this->assertSession()->pageTextContains("The alias {$edit['path[0][alias]']} is already in use in this language.");

    // Delete alias.
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->submitForm(['path[0][alias]' => ''], 'Save');

    // Confirm that the alias no longer works.
    $this->drupalGet($edit['path[0][alias]']);
    $this->assertSession()->pageTextNotContains($node1->label());
    $this->assertSession()->statusCodeEquals(404);

    // Create third test node.
    $node3 = $this->drupalCreateNode();

    // Set its path alias to an absolute path.
    $edit = ['path[0][alias]' => '/' . $this->randomMachineName(8)];
    $this->drupalGet('node/' . $node3->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias was converted to a relative path.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertSession()->pageTextContains($node3->label());
    $this->assertSession()->statusCodeEquals(200);

    // Create fourth test node.
    $node4 = $this->drupalCreateNode();

    // Set its path alias to have a trailing slash.
    $edit = ['path[0][alias]' => '/' . $this->randomMachineName(8) . '/'];
    $this->drupalGet('node/' . $node4->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias was converted to a relative path.
    $this->drupalGet(trim($edit['path[0][alias]'], '/'));
    $this->assertSession()->pageTextContains($node4->label());
    $this->assertSession()->statusCodeEquals(200);

    // Create fifth test node.
    $node5 = $this->drupalCreateNode();

    // Set a path alias.
    $edit = ['path[0][alias]' => '/' . $this->randomMachineName(8)];
    $this->drupalGet('node/' . $node5->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Delete the node and check that the path alias is also deleted.
    $node5->delete();
    $path_alias = \Drupal::service('path_alias.repository')->lookUpBySystemPath('/node/' . $node5->id(), $node5->language()->getId());
    $this->assertNull($path_alias, 'Alias was successfully deleted when the referenced node was deleted.');

    // Create sixth test node.
    $node6 = $this->drupalCreateNode();

    // Test the special case where the alias is '0'.
    $edit = ['path[0][alias]' => '0'];
    $this->drupalGet($node6->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The alias path has to start with a slash.');

    // Create an invalid alias with two leading slashes and verify that the
    // extra slash is removed when the link is generated. This ensures that URL
    // aliases cannot be used to inject external URLs.
    // @todo The user interface should either display an error message or
    //   automatically trim these invalid aliases, rather than allowing them to
    //   be silently created, at which point the functional aspects of this
    //   test will need to be moved elsewhere and switch to using a
    //   programmatically-created alias instead.
    $alias = $this->randomMachineName(8);
    $edit = ['path[0][alias]' => '//' . $alias];
    $this->drupalGet($node6->toUrl('edit-form'));
    $this->submitForm($edit, 'Save');
    $this->drupalGet(Url::fromRoute('system.admin_content'));
    // This checks the link href before clicking it, rather than using
    // \Drupal\Tests\BrowserTestBase::assertSession()->addressEquals() after
    // clicking it, because the test browser does not always preserve the
    // correct number of slashes in the URL when it visits internal links;
    // using \Drupal\Tests\BrowserTestBase::assertSession()->addressEquals()
    // would actually make the test pass unconditionally on the testbot (or
    // anywhere else where Drupal is installed in a subdirectory).
    $this->assertSession()->elementAttributeContains('xpath', "//a[normalize-space(text())='{$node6->getTitle()}']", 'href', base_path() . $alias);
    $this->clickLink($node6->getTitle());
    $this->assertSession()->statusCodeEquals(404);
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
    $result = \Drupal::entityTypeManager()->getStorage('path_alias')->getQuery()
      ->condition('alias', $alias, '=')
      ->accessCheck(FALSE)
      ->execute();
    return reset($result);
  }

  /**
   * Tests that duplicate aliases fail validation.
   */
  public function testDuplicateNodeAlias() {
    // Create one node with a random alias.
    $node_one = $this->drupalCreateNode();
    $edit = [];
    $edit['path[0][alias]'] = '/' . $this->randomMachineName();
    $this->drupalGet('node/' . $node_one->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Now create another node and try to set the same alias.
    $node_two = $this->drupalCreateNode();
    $this->drupalGet('node/' . $node_two->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("The alias {$edit['path[0][alias]']} is already in use in this language.");
    $path_alias = $this->assertSession()->fieldExists('path[0][alias]');
    $this->assertSession()->fieldValueEquals('path[0][alias]', $edit['path[0][alias]']);
    $this->assertTrue($path_alias->hasClass('error'));

    // Behavior here differs with the inline_form_errors module enabled.
    // Enable the inline_form_errors module and try this again. This module
    // improves validation with a link in the error message(s) to the fields
    // which have invalid input.
    $this->assertTrue($this->container->get('module_installer')->install(['inline_form_errors'], TRUE), 'Installed inline_form_errors.');
    // Attempt to edit the second node again, as before.
    $this->drupalGet('node/' . $node_two->id() . '/edit');
    $this->submitForm($edit, 'Preview');
    // This error should still be present next to the field.
    $this->assertSession()->pageTextContains("The alias {$edit['path[0][alias]']} is already in use in this language.");
    // The validation error set for the page should include this text.
    $this->assertSession()->pageTextContains('1 error has been found: URL alias');
    // The text 'URL alias' should be a link.
    $this->assertSession()->linkExists('URL alias');
    // The link should be to the ID of the URL alias field.
    $this->assertSession()->linkByHrefExists('#edit-path-0-alias');
  }

}
