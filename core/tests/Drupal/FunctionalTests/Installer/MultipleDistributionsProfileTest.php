<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Serialization\Yaml;

/**
 * Tests multiple distribution profile support.
 *
 * @group Installer
 */
class MultipleDistributionsProfileTest extends InstallerTestBase {

  /**
   * The distribution profile info.
   *
   * @var array
   */
  protected $info;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // Create two distributions.
    foreach (['distribution_one', 'distribution_two'] as $name) {
      $info = [
        'type' => 'profile',
        'core_version_requirement' => '*',
        'name' => $name . ' profile',
        'distribution' => [
          'name' => $name,
          'install' => [
            'theme' => 'bartik',
          ],
        ],
      ];
      // File API functions are not available yet.
      $path = $this->root . DIRECTORY_SEPARATOR . $this->siteDirectory . '/profiles/' . $name;
      mkdir($path, 0777, TRUE);
      file_put_contents("$path/$name.info.yml", Yaml::encode($info));
    }
    // Install the first distribution.
    $this->profile = 'distribution_one';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Verify that the distribution name appears.
    $this->assertSession()->pageTextContains('distribution_one');
    // Verify that the requested theme is used.
    $this->assertSession()->responseContains('bartik');
    // Verify that the "Choose profile" step does not appear.
    $this->assertSession()->pageTextNotContains('profile');

    parent::setUpLanguage();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // This step is skipped, because there is a distribution profile.
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getAccountName());

    // Confirm that Drupal recognizes this distribution as the current profile.
    $this->assertEquals('distribution_one', \Drupal::installProfile());
    $this->assertEquals('distribution_one', $this->config('core.extension')->get('profile'), 'The install profile has been written to core.extension configuration.');

    $this->rebuildContainer();
    $this->assertEquals('distribution_one', \Drupal::installProfile());
  }

}
