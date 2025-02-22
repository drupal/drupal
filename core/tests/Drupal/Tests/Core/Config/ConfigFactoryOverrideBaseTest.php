<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigFactoryOverrideBase;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\ConfigFactoryOverrideBase
 * @group config
 */
class ConfigFactoryOverrideBaseTest extends UnitTestCase {

  /**
   * @dataProvider providerTestFilterNestedArray
   */
  public function testFilterNestedArray(array $original_data, array $override_data_before, array $override_data_after, $changed): void {
    $config_factory = new TestConfigFactoryOverrideBase();
    $result = $config_factory->doFilterNestedArray($original_data, $override_data_before);
    $this->assertEquals($changed, $result);
    $this->assertEquals($override_data_after, $override_data_before);
  }

  public static function providerTestFilterNestedArray() {
    $data = [];
    $data['empty'] = [
      [],
      [],
      [],
      FALSE,
    ];

    $data['one-level-no-change'] = [
      ['key' => 'value'],
      [],
      [],
      FALSE,
    ];

    $data['one-level-override-no-change'] = [
      ['key' => 'value'],
      ['key' => 'value2'],
      ['key' => 'value2'],
      FALSE,
    ];

    $data['one-level-override-change'] = [
      ['key' => 'value'],
      ['key2' => 'value2'],
      [],
      TRUE,
    ];

    $data['one-level-multiple-override-change'] = [
      ['key' => 'value', 'key2' => 'value2'],
      ['key2' => 'value2', 'key3' => 'value3'],
      ['key2' => 'value2'],
      TRUE,
    ];

    $data['multiple-level-multiple-override-change'] = [
      ['key' => ['key' => 'value'], 'key2' => ['key' => 'value']],
      ['key' => ['key2' => 'value2'], 'key2' => ['key' => 'value']],
      ['key2' => ['key' => 'value']],
      TRUE,
    ];

    $data['original-scalar-array-override'] = [
      ['key' => 'value'],
      ['key' => ['value1', 'value2']],
      [],
      TRUE,
    ];

    return $data;
  }

}

/**
 * Stub class for testing ConfigFactoryOverrideBase.
 */
class TestConfigFactoryOverrideBase extends ConfigFactoryOverrideBase {

  public function doFilterNestedArray(array $original_data, array &$override_data) {
    return $this->filterNestedArray($original_data, $override_data);
  }

  public function addCollections(ConfigCollectionInfo $collection_info) {
  }

  public function onConfigSave(ConfigCrudEvent $event) {
  }

  public function onConfigDelete(ConfigCrudEvent $event) {
  }

  public function onConfigRename(ConfigRenameEvent $event) {
  }

}
