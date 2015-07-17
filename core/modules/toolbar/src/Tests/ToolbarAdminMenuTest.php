<?php

/**
 * @file
 * Contains \Drupal\toolbar\Tests\ToolbarAdminMenuTest.
 */

namespace Drupal\toolbar\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;
use Drupal\user\RoleInterface;

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
 *
 * @group toolbar
 */
class ToolbarAdminMenuTest extends WebTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A second user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser2;

  /**
   * The current admin menu subtrees hash for adminUser.
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

  protected function setUp() {
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
    $this->adminUser = $this->drupalCreateUser($perms);
    $this->adminUser2 = $this->drupalCreateUser($perms);

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    // Store the adminUser admin menu subtrees hash for comparison later.
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
   * Tests toolbar cache tags implementation.
   */
  function testMenuLinkUpdateSubtreesHashCacheClear() {
    // The ID of a (any) admin menu link.
    $admin_menu_link_id = 'system.admin_config_development';

    // Disable the link.
    $edit = array();
    $edit['enabled'] = FALSE;
    $this->drupalPostForm("admin/structure/menu/link/" . $admin_menu_link_id . "/edit", $edit, t('Save'));
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
    $all_rids = $this->adminUser->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    $rid = reset($all_rids);

    $edit = array();
    $edit[$rid . '[administer taxonomy]'] = FALSE;
    $this->drupalPostForm('admin/people/permissions', $edit, t('Save permissions'));

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Test that assigning a user an extra role only affects that single user.
    // Get the hash for a second user.
    $this->drupalLogin($this->adminUser2);
    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    $admin_user_2_hash = $this->getSubtreesHash();

    // Log in the first admin user again.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test-page');
    $this->assertResponse(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertRaw('id="toolbar-administration"');

    $this->hash = $this->getSubtreesHash();

    $rid = $this->drupalCreateRole(array('administer content types',));

    // Assign the role to the user.
    $this->drupalPostForm('user/' . $this->adminUser->id() . '/edit', array("roles[$rid]" => $rid), t('Save'));
    $this->assertText(t('The changes have been saved.'));

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Log in the second user again and assert that their subtrees hash did not
    // change.
    $this->drupalLogin($this->adminUser2);

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
   * Tests that changes to a user account by another user clears the changed
   * account's toolbar cached, not the user's who took the action.
   */
  function testNonCurrentUserAccountUpdates() {
    $admin_user_id = $this->adminUser->id();
    $admin_user_2_id = $this->adminUser2->id();
    $this->hash = $this->getSubtreesHash();

    // adminUser2 will add a role to adminUser.
    $this->drupalLogin($this->adminUser2);
    $rid = $this->drupalCreateRole(array('administer content types',));

    // Get the subtree hash for adminUser2 to check later that it has not
    // changed. Request a new page to refresh the drupalSettings object.
    $this->drupalGet('test-page');
    $this->assertResponse(200);
    $admin_user_2_hash = $this->getSubtreesHash();

    // Assign the role to the user.
    $this->drupalPostForm('user/' . $admin_user_id . '/edit', array("roles[$rid]" => $rid), t('Save'));
    $this->assertText(t('The changes have been saved.'));

    // Log in adminUser and assert that the subtrees hash has changed.
    $this->drupalLogin($this->adminUser);
    $this->assertDifferentHash();

    // Log in adminUser2 to check that its subtrees hash has not changed.
    $this->drupalLogin($this->adminUser2);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old adminUser subtree hash and the new adminUser
    // subtree hash are the same.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertEqual($admin_user_2_hash, $new_subtree_hash, 'The user-specific subtree menu hash has not been updated.');
  }

  /**
   * Tests that toolbar cache is cleared when string translations are made.
   */
  function testLocaleTranslationSubtreesHashCacheClear() {
    $admin_user = $this->adminUser;
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser(array('translate interface', 'access administration pages'));

    // Create a new language with the langcode 'xx'.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomMachineName(16);
    // This is the language indicator on the translation search screen for
    // untranslated strings.
    $language_indicator = "<em class=\"locale-untranslated\">$langcode</em> ";
    // This will be the translation of $name.
    $translation = $this->randomMachineName(16);
    $translation_to_en = $this->randomMachineName(16);

    // Add custom language.
    $this->drupalLogin($admin_user);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
    t($name, array(), array('langcode' => $langcode));
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->assertRaw('"edit-languages-' . $langcode .'-weight"', 'Language code found.');
    $this->assertText(t($name), 'Test language added.');

    // Have the adminUser request a page in the new language.
    $this->drupalGet($langcode . '/test-page');
    $this->assertResponse(200);

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
    $this->assertUrl(\Drupal::url('locale.translate_page', [], ['absolute' => TRUE]), [], 'Correct page redirection.');
    $this->drupalLogout();

    // Log in the adminUser. Check the admin menu subtrees hash now that one
    // of the link items in the Structure tree (Menus) has had its text
    // translated.
    $this->drupalLogin($admin_user);
    // Have the adminUser request a page in the new language.
    $this->drupalGet($langcode . '/test-page');
    $this->assertResponse(200);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtrees hash and the new admin menu
    // subtrees hash are different.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEqual($original_subtree_hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');
  }

  /**
   * Tests that the 'toolbar/subtrees/{hash}' is reachable and correct.
   */
  function testSubtreesJsonRequest() {
    $admin_user = $this->adminUser;
    $this->drupalLogin($admin_user);
    // Request a new page to refresh the drupalSettings object.
    $subtrees_hash = $this->getSubtreesHash();

    $this->drupalGetJSON('toolbar/subtrees/' . $subtrees_hash);
    $this->assertResponse('200');
    $json_callback_start = substr($this->getRawContent(), 0, 39);
    $json_callback_end = substr($this->getRawContent(), -2, 2);
    $json = substr($this->getRawContent(), 39, strlen($this->getRawContent()) - 41);
    $this->assertTrue($json_callback_start === '/**/Drupal.toolbar.setSubtrees.resolve(' && $json_callback_end === ');', 'Subtrees response is wrapped in callback.');
    $subtrees = Json::decode($json);
    $this->assertEqual(array_keys($subtrees), ['system-admin_content', 'system-admin_structure', 'system-themes_page', 'system-modules_list', 'system-admin_config', 'entity-user-collection', 'front'], 'Correct subtrees JSON returned.');
  }

  /**
   *  Test that subtrees hashes vary by the language of the page.
   */
  function testLanguageSwitching() {
    // Create a new language with the langcode 'xx'.
    $langcode = 'xx';
    $language = ConfigurableLanguage::createFromLangcode($langcode);
    $language->save();
    // The language path processor is just registered for more than one
    // configured language, so rebuild the container now that we are
    // multilingual.
    $this->rebuildContainer();

    // Get a page with the new language langcode in the URL.
    $this->drupalGet('test-page', array('language' => $language));
    // Assert different hash.
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtree hash and the new admin menu
    // subtree hash are different.
    $this->assertTrue($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEqual($this->hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');
  }

  /**
   * Test that back to site link exists on admin pages, not on content pages.
   */
  public function testBackToSiteLink() {
    // Back to site link should exist in the markup.
    $this->drupalGet('test-page');
    $back_link = $this->cssSelect('.home-toolbar-tab');
    $this->assertTrue($back_link);
  }

  /**
   * Get the hash value from the admin menu subtrees route path.
   *
   * @return string
   *   The hash value from the admin menu subtrees route path.
   */
  private function getSubtreesHash() {
    $settings = $this->getDrupalSettings();
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
