<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\Functional;

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
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
 * @group #slow
 */
class ToolbarAdminMenuTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'block',
    'menu_ui',
    'user',
    'taxonomy',
    'toolbar',
    'language',
    'test_page_test',
    'locale',
    'search',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $perms = [
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
      'administer search',
    ];

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser($perms);
    $this->adminUser2 = $this->drupalCreateUser($perms);

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertSession()->responseContains('id="toolbar-administration"');

    // Store the adminUser admin menu subtrees hash for comparison later.
    $this->hash = $this->getSubtreesHash();
  }

  /**
   * Tests Toolbar's responses to installing and uninstalling modules.
   *
   * @see toolbar_modules_installed()
   * @see toolbar_modules_uninstalled()
   */
  public function testModuleStatusChangeSubtreesHashCacheClear(): void {
    // Use an admin role to ensure the user has all available permissions. This
    // results in the admin menu links changing as the taxonomy module is
    // installed and uninstalled because the role will always have the
    // 'administer taxonomy' permission if it exists.
    $role = Role::load($this->createRole([]));
    $role->setIsAdmin(TRUE);
    $role->save();
    $this->adminUser->addRole($role->id())->save();

    // Uninstall a module.
    $edit = [];
    $edit['uninstall[taxonomy]'] = TRUE;
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm($edit, 'Uninstall');
    // Confirm the uninstall form.
    $this->submitForm([], 'Uninstall');
    $this->rebuildContainer();

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Enable a module.
    $edit = [];
    $edit['modules[taxonomy][enable]'] = TRUE;
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');
    $this->rebuildContainer();

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();
  }

  /**
   * Tests toolbar cache tags implementation.
   */
  public function testMenuLinkUpdateSubtreesHashCacheClear(): void {
    // The ID of (any) admin menu link.
    $admin_menu_link_id = 'system.admin_config_development';

    // Disable the link.
    $edit = [];
    $edit['enabled'] = FALSE;
    $this->drupalGet("admin/structure/menu/link/" . $admin_menu_link_id . "/edit");
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The menu link has been saved.');

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();
  }

  /**
   * Tests Toolbar's responses to user data updates.
   *
   * @see toolbar_user_role_update()
   * @see toolbar_user_update()
   */
  public function testUserRoleUpdateSubtreesHashCacheClear(): void {
    // Find the new role ID.
    $all_rids = $this->adminUser->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    $rid = reset($all_rids);

    $edit = [];
    $edit[$rid . '[administer taxonomy]'] = FALSE;
    $this->drupalGet('admin/people/permissions');
    $this->submitForm($edit, 'Save permissions');

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Test that assigning a user an extra role only affects that single user.
    // Get the hash for a second user.
    $this->drupalLogin($this->adminUser2);
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertSession()->responseContains('id="toolbar-administration"');

    $admin_user_2_hash = $this->getSubtreesHash();

    // Log in the first admin user again.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the toolbar is present in the HTML.
    $this->assertSession()->responseContains('id="toolbar-administration"');

    $this->hash = $this->getSubtreesHash();

    $rid = $this->drupalCreateRole(['administer content types']);

    // Assign the role to the user.
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->submitForm(["roles[{$rid}]" => $rid], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Assert that the subtrees hash has been altered because the subtrees
    // structure changed.
    $this->assertDifferentHash();

    // Log in the second user again and assert that their subtrees hash did not
    // change.
    $this->drupalLogin($this->adminUser2);

    // Request a new page to refresh the drupalSettings object.
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtree hash and the new admin menu
    // subtree hash are the same.
    $this->assertNotEmpty($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertEquals($admin_user_2_hash, $new_subtree_hash, 'The user-specific subtree menu hash has not been updated.');
  }

  /**
   * Tests cache invalidation when one user modifies another user.
   */
  public function testNonCurrentUserAccountUpdates(): void {
    $admin_user_id = $this->adminUser->id();
    $this->hash = $this->getSubtreesHash();

    // adminUser2 will add a role to adminUser.
    $this->drupalLogin($this->adminUser2);
    $rid = $this->drupalCreateRole(['administer content types']);

    // Get the subtree hash for adminUser2 to check later that it has not
    // changed. Request a new page to refresh the drupalSettings object.
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);
    $admin_user_2_hash = $this->getSubtreesHash();

    // Assign the role to the user.
    $this->drupalGet('user/' . $admin_user_id . '/edit');
    $this->submitForm(["roles[{$rid}]" => $rid], 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Log in adminUser and assert that the subtrees hash has changed.
    $this->drupalLogin($this->adminUser);
    $this->assertDifferentHash();

    // Log in adminUser2 to check that its subtrees hash has not changed.
    $this->drupalLogin($this->adminUser2);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old adminUser subtree hash and the new adminUser
    // subtree hash are the same.
    $this->assertNotEmpty($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertEquals($new_subtree_hash, $admin_user_2_hash, 'The user-specific subtree menu hash has not been updated.');
  }

  /**
   * Tests that toolbar cache is cleared when string translations are made.
   */
  public function testLocaleTranslationSubtreesHashCacheClear(): void {
    $admin_user = $this->adminUser;
    // User to translate and delete string.
    $translate_user = $this->drupalCreateUser([
      'translate interface',
      'access administration pages',
    ]);

    // Create a new language with the langcode 'xx'.
    $langcode = 'xx';
    // The English name for the language. This will be translated.
    $name = $this->randomMachineName(16);
    // This will be the translation of $name.
    $translation = $this->randomMachineName(16);

    // Add custom language.
    $this->drupalLogin($admin_user);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
    t($name, [], ['langcode' => $langcode]);
    // Reset locale cache.
    $this->container->get('string_translation')->reset();
    $this->assertSession()->responseContains('"edit-languages-' . $langcode . '-weight"');
    // Verify that the test language was added.
    $this->assertSession()->pageTextContains($name);

    // Have the adminUser request a page in the new language.
    $this->drupalGet($langcode . '/test-page');
    $this->assertSession()->statusCodeEquals(200);

    // Get a baseline hash for the admin menu subtrees before translating one
    // of the menu link items.
    $original_subtree_hash = $this->getSubtreesHash();
    $this->assertNotEmpty($original_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->drupalLogout();

    // Translate the string 'Search and metadata' in the xx language. This
    // string appears in a link in the admin menu subtrees. Changing the string
    // should create a new menu hash if the toolbar subtrees cache is correctly
    // invalidated.
    $this->drupalLogin($translate_user);
    // We need to visit the page to get the string to be translated.
    $this->drupalGet($langcode . '/admin/config');
    $search = [
      'string' => 'Search and metadata',
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    $this->assertSession()->pageTextNotContains('No strings available');
    // Verify that search found the string as untranslated.
    $this->assertSession()->pageTextContains($name);

    // Assume this is the only result.
    // Translate the string to a random string.
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = (string) $textarea->getAttribute('name');
    $edit = [
      $lid => $translation,
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');
    $this->assertSession()->pageTextContains('The strings have been saved.');
    // Verify that the user is redirected to the correct page.
    $this->assertSession()->addressEquals(Url::fromRoute('locale.translate_page'));
    $this->drupalLogout();

    // Log in the adminUser. Check the admin menu subtrees hash now that one
    // of the link items in the Structure tree (Menus) has had its text
    // translated.
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config');
    // Have the adminUser request a page in the new language.
    $this->drupalGet($langcode . '/test-page');
    $this->assertSession()->statusCodeEquals(200);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtrees hash and the new admin menu
    // subtrees hash are different.
    $this->assertNotEmpty($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEquals($original_subtree_hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');
  }

  /**
   * Tests that the 'toolbar/subtrees/{hash}' is reachable and correct.
   */
  public function testSubtreesJsonRequest(): void {
    $admin_user = $this->adminUser;
    $this->drupalLogin($admin_user);
    // Request a new page to refresh the drupalSettings object.
    $subtrees_hash = $this->getSubtreesHash();

    $this->drupalGet('toolbar/subtrees/' . $subtrees_hash, ['query' => [MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax']], ['X-Requested-With' => 'XMLHttpRequest']);
    $ajax_result = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertEquals('setToolbarSubtrees', $ajax_result[0]['command'], 'Subtrees response uses the correct command.');
    $this->assertEquals(['system-admin_content', 'system-admin_structure', 'system-themes_page', 'system-modules_list', 'system-admin_config', 'entity-user-collection', 'front'], array_keys($ajax_result[0]['subtrees']), 'Correct subtrees returned.');
  }

  /**
   * Tests that subtrees hashes vary by the language of the page.
   */
  public function testLanguageSwitching(): void {
    // Create a new language with the langcode 'xx'.
    $langcode = 'xx';
    $language = ConfigurableLanguage::createFromLangcode($langcode);
    $language->save();
    // The language path processor is just registered for more than one
    // configured language, so rebuild the container now that we are
    // multilingual.
    $this->rebuildContainer();

    // Get a page with the new language langcode in the URL.
    $this->drupalGet('test-page', ['language' => $language]);
    // Assert different hash.
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtree hash and the new admin menu
    // subtree hash are different.
    $this->assertNotEmpty($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEquals($this->hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');
  }

  /**
   * Tests that back to site link exists on admin pages, not on content pages.
   */
  public function testBackToSiteLink(): void {
    // Back to site link should exist in the markup.
    $this->drupalGet('test-page');
    $back_link = $this->cssSelect('.home-toolbar-tab');
    $this->assertNotEmpty($back_link);
  }

  /**
   * Tests that external links added to the menu appear in the toolbar.
   */
  public function testExternalLink(): void {
    $edit = [
      'title[0][value]' => 'External URL',
      'link[0][uri]' => 'http://example.org',
      'menu_parent' => 'admin:system.admin',
      'description[0][value]' => 'External URL & escaped',
    ];
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $this->submitForm($edit, 'Save');

    // Assert that the new menu link is shown on the menu link listing.
    $this->drupalGet('admin/structure/menu/manage/admin');
    $this->assertSession()->pageTextContains('External URL');

    // Assert that the new menu link is shown in the toolbar on a regular page.
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSession()->pageTextContains('External URL');
    // Ensure the description is escaped as expected.
    $this->assertSession()->responseContains('title="External URL &amp; escaped"');
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
   * Checks the subtree hash of the current page with that of the previous page.
   *
   * Asserts that the subtrees hash on a fresh page GET is different from the
   * subtree hash from the previous page GET.
   *
   * @internal
   */
  private function assertDifferentHash(): void {
    // Request a new page to refresh the drupalSettings object.
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);
    $new_subtree_hash = $this->getSubtreesHash();

    // Assert that the old admin menu subtree hash and the new admin menu
    // subtree hash are different.
    $this->assertNotEmpty($new_subtree_hash, 'A valid hash value for the admin menu subtrees was created.');
    $this->assertNotEquals($this->hash, $new_subtree_hash, 'The user-specific subtree menu hash has been updated.');

    // Save the new subtree hash as the original.
    $this->hash = $new_subtree_hash;
  }

}
