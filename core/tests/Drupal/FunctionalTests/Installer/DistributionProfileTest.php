<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Serialization\Yaml;

/**
 * Tests distribution profile support.
 *
 * @group Installer
 */
class DistributionProfileTest extends InstallerTestBase {

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

  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $this->info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Distribution profile',
      'distribution' => [
        'name' => 'My Distribution',
        'install' => [
          'theme' => 'claro',
          'finish_url' => '/root-user',
        ],
      ],
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/my_distribution';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/my_distribution.info.yml", Yaml::encode($this->info));
    file_put_contents("$path/my_distribution.install", "<?php function my_distribution_install() {\Drupal::entityTypeManager()->getStorage('path_alias')->create(['path' => '/user/1', 'alias' => '/root-user'])->save();}");
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Verify that the distribution name appears.
    $this->assertSession()->pageTextContains($this->info['distribution']['name']);
    // Verify that the distribution name is used in the site title.
    $this->assertSession()->titleEquals('Choose language | ' . $this->info['distribution']['name']);
    // Verify that the requested theme is used.
    $this->assertSession()->responseContains($this->info['distribution']['install']['theme']);
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
    $this->assertSession()->addressEquals('root-user');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getAccountName());

    // Confirm that Drupal recognizes this distribution as the current profile.
    $this->assertEquals('my_distribution', \Drupal::installProfile());
    $this->assertEquals('my_distribution', $this->config('core.extension')->get('profile'), 'The install profile has been written to core.extension configuration.');
  }

}
