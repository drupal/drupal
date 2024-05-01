<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Serialization\Yaml;
use Drupal\user\Entity\User;

/**
 * Tests superuser access and the installer.
 *
 * @group Installer
 */
class SuperUserAccessInstallTest extends InstallerTestBase {

  /**
   * Message when the logged-in user does not have admin access after install.
   *
   * @see \Drupal\Core\Installer\Form\SiteConfigureForm::submitForm())
   */
  protected const NO_ACCESS_MESSAGE = 'The user %s does not have administrator access. For more information, see the documentation on securing the admin super user.';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'superuser';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Superuser testing profile',
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/superuser';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/superuser.info.yml", Yaml::encode($info));

    file_put_contents("$path/superuser.install", $this->getProvidedData()['install_code']);

    $services = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/sites/default/default.services.yml'));
    $services['parameters']['security.enable_super_user'] = $this->getProvidedData()['super_user_policy'];
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/services.yml', Yaml::encode($services));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    if ($this->getProvidedData()['super_user_policy'] === FALSE && empty($this->getProvidedData()['expected_roles'])) {
      $this->assertSession()->pageTextContains('Site account');
      $this->assertSession()->pageTextNotContains('Site maintenance account');
    }
    else {
      $this->assertSession()->pageTextNotContains('Site account');
      $this->assertSession()->pageTextContains('Site maintenance account');
    }
    parent::setUpSite();
  }

  /**
   * Confirms that the installation succeeded.
   *
   * @dataProvider getInstallTests
   */
  public function testInstalled(bool $expected_runtime_has_permission, bool $expected_no_access_message, array $expected_roles, string $install_code, bool $super_user_policy): void {
    $user = User::load(1);
    $this->assertSame($expected_runtime_has_permission, $user->hasPermission('administer software updates'));
    $this->assertTrue(\Drupal::state()->get('admin_permission_in_installer'));
    $message = sprintf(static::NO_ACCESS_MESSAGE, $this->rootUser->getDisplayName());
    if ($expected_no_access_message) {
      $this->assertSession()->pageTextContains($message);
    }
    else {
      $this->assertSession()->pageTextNotContains($message);
    }
    $this->assertSame($expected_roles, $user->getRoles(TRUE));
  }

  public static function getInstallTests(): array {
    $test_cases = [];
    $test_cases['runtime super user policy enabled'] = [
      'expected_runtime_has_permission' => TRUE,
      'expected_no_access_message' => FALSE,
      'expected_roles' => [],
      'install_code' => <<<PHP
      <?php
      function superuser_install() {
        \$user = \Drupal\user\Entity\User::load(1);
        \Drupal::state()->set('admin_permission_in_installer', \$user->hasPermission('administer software updates'));
      }
      PHP,
      'super_user_policy' => TRUE,
    ];

    $test_cases['no super user policy enabled and no admin role'] = [
      'expected_runtime_has_permission' => FALSE,
      'expected_no_access_message' => TRUE,
      'expected_roles' => [],
      'install_code' => $test_cases['runtime super user policy enabled']['install_code'],
      'super_user_policy' => FALSE,
    ];

    $test_cases['no super user policy enabled and admin role'] = [
      'expected_runtime_has_permission' => TRUE,
      'expected_no_access_message' => FALSE,
      'expected_roles' => ['admin_role'],
      'install_code' => <<<PHP
      <?php
      function superuser_install() {
        \$user = \Drupal\user\Entity\User::load(1);
        \Drupal::state()->set('admin_permission_in_installer', \$user->hasPermission('administer software updates'));
        \Drupal\user\Entity\Role::create(['id' => 'admin_role', 'label' => 'Admin role'])->setIsAdmin(TRUE)->save();
        \Drupal\user\Entity\Role::create(['id' => 'another_role', 'label' => 'Another role'])->save();
      }
      PHP,
      'super_user_policy' => FALSE,
    ];

    $test_cases['no super user policy enabled and multiple admin role'] = [
      'expected_runtime_has_permission' => TRUE,
      'expected_no_access_message' => FALSE,
      'expected_roles' => ['admin_role', 'another_admin_role'],
      'install_code' => <<<PHP
      <?php
      function superuser_install() {
        \$user = \Drupal\user\Entity\User::load(1);
        \Drupal::state()->set('admin_permission_in_installer', \$user->hasPermission('administer software updates'));
        \Drupal\user\Entity\Role::create(['id' => 'admin_role', 'label' => 'Admin role'])->setIsAdmin(TRUE)->save();
        \Drupal\user\Entity\Role::create(['id' => 'another_admin_role', 'label' => 'Another admin role'])->setIsAdmin(TRUE)->save();
        \Drupal\user\Entity\Role::create(['id' => 'another_role', 'label' => 'Another role'])->save();
      }
      PHP,
      'super_user_policy' => FALSE,
    ];

    return $test_cases;
  }

}
