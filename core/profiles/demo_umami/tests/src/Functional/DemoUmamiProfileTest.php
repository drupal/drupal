<?php

namespace Drupal\Tests\demo_umami\Functional;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\editor\Entity\Editor;
use Drupal\KernelTests\AssertConfigTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\SchemaCheckTestTrait;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests demo_umami profile.
 *
 * @group demo_umami
 * @group #slow
 */
class DemoUmamiProfileTest extends BrowserTestBase {
  use AssertConfigTrait;
  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    $parameters['forms']['install_configure_form']['site_mail'] = 'admin@example.com';
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Tests some features specific to being a demonstration profile.
   */
  public function testDemoSpecificFeatures() {
    // This test coverage is organized into separate protected methods rather
    // than individual test methods to avoid having to reinstall Umami for
    // a handful of assertions each.
    $this->testUser();
    $this->testWarningsOnStatusPage();
    $this->testAppearance();
    $this->testDemonstrationWarningMessage();
  }

  /**
   * Tests demo_umami profile warnings shown on Status Page.
   */
  protected function testWarningsOnStatusPage() {
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);

    // Check the requirements warning for using an experimental profile.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains('Experimental profiles are provided for testing purposes only. Use at your own risk. To start building a new site, reinstall Drupal and choose a non-experimental profile.');
  }

  /**
   * Tests the profile supplied configuration is the same after installation.
   */
  public function testConfig() {
    // Just connect directly to the config table so we don't need to worry about
    // the cache layer.
    $active_config_storage = $this->container->get('config.storage');

    $default_config_storage = new FileStorage($this->container->get('extension.list.profile')->getPath('demo_umami') . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY, InstallStorage::DEFAULT_COLLECTION);
    $this->assertDefaultConfig($default_config_storage, $active_config_storage);

    $default_config_storage = new FileStorage($this->container->get('extension.list.profile')->getPath('demo_umami') . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY, InstallStorage::DEFAULT_COLLECTION);
    $this->assertDefaultConfig($default_config_storage, $active_config_storage);

    // Now we have all configuration imported, test all of them for schema
    // conformance. Ensures all imported default configuration is valid when
    // Demo Umami profile modules are enabled.
    $names = $this->container->get('config.storage')->listAll();
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    foreach ($names as $name) {
      $config = $this->config($name);
      $this->assertConfigSchema($typed_config, $name, $config->get());
    }

    // Validate all configuration.
    // @todo Generalize in https://www.drupal.org/project/drupal/issues/2164373
    foreach (Editor::loadMultiple() as $editor) {
      // Currently only text editors using CKEditor 5 can be validated.
      if ($editor->getEditor() !== 'ckeditor5') {
        continue;
      }

      $this->assertSame([], array_map(
        function (ConstraintViolation $v) {
          return (string) $v->getMessage();
        },
        iterator_to_array(CKEditor5::validatePair(
          $editor,
          $editor->getFilterFormat()
        ))
      ));
    }
  }

  /**
   * Asserts that the default configuration matches active configuration.
   *
   * @param \Drupal\Core\Config\StorageInterface $default_config_storage
   *   The default configuration storage to check.
   * @param \Drupal\Core\Config\StorageInterface $active_config_storage
   *   The active configuration storage.
   */
  protected function assertDefaultConfig(StorageInterface $default_config_storage, StorageInterface $active_config_storage): void {
    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = $this->container->get('config.manager');

    foreach ($default_config_storage->listAll() as $config_name) {
      if ($active_config_storage->exists($config_name)) {
        $result = $config_manager->diff($default_config_storage, $active_config_storage, $config_name);
        $this->assertConfigDiff($result, $config_name, [
          // The filter.format.*:roles key is a special install key.
          'filter.format.basic_html' => ['roles:', '  - authenticated'],
          'filter.format.full_html' => ['roles:', '  - administrator'],
          'filter.format.restricted_html' => ['roles:', '  - anonymous'],
          // The system.site config is overwritten during tests by
          // FunctionalTestSetupTrait::installParameters().
          'system.site' => ['uuid:', 'name:', 'mail:'],
        ]);
      }
      else {
        $this->fail("$config_name has not been installed");
      }
    }
  }

  /**
   * Tests that the users can log in with the admin password entered at install.
   */
  protected function testUser() {
    $password = $this->rootUser->pass_raw;
    $ids = \Drupal::entityQuery('user')
      ->accessCheck(FALSE)
      ->condition('roles', ['author', 'editor'], 'IN')
      ->execute();

    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($ids);

    foreach ($users as $user) {
      $this->drupalLoginWithPassword($user, $password);
    }
  }

  /**
   * Tests the successful editing of nodes by admin.
   */
  public function testEditNodesByAdmin() {
    $permissions = [
      'administer nodes',
      'edit any recipe content',
      'use editorial transition create_new_draft',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
    $webassert = $this->assertSession();

    // Check that admin is able to edit the node.
    $nodes = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['title' => 'Deep mediterranean quiche']);
    $node = reset($nodes);
    $this->drupalGet($node->toUrl('edit-form'));
    $webassert->statusCodeEquals('200');

    $this->submitForm([], 'Preview');
    $webassert->statusCodeEquals('200');
    $this->assertSession()->elementsCount('css', 'h1', 1);
    $this->clickLink('Back to content editing');

    $this->submitForm([], "Save");
    $webassert->pageTextContains('Recipe Deep mediterranean quiche has been updated.');
  }

  /**
   * Tests that the Umami theme is available on the Appearance page.
   */
  protected function testAppearance() {
    $account = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($account);
    $webassert = $this->assertSession();

    $this->drupalGet('admin/appearance');
    $webassert->pageTextContains('Umami');
  }

  /**
   * Tests that the toolbar warning only appears on the admin pages.
   */
  protected function testDemonstrationWarningMessage() {
    $permissions = [
      'access content overview',
      'access toolbar',
      'administer nodes',
      'edit any recipe content',
      'create recipe content',
      'use editorial transition create_new_draft',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
    $web_assert = $this->assertSession();

    $nodes = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['title' => 'Deep mediterranean quiche']);
    /** @var \Drupal\node\Entity\Node $recipe_node */
    $recipe_node = reset($nodes);

    // Check when editing a node, the warning is visible.
    $this->drupalGet($recipe_node->toUrl('edit-form'));
    $web_assert->statusCodeEquals('200');
    $web_assert->pageTextContains('This site is intended for demonstration purposes.');

    // Check when adding a node, the warning is visible.
    $this->drupalGet('node/add/recipe');
    $web_assert->statusCodeEquals('200');
    $web_assert->pageTextContains('This site is intended for demonstration purposes.');

    // Check when looking at admin/content, the warning is visible.
    $this->drupalGet('admin/content');
    $web_assert->statusCodeEquals('200');
    $web_assert->pageTextContains('This site is intended for demonstration purposes.');

    // Check when viewing a node, the warning is not visible.
    $this->drupalGet($recipe_node->toUrl());
    $web_assert->statusCodeEquals('200');
    $web_assert->pageTextNotContains('This site is intended for demonstration purposes.');

    // Check when viewing the homepage, the warning is not visible.
    $this->drupalGet('<front>');
    $web_assert->statusCodeEquals('200');
    $web_assert->pageTextNotContains('This site is intended for demonstration purposes.');
  }

  /**
   * Logs in a user using the Mink controlled browser using a password.
   *
   * If a user is already logged in, then the current user is logged out before
   * logging in the specified user.
   *
   * Note that neither the current user nor the passed-in user object is
   * populated with data of the logged in user. If you need full access to the
   * user object after logging in, it must be updated manually. If you also need
   * access to the plain-text password of the user (set by drupalCreateUser()),
   * e.g. to log in the same user again, then it must be re-assigned manually.
   * For example:
   * @code
   *   // Create a user.
   *   $account = $this->drupalCreateUser(array());
   *   $this->drupalLogin($account);
   *   // Load real user object.
   *   $pass_raw = $account->passRaw;
   *   $account = User::load($account->id());
   *   $account->passRaw = $pass_raw;
   * @endcode
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object representing the user to log in.
   * @param string $password
   *   The password to authenticate the user with.
   *
   * @see drupalCreateUser()
   */
  protected function drupalLoginWithPassword(AccountInterface $account, $password) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    $this->drupalGet('user/login');
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $password,
    ], 'Log in');

    // @see ::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
