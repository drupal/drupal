<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Block;

use Drupal\autowire_test\Plugin\Block\AutowireBlock;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\Exception\AutowiringFailedException;

/**
 * Tests that blocks can be autowired.
 */
#[Group('block')]
#[RunTestsInSeparateProcesses]
class AutowireBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'autowire_test'];

  /**
   * Tests blocks with autowiring are created successfully.
   */
  public function testAutowireBlock(): void {
    $block = \Drupal::service('plugin.manager.block')->createInstance('autowire');
    $this->assertInstanceOf(AutowireBlock::class, $block);
    $this->assertInstanceOf(LockBackendInterface::class, $block->getLock());
  }

  /**
   * Tests that autowire errors are handled correctly.
   */
  public function testAutowireError(): void {
    $this->expectException(AutowiringFailedException::class);
    $this->expectExceptionMessage('Cannot autowire service "Drupal\Core\Lock\LockBackendInterface": argument "$lock" of method "Drupal\autowire_test\Plugin\Block\AutowireErrorBlock::__construct()", you should configure its value explicitly.');

    \Drupal::service('plugin.manager.block')->createInstance('autowire_error');
  }

}
