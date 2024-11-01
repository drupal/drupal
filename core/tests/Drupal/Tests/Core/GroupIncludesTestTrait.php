<?php

declare(strict_types=1);

namespace Drupal\Tests\Core;

use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Hook\HookCollectorPass
 * @group Hook
 */
trait GroupIncludesTestTrait {

  const GROUP_INCLUDES = ['token_info' => ['vfs://drupal_root/test_module.tokens.inc']];

  /**
   * @return array[]
   */
  public static function setupGroupIncludes(): array {
    vfsStream::setup('drupal_root');
    file_put_contents('vfs://drupal_root/test_module_info.yml', '');
    $module_filenames = [
      'test_module' => ['pathname' => 'vfs://drupal_root/test_module_info.yml'],
    ];
    file_put_contents('vfs://drupal_root/test_module.module', <<<'EOD'
<?php

function test_module_hook_info() {
  $hooks['token_info'] = [
    'group' => 'tokens',
  ];
  return $hooks;
}

EOD
    );
    file_put_contents('vfs://drupal_root/test_module.tokens.inc', <<<'EOD'
<?php

function _test_module_helper() {}

EOD
    );
    return $module_filenames;
  }

}
