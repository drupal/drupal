<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that an install profile with only dependencies works as expected.
 *
 * @group Installer
 * @group legacy
 */
class InstallProfileDependenciesBcTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_install_profile_dependencies_bc';

  /**
   * Tests that the install profile BC layer for dependencies key works.
   *
   * @expectedDeprecation The install profile core/profiles/testing_install_profile_dependencies_bc/testing_install_profile_dependencies_bc.info.yml only implements a 'dependencies' key. As of Drupal 8.6.0 profile's support a new 'install' key for modules that should be installed but not depended on. See https://www.drupal.org/node/2952947.
   */
  public function testUninstallingModules() {
    $user = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($user);
    $this->drupalGet('admin/modules/uninstall');
    $this->getSession()->getPage()->checkField('uninstall[ban]');
    $this->getSession()->getPage()->checkField('uninstall[dblog]');
    $this->click('#edit-submit');
    // Click the confirm button.
    $this->click('#edit-submit');
    $this->assertSession()->responseContains('The selected modules have been uninstalled.');
    $this->assertSession()->responseContains('No modules are available to uninstall.');
    // We've uninstalled modules therefore we need to rebuild the container in
    // the test runner.
    $this->rebuildContainer();
    $module_handler = $this->container->get('module_handler');
    $this->assertFalse($module_handler->moduleExists('ban'));
    $this->assertFalse($module_handler->moduleExists('dblog'));
  }

}
