<?php

namespace Drupal\Tests\Core\Config;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\Markup;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigValueException;

/**
 * Tests the Config.
 *
 * @coversDefaultClass \Drupal\Core\Config\Config
 *
 * @group Config
 *
 * @see \Drupal\Core\Config\Config
 */
class ConfigTest extends UnitTestCase {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Storage.
   *
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * Event Dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * Typed Config.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedConfig;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->storage = $this->createMock('Drupal\Core\Config\StorageInterface');
    $this->eventDispatcher = $this->createMock('Symfony\Contracts\EventDispatcher\EventDispatcherInterface');
    $this->typedConfig = $this->createMock('\Drupal\Core\Config\TypedConfigManagerInterface');
    $this->config = new Config('config.test', $this->storage, $this->eventDispatcher, $this->typedConfig);
    $this->cacheTagsInvalidator = $this->createMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $container = new ContainerBuilder();
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::setName
   * @dataProvider setNameProvider
   */
  public function testSetName($name) {
    // Set the name.
    $this->config->setName($name);

    // Check that the name has been set correctly.
    $this->assertEquals($name, $this->config->getName());

    // Check that the name validates.
    // Should throw \Drupal\Core\Config\ConfigNameException if invalid.
    $this->config->validateName($name);
  }

  /**
   * Provides config names to test.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSetName()
   */
  public function setNameProvider() {
    return [
      // Valid name with dot.
      [
        'test.name',
      ],
      // Maximum length.
      [
        'test.' . str_repeat('a', Config::MAX_NAME_LENGTH - 5),
      ],
    ];
  }

  /**
   * @covers ::isNew
   */
  public function testIsNew() {
    // Config should be new by default.
    $this->assertTrue($this->config->isNew());

    // Config is no longer new once saved.
    $this->config->save();
    $this->assertFalse($this->config->isNew());
  }

  /**
   * @covers ::setData
   * @dataProvider nestedDataProvider
   */
  public function testSetData($data) {
    $this->config->setData($data);
    $this->assertEquals($data, $this->config->getRawData());
    $this->assertConfigDataEquals($data);
  }

  /**
   * @covers ::save
   * @dataProvider nestedDataProvider
   */
  public function testSaveNew($data) {
    $this->cacheTagsInvalidator->expects($this->never())
      ->method('invalidateTags');

    // Set initial data.
    $this->config->setData($data);

    // Check that original data has not been set yet.
    foreach ($data as $key => $value) {
      $this->assertNull($this->config->getOriginal($key, FALSE));
    }

    // Save so that the original data is set.
    $config = $this->config->save();

    // Check that returned $config is instance of Config.
    $this->assertInstanceOf('\Drupal\Core\Config\Config', $config);

    // Check that the original data it saved.
    $this->assertOriginalConfigDataEquals($data, TRUE);
  }

  /**
   * @covers ::save
   * @dataProvider nestedDataProvider
   */
  public function testSaveExisting($data) {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['config:config.test']);

    // Set initial data.
    $this->config->setData($data);
    $this->config->save();

    // Update.
    $new_data = $data;
    $new_data['a']['d'] = 2;
    $this->config->setData($new_data);
    $this->config->save();
    $this->assertOriginalConfigDataEquals($new_data, TRUE);
  }

  /**
   * @covers ::setModuleOverride
   * @covers ::setSettingsOverride
   * @covers ::getOriginal
   * @covers ::hasOverrides
   * @dataProvider overrideDataProvider
   */
  public function testOverrideData($data, $module_data, $setting_data) {
    // Set initial data.
    $this->config->setData($data);

    // Check original data was set correctly.
    $this->assertConfigDataEquals($data);

    // Save so that the original data is stored.
    $this->config->save();
    $this->assertFalse($this->config->hasOverrides());
    $this->assertOverriddenKeys($data, []);

    // Set module override data and check value before and after save.
    $this->config->setModuleOverride($module_data);
    $this->assertConfigDataEquals($module_data);
    $this->assertOverriddenKeys($data, $module_data);

    $this->config->save();
    $this->assertConfigDataEquals($module_data);
    $this->assertOverriddenKeys($data, $module_data);

    // Reset the module overrides.
    $this->config->setModuleOverride([]);
    $this->assertOverriddenKeys($data, []);

    // Set setting override data and check value before and after save.
    $this->config->setSettingsOverride($setting_data);
    $this->assertConfigDataEquals($setting_data);
    $this->assertOverriddenKeys($data, $setting_data);
    $this->config->save();
    $this->assertConfigDataEquals($setting_data);
    $this->assertOverriddenKeys($data, $setting_data);

    // Set module overrides again to ensure override order is correct.
    $this->config->setModuleOverride($module_data);
    $merged_overrides = array_merge($module_data, $setting_data);

    // Setting data should be overriding module data.
    $this->assertConfigDataEquals($setting_data);
    $this->assertOverriddenKeys($data, $merged_overrides);
    $this->config->save();
    $this->assertConfigDataEquals($setting_data);
    $this->assertOverriddenKeys($data, $merged_overrides);

    // Check original data has not changed.
    $this->assertOriginalConfigDataEquals($data, FALSE);

    // Check setting overrides are returned with $apply_overrides = TRUE.
    $this->assertOriginalConfigDataEquals($setting_data, TRUE);

    // Check that $apply_overrides defaults to TRUE.
    foreach ($setting_data as $key => $value) {
      $config_value = $this->config->getOriginal($key);
      $this->assertEquals($value, $config_value);
    }

    // Check that the overrides can be completely reset.
    $this->config->setModuleOverride([]);
    $this->config->setSettingsOverride([]);
    $this->assertConfigDataEquals($data);
    $this->assertOverriddenKeys($data, []);
    $this->config->save();
    $this->assertConfigDataEquals($data);
    $this->assertOverriddenKeys($data, []);
  }

  /**
   * @covers ::set
   * @dataProvider nestedDataProvider
   */
  public function testSetValue($data) {
    foreach ($data as $key => $value) {
      $this->config->set($key, $value);
    }
    $this->assertConfigDataEquals($data);
  }

  /**
   * @covers ::set
   */
  public function testSetValidation() {
    $this->expectException(ConfigValueException::class);
    $this->config->set('testData', ['dot.key' => 1]);
  }

  /**
   * @covers ::set
   */
  public function testSetIllegalOffsetValue() {
    // Set a single value.
    $this->config->set('testData', 1);

    // Attempt to treat the single value as a nested item.
    $this->expectError();
    $this->config->set('testData.illegalOffset', 1);
  }

  /**
   * @covers ::initWithData
   * @dataProvider nestedDataProvider
   */
  public function testInitWithData($data) {
    $config = $this->config->initWithData($data);

    // Should return the Config object.
    $this->assertInstanceOf('\Drupal\Core\Config\Config', $config);

    // Check config is not new.
    $this->assertEquals(FALSE, $this->config->isNew());

    // Check that data value was set correctly.
    $this->assertConfigDataEquals($data);

    // Check that original data was set.
    $this->assertOriginalConfigDataEquals($data, TRUE);

    // Check without applying overrides.
    $this->assertOriginalConfigDataEquals($data, FALSE);
  }

  /**
   * @covers ::clear
   * @dataProvider simpleDataProvider
   */
  public function testClear($data) {
    foreach ($data as $key => $value) {
      // Check that values are cleared.
      $this->config->set($key, $value);
      $this->assertEquals($value, $this->config->get($key));
      $this->config->clear($key);
      $this->assertNull($this->config->get($key));
    }
  }

  /**
   * @covers ::clear
   * @dataProvider nestedDataProvider
   */
  public function testNestedClear($data) {
    foreach ($data as $key => $value) {
      // Check that values are cleared.
      $this->config->set($key, $value);
      // Check each nested value.
      foreach ($value as $nested_key => $nested_value) {
        $full_nested_key = $key . '.' . $nested_key;
        $this->assertEquals($nested_value, $this->config->get($full_nested_key));
        $this->config->clear($full_nested_key);
        $this->assertNull($this->config->get($full_nested_key));
      }
    }
  }

  /**
   * @covers ::delete
   * @dataProvider overrideDataProvider
   */
  public function testDelete($data, $module_data) {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['config:config.test']);

    // Set initial data.
    foreach ($data as $key => $value) {
      $this->config->set($key, $value);
    }
    // Set overrides.
    $this->config->setModuleOverride($module_data);

    // Save.
    $this->config->save();

    // Check that original data is still correct.
    $this->assertOriginalConfigDataEquals($data, FALSE);

    // Check overrides have been set.
    $this->assertConfigDataEquals($module_data);
    $this->assertOriginalConfigDataEquals($module_data, TRUE);

    // Check that config is new.
    $this->assertFalse($this->config->isNew());

    // Delete.
    $this->config->delete();

    // Check object properties have been reset.
    $this->assertTrue($this->config->isNew());
    foreach ($data as $key => $value) {
      $this->assertEmpty($this->config->getOriginal($key, FALSE));
    }

    // Check that overrides have persisted.
    foreach ($module_data as $key => $value) {
      $this->assertConfigDataEquals($module_data);
      $this->assertOriginalConfigDataEquals($module_data, TRUE);
    }
  }

  /**
   * @covers ::merge
   * @dataProvider mergeDataProvider
   */
  public function testMerge($data, $data_to_merge, $merged_data) {
    // Set initial data.
    $this->config->setData($data);

    // Data to merge.
    $this->config->merge($data_to_merge);

    // Check that data has merged correctly.
    $this->assertEquals($merged_data, $this->config->getRawData());
  }

  /**
   * Provides data to test merges.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testMerge()
   */
  public function mergeDataProvider() {
    return [
      [
        // Data.
        ['a' => 1, 'b' => 2, 'c' => ['d' => 3]],
        // Data to merge.
        ['a' => 2, 'e' => 4, 'c' => ['f' => 5]],
        // Data merged.
        ['a' => 2, 'b' => 2, 'c' => ['d' => 3, 'f' => 5], 'e' => 4],
      ],
    ];
  }

  /**
   * @covers ::validateName
   * @dataProvider validateNameProvider
   */
  public function testValidateNameException($name, $exception_message) {
    $this->expectException('\Drupal\Core\Config\ConfigNameException');
    $this->expectExceptionMessage($exception_message);
    $this->config->validateName($name);
  }

  /**
   * @covers ::getCacheTags
   */
  public function testGetCacheTags() {
    $this->assertSame(['config:' . $this->config->getName()], $this->config->getCacheTags());
  }

  /**
   * Provides data to test name validation.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testValidateNameException()
   */
  public function validateNameProvider() {
    $return = [
      // Name missing namespace (dot).
      [
        'MissingNamespace',
        'Missing namespace in Config object name MissingNamespace.',
      ],
      // Exceeds length (max length plus an extra dot).
      [
        str_repeat('a', Config::MAX_NAME_LENGTH) . ".",
        'Config object name ' . str_repeat('a', Config::MAX_NAME_LENGTH) . '. exceeds maximum allowed length of ' . Config::MAX_NAME_LENGTH . ' characters.',
      ],
    ];
    // Name must not contain : ? * < > " ' / \
    foreach ([':', '?', '*', '<', '>', '"', "'", '/', '\\'] as $char) {
      $name = 'name.' . $char;
      $return[] = [
        $name,
        "Invalid character in Config object name $name.",
      ];
    }
    return $return;
  }

  /**
   * Provides override data.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testOverrideData()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testDelete()
   */
  public function overrideDataProvider() {
    $test_cases = [
      [
        // Original data.
        [
          'a' => 'originalValue',
        ],
        // Module overrides.
        [
          'a' => 'moduleValue',
        ],
        // Setting overrides.
        [
          'a' => 'settingValue',
        ],
      ],
      [
        // Original data.
        [
          'a' => 'originalValue',
          'b' => 'originalValue',
          'c' => 'originalValue',
        ],
        // Module overrides.
        [
          'a' => 'moduleValue',
          'b' => 'moduleValue',
        ],
        // Setting overrides.
        [
          'a' => 'settingValue',
        ],
      ],
      [
        // Original data.
        [
          'a' => 'allTheSameValue',
        ],
        // Module overrides.
        [
          'a' => 'allTheSameValue',
        ],
        // Setting overrides.
        [
          'a' => 'allTheSameValue',
        ],
      ],
    ];
    // For each of the above test cases create duplicate test case except with
    // config values nested.
    foreach ($test_cases as $test_key => $test_case) {
      foreach ($test_case as $parameter) {
        $nested_parameter = [];
        foreach ($parameter as $config_key => $value) {
          // Nest config value 5 levels.
          $nested_value = $value;
          for ($i = 5; $i >= 0; $i--) {
            $nested_value = [
              $i => $nested_value,
            ];
          }
          $nested_parameter[$config_key] = $nested_value;
        }
        $test_cases["nested:$test_key"][] = $nested_parameter;
      }
    }
    return $test_cases;
  }

  /**
   * Provides simple test data.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testClear()
   */
  public function simpleDataProvider() {
    return [
      [
        [
          'a' => '1',
          'b' => '2',
          'c' => '3',
        ],
      ],
    ];
  }

  /**
   * Provides nested test data.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSetData()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSave()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSetValue()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testInitWithData()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testNestedClear()
   */
  public function nestedDataProvider() {
    return [
      [
        [
          'a' => [
            'd' => 1,
          ],
          'b' => [
            'e' => 2,
          ],
          'c' => [
            'f' => 3,
          ],
        ],
      ],
    ];
  }

  /**
   * Asserts all config data equals $data provided.
   *
   * @param array $data
   *   Config data to be checked.
   *
   * @internal
   */
  public function assertConfigDataEquals(array $data): void {
    foreach ($data as $key => $value) {
      $this->assertEquals($value, $this->config->get($key));
    }
  }

  /**
   * Asserts all original config data equals $data provided.
   *
   * @param array $data
   *   Config data to be checked.
   * @param bool $apply_overrides
   *   Apply any overrides to the original data.
   *
   * @internal
   */
  public function assertOriginalConfigDataEquals(array $data, bool $apply_overrides): void {
    foreach ($data as $key => $value) {
      $config_value = $this->config->getOriginal($key, $apply_overrides);
      $this->assertEquals($value, $config_value);
    }
  }

  /**
   * @covers ::setData
   * @covers ::set
   * @covers ::initWithData
   */
  public function testSafeStringHandling() {
    // Safe strings are cast when using ::set().
    $safe_string = Markup::create('bar');
    $this->config->set('foo', $safe_string);
    $this->assertSame('bar', $this->config->get('foo'));
    $this->config->set('foo', ['bar' => $safe_string]);
    $this->assertSame('bar', $this->config->get('foo.bar'));

    // Safe strings are cast when using ::setData().
    $this->config->setData(['bar' => $safe_string]);
    $this->assertSame('bar', $this->config->get('bar'));

    // Safe strings are not cast when using ::initWithData().
    $this->config->initWithData(['bar' => $safe_string]);
    $this->assertSame($safe_string, $this->config->get('bar'));
  }

  /**
   * Asserts that the correct keys are overridden.
   *
   * @param array $data
   *   The original data.
   * @param array $overridden_data
   *   The overridden data.
   *
   * @internal
   */
  protected function assertOverriddenKeys(array $data, array $overridden_data): void {
    if (empty($overridden_data)) {
      $this->assertFalse($this->config->hasOverrides());
    }
    else {
      $this->assertTrue($this->config->hasOverrides());
      foreach ($overridden_data as $key => $value) {
        // If there are nested overrides test a keys at every level.
        if (is_array($value)) {
          $nested_key = $key;
          $nested_value = $overridden_data[$key];
          while (is_array($nested_value)) {
            $nested_key .= '.' . key($nested_value);
            $this->assertTrue($this->config->hasOverrides($nested_key));
            $nested_value = array_pop($nested_value);
          }
        }
        $this->assertTrue($this->config->hasOverrides($key));
      }
    }

    $non_overridden_keys = array_diff(array_keys($data), array_keys($overridden_data));
    foreach ($non_overridden_keys as $non_overridden_key) {
      $this->assertFalse($this->config->hasOverrides($non_overridden_key));
      // If there are nested overrides test keys at every level.
      if (is_array($data[$non_overridden_key])) {
        $nested_key = $non_overridden_key;
        $nested_value = $data[$non_overridden_key];
        while (is_array($nested_value)) {
          $nested_key .= '.' . key($nested_value);
          $this->assertFalse($this->config->hasOverrides($nested_key));
          $nested_value = array_pop($nested_value);
        }
      }
    }
  }

}
