<?php

namespace Drupal\Tests\Core\Site;

use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Site\Settings
 * @runTestsInSeparateProcesses
 * @group Site
 */
class SettingsTest extends UnitTestCase {

  /**
   * Simple settings array to test against.
   *
   * @var array
   */
  protected $config = [];

  /**
   * The class under test.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * @covers ::__construct
   */
  protected function setUp(): void {
    $this->config = [
      'one' => '1',
      'two' => '2',
      'hash_salt' => $this->randomMachineName(),
    ];
    $this->settings = new Settings($this->config);
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    // Test stored settings.
    $this->assertEquals($this->config['one'], Settings::get('one'), 'The correct setting was not returned.');
    $this->assertEquals($this->config['two'], Settings::get('two'), 'The correct setting was not returned.');

    // Test setting that isn't stored with default.
    $this->assertEquals('3', Settings::get('three', '3'), 'Default value for a setting not properly returned.');
    $this->assertNull(Settings::get('four'), 'Non-null value returned for a setting that should not exist.');
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll() {
    $this->assertEquals($this->config, Settings::getAll());
  }

  /**
   * @covers ::getInstance
   */
  public function testGetInstance() {
    $singleton = $this->settings->getInstance();
    $this->assertEquals($singleton, $this->settings);
  }

  /**
   * Tests Settings::getHashSalt().
   *
   * @covers ::getHashSalt
   */
  public function testGetHashSalt() {
    $this->assertSame($this->config['hash_salt'], $this->settings->getHashSalt());
  }

  /**
   * Tests Settings::getHashSalt() with no hash salt value.
   *
   * @covers ::getHashSalt
   *
   * @dataProvider providerTestGetHashSaltEmpty
   */
  public function testGetHashSaltEmpty(array $config) {
    // Re-create settings with no 'hash_salt' key.
    $settings = new Settings($config);
    $this->expectException(\RuntimeException::class);
    $settings->getHashSalt();
  }

  /**
   * Data provider for testGetHashSaltEmpty.
   *
   * @return array
   */
  public function providerTestGetHashSaltEmpty() {
    return [
      [[]],
      [['hash_salt' => '']],
      [['hash_salt' => NULL]],
    ];
  }

  /**
   * Ensures settings cannot be serialized.
   *
   * @covers ::__sleep
   */
  public function testSerialize() {
    $this->expectException(\LogicException::class);
    serialize(new Settings([]));
  }

  /**
   * Tests Settings::getApcuPrefix().
   *
   * @covers ::getApcuPrefix
   */
  public function testGetApcuPrefix() {
    $settings = new Settings([
      'hash_salt' => 123,
      'apcu_ensure_unique_prefix' => TRUE,
    ]);
    $this->assertNotEquals($settings::getApcuPrefix('cache_test', '/test/a'), $settings::getApcuPrefix('cache_test', '/test/b'));

    $settings = new Settings([
      'hash_salt' => 123,
      'apcu_ensure_unique_prefix' => FALSE,
    ]);
    $this->assertNotEquals($settings::getApcuPrefix('cache_test', '/test/a'), $settings::getApcuPrefix('cache_test', '/test/b'));
  }

  /**
   * Tests that an exception is thrown when settings are not initialized yet.
   *
   * @covers ::getInstance
   */
  public function testGetInstanceReflection() {
    $settings = new Settings([]);

    $class = new \ReflectionClass(Settings::class);
    $instance_property = $class->getProperty("instance");
    $instance_property->setAccessible(TRUE);
    $instance_property->setValue(NULL);

    $this->expectException(\BadMethodCallException::class);
    $settings->getInstance();
  }

  /**
   * Tests deprecation messages and values when using fake deprecated settings.
   *
   * Note: Tests for real deprecated settings should not be added to this test
   * or provider. This test is only for the general deprecated settings API
   * itself.
   *
   * @see self::testRealDeprecatedSettings()
   * @see self::providerTestRealDeprecatedSettings()
   *
   * @param string[] $settings_config
   *   Array of settings to put in the settings.php file for testing.
   * @param string $setting_name
   *   The name of the setting this case should use for Settings::get().
   * @param string $expected_value
   *   The expected value of the setting.
   * @param bool $expect_deprecation_message
   *   Should the case expect a deprecation message? Defaults to TRUE.
   *
   * @dataProvider providerTestFakeDeprecatedSettings
   *
   * @covers ::handleDeprecations
   * @covers ::initialize
   *
   * @group legacy
   */
  public function testFakeDeprecatedSettings(array $settings_config, string $setting_name, string $expected_value, bool $expect_deprecation_message = TRUE): void {

    $settings_file_content = "<?php\n";
    foreach ($settings_config as $name => $value) {
      $settings_file_content .= "\$settings['$name'] = '$value';\n";
    }
    $class_loader = NULL;
    $vfs_root = vfsStream::setup('root');
    $sites_directory = vfsStream::newDirectory('sites')->at($vfs_root);
    vfsStream::newFile('settings.php')
      ->at($sites_directory)
      ->setContent($settings_file_content);

    // This is the deprecated setting used by all cases for this test method.
    $deprecated_setting = [
      'replacement' => 'happy_replacement',
      'message' => 'The settings key "deprecated_legacy" is deprecated in drupal:9.1.0 and will be removed in drupal:10.0.0. Use "happy_replacement" instead. See https://www.drupal.org/node/3163226.',
    ];

    $class = new \ReflectionClass(Settings::class);
    $instance_property = $class->getProperty('deprecatedSettings');
    $instance_property->setAccessible(TRUE);
    $deprecated_settings = $instance_property->getValue();
    $deprecated_settings['deprecated_legacy'] = $deprecated_setting;
    $instance_property->setValue($deprecated_settings);

    if ($expect_deprecation_message) {
      $this->expectDeprecation($deprecated_setting['message']);
    }

    Settings::initialize(vfsStream::url('root'), 'sites', $class_loader);
    $this->assertEquals($expected_value, Settings::get($setting_name));
  }

  /**
   * Provides data for testFakeDeprecatedSettings().
   *
   * Note: Tests for real deprecated settings should not be added here.
   *
   * @see self::providerTestRealDeprecatedSettings()
   */
  public function providerTestFakeDeprecatedSettings(): array {

    $only_legacy = [
      'deprecated_legacy' => 'old',
    ];
    $only_replacement = [
      'happy_replacement' => 'new',
    ];
    $both_settings = [
      'deprecated_legacy' => 'old',
      'happy_replacement' => 'new',
    ];

    return [
      'Only legacy defined, get legacy' => [
        $only_legacy,
        'deprecated_legacy',
        'old',
      ],
      'Only legacy defined, get replacement' => [
        $only_legacy,
        'happy_replacement',
        // Since the new setting isn't yet defined, use the old value.
        'old',
        // Since the old setting is there, we should see a deprecation message.
      ],
      'Both legacy and replacement defined, get legacy' => [
        $both_settings,
        'deprecated_legacy',
        // Since the replacement is already defined, that should be used.
        'new',
      ],
      'Both legacy and replacement defined, get replacement' => [
        $both_settings,
        'happy_replacement',
        'new',
        // Should see the deprecation, since the legacy setting is defined.
      ],
      'Only replacement defined, get legacy' => [
        $only_replacement,
        'deprecated_legacy',
        // Should get the new value.
        'new',
        // But we should see a deprecation message for accessing the old name.
      ],
      'Only replacement defined, get replacement' => [
        $only_replacement,
        'happy_replacement',
        // Should get the new value.
        'new',
        // No deprecation since the old name is neither used nor defined.
        FALSE,
      ],
    ];
  }

  /**
   * Tests deprecation messages for real deprecated settings.
   *
   * @param string $legacy_setting
   *   The legacy name of the setting to test.
   * @param string $expected_deprecation
   *   The expected deprecation message.
   *
   * @dataProvider providerTestRealDeprecatedSettings
   * @group legacy
   */
  public function testRealDeprecatedSettings(string $legacy_setting, string $expected_deprecation): void {

    $settings_file_content = "<?php\n\$settings['$legacy_setting'] = 'foo';\n";
    $class_loader = NULL;
    $vfs_root = vfsStream::setup('root');
    $sites_directory = vfsStream::newDirectory('sites')->at($vfs_root);
    vfsStream::newFile('settings.php')
      ->at($sites_directory)
      ->setContent($settings_file_content);

    $this->expectDeprecation($expected_deprecation);

    // Presence of the old name in settings.php is enough to trigger messages.
    Settings::initialize(vfsStream::url('root'), 'sites', $class_loader);
  }

  /**
   * Provides data for testRealDeprecatedSettings().
   */
  public function providerTestRealDeprecatedSettings(): array {
    return [
      [
        'sanitize_input_whitelist',
        'The "sanitize_input_whitelist" setting is deprecated in drupal:9.1.0 and will be removed in drupal:10.0.0. Use Drupal\Core\Security\RequestSanitizer::SANITIZE_INPUT_SAFE_KEYS instead. See https://www.drupal.org/node/3163148.',
      ],
      [
        'twig_sandbox_whitelisted_classes',
        'The "twig_sandbox_whitelisted_classes" setting is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use "twig_sandbox_allowed_classes" instead. See https://www.drupal.org/node/3162897.',
      ],
      [
        'twig_sandbox_whitelisted_methods',
        'The "twig_sandbox_whitelisted_methods" setting is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use "twig_sandbox_allowed_methods" instead. See https://www.drupal.org/node/3162897.',
      ],
      [
        'twig_sandbox_whitelisted_prefixes',
        'The "twig_sandbox_whitelisted_prefixes" setting is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use "twig_sandbox_allowed_prefixes" instead. See https://www.drupal.org/node/3162897.',
      ],
    ];
  }

}
