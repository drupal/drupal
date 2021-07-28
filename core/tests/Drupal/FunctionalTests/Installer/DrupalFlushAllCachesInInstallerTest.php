<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests drupal_flush_all_caches() during an install.
 *
 * @group Installer
 */
class DrupalFlushAllCachesInInstallerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'cache_flush_test';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Cache flush test',
      'install' => ['language'],
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/cache_flush_test';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/cache_flush_test.info.yml", Yaml::encode($info));
    $php_code = <<<EOF
<?php
function cache_flush_test_install() {
  // Note it is bad practice to call this method during hook_install() as it
  // results in an additional expensive container rebuild.
  drupal_flush_all_caches();
  // Ensure services are available after calling drupal_flush_all_caches().
  \Drupal::state()->set('cache_flush_test', \Drupal::hasService('language_negotiator'));
}
EOF;

    file_put_contents("$path/cache_flush_test.install", $php_code);
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertTrue(\Drupal::state()->get('cache_flush_test'));
  }

}
