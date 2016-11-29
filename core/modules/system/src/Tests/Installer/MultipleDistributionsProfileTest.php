<?php

namespace Drupal\system\Tests\Installer;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\InstallerTestBase;

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
  protected function setUp() {
    // Create two distributions.
    foreach (['distribution_one', 'distribution_two'] as $name) {
      $info = [
        'type' => 'profile',
        'core' => \Drupal::CORE_COMPATIBILITY,
        'name' => $name . ' profile',
        'distribution' => [
          'name' => $name,
          'install' => [
            'theme' => 'bartik',
          ],
        ],
      ];
      // File API functions are not available yet.
      $path = $this->siteDirectory . '/profiles/' . $name;
      mkdir($path, 0777, TRUE);
      file_put_contents("$path/$name.info.yml", Yaml::encode($info));
    }
    // Install the first distribution.
    $this->profile = 'distribution_one';

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Verify that the distribution name appears.
    $this->assertRaw('distribution_one');
    // Verify that the requested theme is used.
    $this->assertRaw('bartik');
    // Verify that the "Choose profile" step does not appear.
    $this->assertNoText('profile');

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
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    // Confirm that we are logged-in after installation.
    $this->assertText($this->rootUser->getUsername());

    // Confirm that Drupal recognizes this distribution as the current profile.
    $this->assertEqual(\Drupal::installProfile(), 'distribution_one');
    $this->assertEqual(Settings::get('install_profile'), 'distribution_one', 'The install profile has been written to settings.php.');
    $this->assertEqual($this->config('core.extension')->get('profile'), 'distribution_one', 'The install profile has been written to core.extension configuration.');

    $this->rebuildContainer();
    $this->pass('Container can be rebuilt as distribution is written to configuration.');
    $this->assertEqual(\Drupal::installProfile(), 'distribution_one');
  }

}
