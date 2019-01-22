<?php

namespace Drupal\Tests;

/**
 * Tests Composer integration.
 *
 * @group Composer
 */
class ComposerIntegrationTest extends UnitTestCase {

  /**
   * Gets human-readable JSON error messages.
   *
   * @return string[]
   *   Keys are JSON_ERROR_* constants.
   */
  protected function getErrorMessages() {
    $messages = [
      0 => 'No errors found',
      JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
      JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
      JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
      JSON_ERROR_SYNTAX => 'Syntax error',
      JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
    ];

    if (version_compare(phpversion(), '5.5.0', '>=')) {
      $messages[JSON_ERROR_RECURSION] = 'One or more recursive references in the value to be encoded';
      $messages[JSON_ERROR_INF_OR_NAN] = 'One or more NAN or INF values in the value to be encoded';
      $messages[JSON_ERROR_UNSUPPORTED_TYPE] = 'A value of a type that cannot be encoded was given';
    }

    return $messages;
  }

  /**
   * Gets the paths to the folders that contain the Composer integration.
   *
   * @return string[]
   *   The paths.
   */
  protected function getPaths() {
    return [
      $this->root,
      $this->root . '/core',
      $this->root . '/core/lib/Drupal/Component/Annotation',
      $this->root . '/core/lib/Drupal/Component/Assertion',
      $this->root . '/core/lib/Drupal/Component/Bridge',
      $this->root . '/core/lib/Drupal/Component/ClassFinder',
      $this->root . '/core/lib/Drupal/Component/Datetime',
      $this->root . '/core/lib/Drupal/Component/DependencyInjection',
      $this->root . '/core/lib/Drupal/Component/Diff',
      $this->root . '/core/lib/Drupal/Component/Discovery',
      $this->root . '/core/lib/Drupal/Component/EventDispatcher',
      $this->root . '/core/lib/Drupal/Component/FileCache',
      $this->root . '/core/lib/Drupal/Component/FileSystem',
      $this->root . '/core/lib/Drupal/Component/Gettext',
      $this->root . '/core/lib/Drupal/Component/Graph',
      $this->root . '/core/lib/Drupal/Component/HttpFoundation',
      $this->root . '/core/lib/Drupal/Component/PhpStorage',
      $this->root . '/core/lib/Drupal/Component/Plugin',
      $this->root . '/core/lib/Drupal/Component/ProxyBuilder',
      $this->root . '/core/lib/Drupal/Component/Render',
      $this->root . '/core/lib/Drupal/Component/Serialization',
      $this->root . '/core/lib/Drupal/Component/Transliteration',
      $this->root . '/core/lib/Drupal/Component/Utility',
      $this->root . '/core/lib/Drupal/Component/Uuid',
      $this->root . '/core/lib/Drupal/Component/Version',
    ];
  }

  /**
   * Tests composer.json.
   */
  public function testComposerJson() {
    foreach ($this->getPaths() as $path) {
      $json = file_get_contents($path . '/composer.json');
      $result = json_decode($json);
      $this->assertNotNull($result, $this->getErrorMessages()[json_last_error()]);
    }
  }

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
    $root = realpath(__DIR__ . '/../../../../');
    $tests = [[$root . '/composer.json']];
    $directory = new \RecursiveDirectoryIterator($root . '/core');
    $iterator = new \RecursiveIteratorIterator($directory);
    /** @var \SplFileInfo $file */
    foreach ($iterator as $file) {
      if ($file->getFilename() === 'composer.json' && strpos($file->getPath(), 'core/modules/system/tests/fixtures/HtaccessTest') === FALSE) {
        $tests[] = [$file->getRealPath()];
      }
    }
    return $tests;
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
