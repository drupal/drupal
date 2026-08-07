<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests drupal_flush_all_caches() and container rebuilds during an install.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
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
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    $info = [
      'type' => 'profile',
      'core_version_requirement' => '*',
      'name' => 'Cache flush test',
      'install' => ['language', 'cache_flush_test_module'],
    ];
    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/cache_flush_test';
    mkdir($path, 0777, TRUE);
    file_put_contents("$path/cache_flush_test.info.yml", Yaml::encode($info));
    $php_code = <<<'EOF'
<?php
function cache_flush_test_install() {
  // Note it is bad practice to call this method during hook_install() as it
  // results in an additional expensive container rebuild.
  drupal_flush_all_caches();
  // Ensure services are available after calling drupal_flush_all_caches().
  \Drupal::state()->set('cache_flush_test', \Drupal::hasService('language_negotiator'));
  // Create configuration the way a profile creates default content and
  // configuration. Its langcode comes from the language the site is being
  // installed in.
  \Drupal\Core\Datetime\Entity\DateFormat::create([
    'id' => 'cache_flush_test',
    'label' => 'Cache flush test',
    'pattern' => 'Y-m-d',
  ])->save();
}
EOF;

    file_put_contents("$path/cache_flush_test.install", $php_code);

    // Create a module which rebuilds the container in its hook_install().
    // Because the profile is small, all of its modules are installed in the
    // same install group as the system module.
    $module_path = "$path/modules/cache_flush_test_module";
    mkdir($module_path, 0777, TRUE);
    $module_info = [
      'type' => 'module',
      'core_version_requirement' => '*',
      'name' => 'Cache flush test module',
    ];
    file_put_contents("$module_path/cache_flush_test_module.info.yml", Yaml::encode($module_info));
    $module_php_code = <<<'EOF'
<?php
function cache_flush_test_module_install(): void {
  // Installing another module changes the module list, so this rebuilds
  // the container while the install group containing the system module is
  // still being processed.
  \Drupal::service('module_installer')->install(['dblog']);
}
EOF;
    file_put_contents("$module_path/cache_flush_test_module.install", $module_php_code);
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters(): array {
    $parameters = parent::installParameters();
    // Install Drupal in German so the test can detect when the language
    // selected in the installer gets lost in a container rebuild.
    $parameters['parameters']['langcode'] = 'de';
    // Create an empty po file so we don't attempt to download one from
    // localize.drupal.org.
    \Drupal::service('file_system')->mkdir($this->publicFilesDirectory . '/translations', NULL, TRUE);
    $contents = <<<PO
msgid ""
msgstr ""

PO;
    file_put_contents($this->publicFilesDirectory . '/translations/drupal-' . \Drupal::VERSION . '.de.po', $contents);
    return $parameters;
  }

  /**
   * Confirms that the installation succeeded in the selected language.
   */
  public function testInstalled(): void {
    $this->assertTrue(\Drupal::state()->get('cache_flush_test'));

    // Ensure the container rebuild in cache_flush_test_module_install()
    // actually happened.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('dblog'));

    // Configuration created by the profile's hook_install() is in the
    // language the site was installed in.
    $this->assertSame('de', DateFormat::load('cache_flush_test')->language()->getId());

    // The installed site's default language is the selected language.
    $this->assertSame('de', $this->config('system.site')->get('langcode'));
    $this->assertSame('de', $this->config('system.site')->get('default_langcode'));
  }

}
