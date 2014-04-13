<?php

/**
 * @file
 * Contains \Drupal\toolbar\Tests\ToolbarAdminMenuTest.
 */

namespace Drupal\toolbar\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the caching of the admin menu subtree items.
 *
 * The cache of the admin menu subtree items will be invalidated if the
 * following hooks are invoked.
 *
 * toolbar_modules_enabled()
 * toolbar_modules_disabled()
 * toolbar_menu_link_update()
 * toolbar_user_update()
 * toolbar_user_role_update()
 *
 * Each hook invocation is simulated and then the previous hash of the admin
 * menu subtrees is compared to the new hash.
 */
class ToolbarAdminMenuTest extends WebTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var object
   */
  protected $admin_user;

  /**
   * A second user with permission to access the administrative toolbar.
   *
   * @var object
   */
  protected $admin_user_2;

  /**
   * The current admin menu subtrees hash for admin_user.
   *
   * @var string
   */
  protected $hash;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'block', 'menu_ui', 'user', 'taxonomy', 'toolbar', 'language', 'test_page_test', 'locale');

  public static function getInfo() {
    return array(
      'name' => 'Toolbar admin menu',
      'description' => 'Tests the caching of secondary admin menu items.',
      'group' => 'Toolbar',
    );
  }

  function setUp() {
    parent::setUp();

    $perms = array(
      'access toolbar',
      'access administration pages',
      'administer site configuration',
      'bypass node access',
      'administer themes',
      'administer nodes',
      'access content overview',
      'administer blocks',
      'administer menu',
      'administer modules',
      'administer permissions',
      'administer users',
      'access user profiles',
      'administer taxonomy',
      'administer languages',
      'translate interface',
    );

    // Create an administrative user and log it in.
    $this->admin_user = $this->drupalCreateUser($perms);
    $this->admin_user_2 = $this->drupalCreateUser($perms);

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    // Store the admin_user admin menu subtrees hash for comparison later.
    $this->hash = $this->getSubtreesHash();
  }

  /**
   * Tests the toolbar_modules_installed() and toolbar_modules_uninstalled() hook
   * implementations.
   */
  function testModuleStatusChangeSubtreesHashCacheClear() {
    // Uninstall a module.
    $edit = array();
    $edit['uninstall[taxonomy]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    // Confirm the uninstall form.
    $this->drupalPostForm(NULL, array(), t('Uninstall'));
    $this->rebuildContainer();

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Enable a module.
    $edit = array();
    $edit['modules[Core][taxonomy][enable]'] = TRUE;
    $this->drupalPostForm('admin/modules', $edit, t('Save configuration'));
    $this->rebuildContainer();

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();
  }

  /**
   * Tests toolbar_menu_link_update() hook implementation.
   */
  function testMenuLinkUpdateSubtreesHashCacheClear() {
    // Get subtree items for the admin menu.
    $query = \Drupal::entityQuery('menu_link');
    for ($i = 1; $i <= 3; $i++) {
      $query->sort('p' . $i, 'ASC');
    }
    $query->condition('menu_name', 'admin');
    $query->condition('depth', '2', '>=');

    // Build an ordered array of links using the query result object.
    $links = array();
    if ($result = $query->execute()) {
      $links = menu_link_load_multiple($result);
    }
    // Get the first link in the set.
    $links = array_values($links);
    $link = array_shift($links);

    // Disable the link.
    $edit = array();
    $edit['enabled'] = FALSE;
    $this->drupalPostForm("admin/structure/menu/item/" . $link['mlid'] . "/edit", $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('The menu link has been saved.');

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();
  }

  /**
   * Exercises the toolbar_user_role_update() and toolbar_user_update() hook
   * implementations.
   */
  function testUserRoleUpdateSubtreesHashCacheClear() {
    // Find the new role ID.
    $all_rids = $this->admin_user->getRoles();
    unset($all_rids[array_search(DRUPAL_AUTHENTICATED_RID, $all_rids)]);
    $rid = reset($all_rids);

    $edit = array();
    $edit[$rid . '[administer taxonomy]'] = FALSE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Test that assigning a user an extra role only affects that single user.
    // Get the hash for a second user.
    $this->drupalLogin($this->admin_user_2);
    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    $admin_user_2_hash = $this->getSubtreesHash();

    // Log in the first admin user again.
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    $this->hash = $this->getSubtreesHash();

    $rid = $this->drupalCreateRole(array('administer content types',));

    // Assign the role to the user.
    $this->drupalPostForm('user/' . $this->admin_user->id() . '/edit', array("roles[$rid]" => $rid), t('Save'));
    $this->assertText(t('The changes have been saved.'));

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Log in the second user again and assert that their subtrees hash did not
    // change.
    $this->drupalLogin($this->admin_user_2);

    // Request a new page to refresh the drupalSettings object.
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtree hash and the new admin menu
    // subtree hash are the same.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertEqual($admin_user_2_hash, $new_subtree_hash, 'The user-specific subtree menu hash has not been updated.');
  }

  /**
   * Tests that all toolbar cache entries for a user are cleared with a cache
   * tag for that user, i.e. cache entries for all languages for that user.
   */
  function testCacheClearByCacheTag() {
    // Test that the toolbar admin menu subtrees cache is invalidated for a user
    // across multiple languages.
    $this->drupalLogin($this->admin_user);
    $toolbarCache = $this->container->get('cache.toolbar');
    $admin_user_id = $this->admin_user->id();
    $admin_user_2_id = $this->admin_user_2->id();

    // Assert that a cache tag in the toolbar cache under the key "user" exists
    // for admin_user against the language "en".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_id . ':' . 'en');
    $this->assertEqual($cache->tags[0], 'user:' . $admin_user_id, 'A cache tag in the toolbar cache under the key "user" exists for admin_user against the language "en".');

    // Assert that no toolbar cache exists for admin_user against the
    // language "fr".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_id . ':' . 'fr');
    $this->assertFalse($cache, 'No toolbar cache exists for admin_user against the language "fr".');

    // Install a second language.
    $edit = array(
      'predefined_langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');

    // Request a page in 'fr' to update the cache.
    $this->drupalGet('fr/test-page');
    $this->assertResponse(200);

    // Assert that a cache tag in the toolbar cache under the key "user" exists
    // for admin_user against the language "fr".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_id . ':' . 'fr');
    $this->assertEqual($cache->tags[0], 'user:' . $admin_user_id, 'A cache tag in the toolbar cache under the key "user" exists for admin_user against the language "fr".');

    // Log in the admin_user_2 user. We will use this user as a control to
    // verify that clearing a cache tag for admin_user does not clear the cache
    // for admin_user_2.
    $this->drupalLogin($this->admin_user_2);

    // Request a page in 'en' to create the cache.
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    // Assert that a cache tag in the toolbar cache under the key "user" exists
    // for admin_user_2 against the language "en".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_2_id . ':' . 'en');
    $this->assertEqual($cache->tags[0], 'user:' . $admin_user_2_id, 'A cache tag in the toolbar cache under the key "user" exists for admin_user_2 against the language "en".');

    // Request a page in 'fr' to create the cache.
    $this->drupalGet('fr/test-page');
    $this->assertResponse(200);
    // Assert that a cache tag in the toolbar cache under the key "user" exists
    // for admin_user against the language "fr".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_2_id . ':' . 'fr');
    $this->assertEqual($cache->tags[0], 'user:' . $admin_user_2_id, 'A cache tag in the toolbar cache under the key "user" exists for admin_user_2 against the language "fr".');

    // Log in admin_user and clear the caches for this user using a tag.
    $this->drupalLogin($this->admin_user);
    Cache::deleteTags(array('user' => array($admin_user_id)));

    // Assert that no toolbar cache exists for admin_user against the
    // language "en".
    $cache = $toolbarCache->get($admin_user_id . ':' . 'en');
    $this->assertFalse($cache, 'No toolbar cache exists for admin_user against the language "en".');

    // Assert that no toolbar cache exists for admin_user against the
    // language "fr".
    $cache = $toolbarCache->get($admin_user_id . ':' . 'fr');
    $this->assertFalse($cache, 'No toolbar cache exists for admin_user against the language "fr".');

    // Log in admin_user_2 and verify that this user's caches still exist.
    $this->drupalLogin($this->admin_user_2);

    // Assert that a cache tag in the toolbar cache under the key "user" exists
    // for admin_user_2 against the language "en".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_2_id . ':' . 'en');
    $this->assertEqual($cache->tags[0], 'user:' . $admin_user_2_id, 'A cache tag in the toolbar cache under the key "user" exists for admin_user_2 against the language "en".');

    // Assert that a cache tag in the toolbar cache under the key "user" exists
    // for admin_user_2 against the language "fr".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_2_id . ':' . 'fr');
    $this->assertEqual($cache->tags[0], 'user:' . $admin_user_2_id, 'A cache tag in the toolbar cache under the key "user" exists for admin_user_2 against the language "fr".');
  }

  /**
   * Tests that changes to a user account by another user clears the changed
   * account's toolbar cached, not the user's who took the action.
   */
  function testNonCurrentUserAccountUpdates() {
    $toolbarCache = $this->container->get('cache.toolbar');
    $admin_user_id = $this->admin_user->id();
    $admin_user_2_id = $this->admin_user_2->id();
    $this->hash = $this->getSubtreesHash();

    // admin_user_2 will add a role to admin_user.
    $this->drupalLogin($this->admin_user_2);
    $rid = $this->drupalCreateRole(array('administer content types',));

    // Get the subtree hash for admin_user_2 to check later that it has not
    // changed. Request a new page to refresh the drupalSettings object.
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    $admin_user_2_hash = $this->getSubtreesHash();

    // Assign the role to the user.
    $this->drupalPostForm('user/' . $admin_user_id . '/edit', array("roles[$rid]" => $rid), t('Save'));
    $this->assertText(t('The changes have been saved.'));

    // Log in admin_user and assert that the subtrees hash has changed.
    $this->drupalLogin($this->admin_user);
    $this->assertDifferentHash();

    // Log in admin_user_2 to check that its subtrees hash has not changed.
    $this->drupalLogin($this->admin_user_2);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin_user subtree hash and the new admin_user
    // subtree hash are the same.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertEqual($admin_user_2_hash, $new_subtree_hash, 'The user-specific subtree menu hash has not been updated.');
  }

  /**
   * Tests that toolbar cache is cleared when string translations are made.
   */
  function testLocaleTranslationSubtreesHashCacheClear() {
    $toolbarCache = $this->container->get('cache.toolbar');
    $admin_user = $this->admin_user;
    $admin_user_id = $this->admin_user->id();
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));

    // Create a new language with the langcode 'xx'.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);
    // This is the language indicator on the translation search screen for
    // untranslated strings.
    $language_indicator = "<em class=\"locale-untranslated\">$langcode</em> ";
    // This will be the translation of $name.
    $translation = $this->randomName(16);
    $translation_to_en = $this->randomName(16);

    // Add custom language.
    $this->drupalLogin($admin_user);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->assertRaw('"edit-languages-' . $langcode .'-weight"', 'Language code found.');
    $this->assertText(t($name), 'Test language added.');

    // Have the admin_user request a page in the new language.
    $this->drupalGet($langcode . '/test-page');
    $this->assertResponse(200);

    // Assert that a cache tag in the toolbar cache under the key "user" exists
    // for admin_user against the language "xx".
    $cache = $toolbarCache->get('toolbar_' . $admin_user_id . ':' . $langcode);
    $this->assertEqual($cache->tags[0], 'user:' . $admin_user_id, 'A cache tag in the toolbar cache under the key "user" exists for admin_user against the language "xx".');

    // Get a baseline hash for the admin menu subtrees before translating one
    // of the menu link items.
    $original_subtree_hash = $this->getSubtreesHash();
    $this->assertTrue($original_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->drupalLogout();

    // Translate the string 'Menus' in the xx language. This string appears in
    // a link in the admin menu subtrees. Changing the string should create a
    // new menu hash if the toolbar subtrees cache is properly cleared.
    $this->drupalLogin($translate_user);
    $search = array(
      'string' => 'Menus',
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available'));
    $this->assertText($name, 'Search found the string as untranslated.');

    // Assume this is the only result.
    // Translate the string to a random string.
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $translation,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));
    $this->assertText(t('The strings have been saved.'), 'The strings have been saved.');
    $this->assertEqual($this->getUrl(), url('admin/config/regional/translate', array('absolute' => TRUE)), 'Correct page redirection.');
    $this->drupalLogout();

    // Log in the admin_user. Check the admin menu subtrees hash now that one
    // of the link items in the Structure tree (Menus) has had its text
    // translated.
    $this->drupalLogin($admin_user);
    // Have the admin_user request a page in the new language.
    $this->drupalGet($langcode . '/test-page');
    $this->assertResponse(200);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtrees hash and the new admin menu
    // subtrees hash are different.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEqual($original_subtree_hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');
  }

  /**
   * Tests that the 'toolbar/subtrees/{hash}' is reachable.
   */
  function testSubtreesJsonRequest() {
    $admin_user = $this->admin_user;
    $this->drupalLogin($admin_user);
    // Request a new page to refresh the drupalSettings object.
    $subtrees_hash = $this->getSubtreesHash();

    $this->drupalGetJSON('toolbar/subtrees/' . $subtrees_hash);
    $this->assertResponse('200');

    // Test that the subtrees hash changes with a different language code and
    // that JSON is returned when a language code is specified.
    // Create a new language with the langcode 'xx'.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Get a page with the new language langcode in the URL.
    $this->drupalGet('/xx/test-page');
    // Request a new page to refresh the drupalSettings object.
    $subtrees_hash = $this->getSubtreesHash();

    $this->drupalGetJSON('toolbar/subtrees/' . $subtrees_hash . '/' . $langcode);
    $this->assertResponse('200');
  }

  /**
   *  Test that subtrees hashes vary by the language of the page.
   */
  function testLanguageSwitching() {
    // Create a new language with the langcode 'xx'.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Get a page with the new language langcode in the URL.
    $this->drupalGet('/xx/test-page');
    // Assert different hash.
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtree hash and the new admin menu
    // subtree hash are different.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEqual($this->hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');
  }

  /**
   * Get the hash value from the admin menu subtrees route path.
   *
   * @return string
   *   The hash value from the admin menu subtrees route path.
   */
  private function getSubtreesHash() {
    $settings = $this->drupalGetSettings();
    // The toolbar module defines a route '/toolbar/subtrees/{hash}' that
    // returns JSON for the rendered subtrees. This hash is provided to the
    // client in drupalSettings.
    return $settings['toolbar']['subtreesHash'];
  }

  /**
   * Asserts the subtrees hash on a fresh page GET is different from the hash
   * from the previous page GET.
   */
  private function assertDifferentHash() {
    // Request a new page to refresh the drupalSettings object.
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtree hash and the new admin menu
    // subtree hash are different.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEqual($this->hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');

    // Save the new subtree hash as the original.
    $this->hash = $new_subtree_hash;
  }

}
