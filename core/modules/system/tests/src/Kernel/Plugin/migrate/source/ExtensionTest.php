<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests legacy extension source plugin.
 *
 * @covers \Drupal\system\Plugin\migrate\source\Extension
 * @group migrate_drupal
 */
class ExtensionTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $test = [];

    $test[0]['source_data']['system'] = [
      [
        'filename' => 'sites/all/modules/i18n/i18nmenu/i18nmenu.module',
        'name' => 'i18nmenu',
        'type' => 'module',
        'owner' => '',
        'status' => '1',
        'throttle' => '0',
        'bootstrap' => '0',
        'schema_version' => '0',
        'weight' => '0',
        'info' => 'a:10:{s:4:"name";s:16:"Menu translation";s:11:"description";s:40:"Supports translatable custom menu items.";s:12:"dependencies";a:4:{i:0;s:4:"i18n";i:1;s:4:"menu";i:2;s:10:"i18nblocks";i:3;s:11:"i18nstrings";}s:7:"package";s:12:"Multilingual";s:4:"core";s:3:"6.x";s:7:"version";s:8:"6.x-1.10";s:7:"project";s:4:"i18n";s:9:"datestamp";s:10:"1318336004";s:10:"dependents";a:0:{}s:3:"php";s:5:"4.3.5";},',
      ],
      [
        'filename' => 'sites/all/modules/variable/variable.module ',
        'name' => 'variable',
        'type' => 'module',
        'owner' => '',
        'status' => '1',
        'throttle' => '0',
        'bootstrap' => '0',
        'schema_version' => '-1',
        'weight' => '0',
        'info' => 'a:9:{s:4:"name";s:12:"Variable API";s:11:"description";s:12:"Variable API";s:4:"core";s:3:"6.x";s:7:"version";s:14:"6.x-1.0-alpha1";s:7:"project";s:8:"variable";s:9:"datestamp";s:10:"1414059742";s:12:"dependencies";a:0:{}s:10:"dependents";a:0:{}s:3:"php";s:5:"4.3.5";}',
      ],
    ];

    $info = unserialize('a:9:{s:4:"name";s:12:"Variable API";s:11:"description";s:12:"Variable API";s:4:"core";s:3:"6.x";s:7:"version";s:14:"6.x-1.0-alpha1";s:7:"project";s:8:"variable";s:9:"datestamp";s:10:"1414059742";s:12:"dependencies";a:0:{}s:10:"dependents";a:0:{}s:3:"php";s:5:"4.3.5";}');
    $test[0]['expected_data'] = [
      [
        'filename' => 'sites/all/modules/variable/variable.module ',
        'name' => 'variable',
        'type' => 'module',
        'owner' => '',
        'status' => '1',
        'throttle' => '0',
        'bootstrap' => '0',
        'schema_version' => '-1',
        'weight' => '0',
        'info' => $info,
      ],
    ];

    $test[0]['expected_count'] = NULL;
    $test[0]['configuration'] = [
      'name' => 'variable',
    ];

    return $test;
  }

}
