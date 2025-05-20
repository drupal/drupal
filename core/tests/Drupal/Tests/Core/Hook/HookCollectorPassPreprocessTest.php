<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Hook;

use Drupal\Core\Hook\HookCollectorPass;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversDefaultClass \Drupal\Core\Hook\HookCollectorPass
 * @group Hook
 */
class HookCollectorPassPreprocessTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    restore_error_handler();
    parent::tearDown();
  }

  /**
   * @covers ::process
   */
  public function testPreprocess(): void {
    set_error_handler(static function (int $errno, string $errstr): never {
      throw new \Exception($errstr, $errno);
    }, E_USER_WARNING);
    $this->expectExceptionMessage('To support Drupal 11.1 the hook hook_collector_preprocess_no_function_preprocess_test requires the procedural LegacyHook implementation.');
    $module_filenames = [
      'hook_collector_preprocess_no_function' => ['pathname' => 'core/modules/system/tests/modules/hook_collector_preprocess_no_function/hook_collector_preprocess_no_function.info.yml'],
    ];
    $container = new ContainerBuilder();
    $container->setParameter('container.modules', $module_filenames);
    $container->setDefinition('module_handler', new Definition());
    (new HookCollectorPass())->process($container);

  }

}
