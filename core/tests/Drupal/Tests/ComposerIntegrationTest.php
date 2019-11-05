<?php

namespace Drupal\Tests;

use Drupal\Tests\Composer\ComposerIntegrationTrait;

/**
 * Tests Composer integration.
 *
 * @group Composer
 */
class ComposerIntegrationTest extends UnitTestCase {

  use ComposerIntegrationTrait;

  /**
   * Tests composer.lock content-hash.
   */
  public function testComposerLockHash() {
    $content_hash = self::getContentHash(file_get_contents($this->root . '/composer.json'));
    $lock = json_decode(file_get_contents($this->root . '/composer.lock'), TRUE);
    $this->assertSame($content_hash, $lock['content-hash']);
  }

  /**
   * Tests composer.json versions.
   *
   * @param string $path
   *   Path to a composer.json to test.
   *
   * @dataProvider providerTestComposerJson
   */
  public function testComposerTilde($path) {
    $content = json_decode(file_get_contents($path), TRUE);
    $composer_keys = array_intersect(['require', 'require-dev'], array_keys($content));
    if (empty($composer_keys)) {
      $this->markTestSkipped("$path has no keys to test");
    }
    foreach ($composer_keys as $composer_key) {
      foreach ($content[$composer_key] as $dependency => $version) {
        // We allow tildes if the dependency is a Symfony component.
        // @see https://www.drupal.org/node/2887000
        if (strpos($dependency, 'symfony/') === 0) {
          continue;
        }
        $this->assertFalse(strpos($version, '~'), "Dependency $dependency in $path contains a tilde, use a caret.");
      }
    }
  }

  /**
   * Data provider for all the composer.json provided by Drupal core.
   *
   * @return array
   */
  public function providerTestComposerJson() {
    $data = [];
    $composer_json_finder = $this->getComposerJsonFinder(realpath(__DIR__ . '/../../../../'));
    foreach ($composer_json_finder->getIterator() as $composer_json) {
      $data[] = [$composer_json->getPathname()];
    }
    return $data;
  }

  /**
   * Tests core's composer.json replace section.
   *
   * Verify that all core modules are also listed in the 'replace' section of
   * core's composer.json.
   */
  public function testAllModulesReplaced() {
    // Assemble a path to core modules.
    $module_path = $this->root . '/core/modules';

    // Grab the 'replace' section of the core composer.json file.
    $json = json_decode(file_get_contents($this->root . '/core/composer.json'));
    $composer_replace_packages = (array) $json->replace;

    // Get a list of all the files in the module path.
    $folders = scandir($module_path);

    // Make sure we only deal with directories that aren't . or ..
    $module_names = [];
    $discard = ['.', '..'];
    foreach ($folders as $file_name) {
      if ((!in_array($file_name, $discard)) && is_dir($module_path . '/' . $file_name)) {
        $module_names[] = $file_name;
      }
    }

    // Assert that each core module has a corresponding 'replace' in
    // composer.json.
    foreach ($module_names as $module_name) {
      $this->assertArrayHasKey(
        'drupal/' . $module_name,
        $composer_replace_packages,
        'Unable to find ' . $module_name . ' in replace list of composer.json'
      );
    }
  }

  /**
   * Data provider for the scaffold files test for Drupal core.
   *
   * @return array
   */
  public function providerTestExpectedScaffoldFiles() {
    return [
      ['.editorconfig', 'assets/scaffold/files/editorconfig', '[project-root]'],
      ['.gitattributes', 'assets/scaffold/files/gitattributes', '[project-root]'],
      ['.csslintrc', 'assets/scaffold/files/csslintrc'],
      ['.eslintignore', 'assets/scaffold/files/eslintignore'],
      ['.eslintrc.json', 'assets/scaffold/files/eslintrc.json'],
      ['.ht.router.php', 'assets/scaffold/files/ht.router.php'],
      ['.htaccess', 'assets/scaffold/files/htaccess'],
      ['example.gitignore', 'assets/scaffold/files/example.gitignore'],
      ['index.php', 'assets/scaffold/files/index.php'],
      ['INSTALL.txt', 'assets/scaffold/files/drupal.INSTALL.txt'],
      ['README.txt', 'assets/scaffold/files/drupal.README.txt'],
      ['robots.txt', 'assets/scaffold/files/robots.txt'],
      ['update.php', 'assets/scaffold/files/update.php'],
      ['web.config', 'assets/scaffold/files/web.config'],
      ['sites/README.txt', 'assets/scaffold/files/sites.README.txt'],
      ['sites/development.services.yml', 'assets/scaffold/files/development.services.yml'],
      ['sites/example.settings.local.php', 'assets/scaffold/files/example.settings.local.php'],
      ['sites/example.sites.php', 'assets/scaffold/files/example.sites.php'],
      ['sites/default/default.services.yml', 'assets/scaffold/files/default.services.yml'],
      ['sites/default/default.settings.php', 'assets/scaffold/files/default.settings.php'],
      ['modules/README.txt', 'assets/scaffold/files/modules.README.txt'],
      ['profiles/README.txt', 'assets/scaffold/files/profiles.README.txt'],
      ['themes/README.txt', 'assets/scaffold/files/themes.README.txt'],
    ];
  }

  /**
   * Tests core's composer.json extra drupal-scaffold file-mappings section.
   *
   * Verify that every file listed in file-mappings exists in its destination
   * path (mapping key) and also at its source path (mapping value), and that
   * both of these files have exactly the same content.
   *
   * In Drupal 9, the files at the destination path will be removed. For the
   * remainder of the Drupal 8 development cycle, these files will remain in
   * order to maintain backwards compatibility with sites based on the template
   * project drupal-composer/drupal-project.
   *
   * See https://www.drupal.org/project/drupal/issues/3075954
   *
   * @param string $destRelPath
   *   Path to scaffold file destination location
   * @param string $sourceRelPath
   *   Path to scaffold file source location
   * @param string $expectedDestination
   *   Named location to the destination path of the scaffold file
   *
   * @dataProvider providerTestExpectedScaffoldFiles
   */
  public function testExpectedScaffoldFiles($destRelPath, $sourceRelPath, $expectedDestination = '[web-root]') {
    // Grab the 'file-mapping' section of the core composer.json file.
    $json = json_decode(file_get_contents($this->root . '/core/composer.json'));
    $scaffold_file_mapping = (array) $json->extra->{'drupal-scaffold'}->{'file-mapping'};

    // Assert that the 'file-mapping' section has the expected entry.
    $this->assertArrayHasKey("$expectedDestination/$destRelPath", $scaffold_file_mapping);
    $this->assertEquals($sourceRelPath, $scaffold_file_mapping["$expectedDestination/$destRelPath"]);

    // Assert that the source file exists.
    $this->assertFileExists($this->root . '/core/' . $sourceRelPath);

    // Assert that the destination file exists and has the same contents as
    // the source file. Note that in Drupal 9, the destination file will be
    // removed.
    $this->assertFileExists($this->root . '/' . $destRelPath);
    $this->assertFileEquals($this->root . '/core/' . $sourceRelPath, $this->root . '/' . $destRelPath, 'Scaffold source and destination files must have the same contents.');
  }

  // @codingStandardsIgnoreStart
  /**
   * The following method is copied from \Composer\Package\Locker.
   *
   * @see https://github.com/composer/composer
   */
  /**
   * Returns the md5 hash of the sorted content of the composer file.
   *
   * @param string $composerFileContents The contents of the composer file.
   *
   * @return string
   */
  protected static function getContentHash($composerFileContents)
  {
    $content = json_decode($composerFileContents, true);

    $relevantKeys = array(
      'name',
      'version',
      'require',
      'require-dev',
      'conflict',
      'replace',
      'provide',
      'minimum-stability',
      'prefer-stable',
      'repositories',
      'extra',
    );

    $relevantContent = array();

    foreach (array_intersect($relevantKeys, array_keys($content)) as $key) {
      $relevantContent[$key] = $content[$key];
    }
    if (isset($content['config']['platform'])) {
      $relevantContent['config']['platform'] = $content['config']['platform'];
    }

    ksort($relevantContent);

    return md5(json_encode($relevantContent));
  }
  // @codingStandardsIgnoreEnd

}
