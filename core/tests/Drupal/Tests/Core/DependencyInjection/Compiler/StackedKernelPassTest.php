<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\DependencyInjection\Compiler\StackedKernelPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StackMiddleware\StackedHttpKernel;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Compiler\StackedKernelPass
 * @group DependencyInjection
 */
class StackedKernelPassTest extends UnitTestCase {

  /**
   * The stacked kernel pass.
   *
   * @var \Drupal\Core\DependencyInjection\Compiler\StackedKernelPass
   */
  protected $stackedKernelPass;

  /**
   * @var \Drupal\Core\DependencyInjection\Container
   */
  protected $containerBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->stackedKernelPass = new StackedKernelPass();
    $this->containerBuilder = new ContainerBuilder();
  }

  /**
   * @covers ::process
   */
  public function testProcessWithStackedKernel() {
    $stacked_kernel = new Definition(StackedHttpKernel::class);
    $stacked_kernel->setPublic(TRUE);
    $this->containerBuilder->setDefinition('http_kernel', $stacked_kernel);
    $this->containerBuilder->setDefinition('http_kernel.basic', $this->createMiddlewareServiceDefinition(FALSE, 0));

    $this->containerBuilder->setDefinition('http_kernel.three', $this->createMiddlewareServiceDefinition());
    $this->containerBuilder->setDefinition('http_kernel.one', $this->createMiddlewareServiceDefinition(TRUE, 10));
    $this->containerBuilder->setDefinition('http_kernel.two', $this->createMiddlewareServiceDefinition(TRUE, 5));

    $this->stackedKernelPass->process($this->containerBuilder);

    $stacked_kernel_args = $this->containerBuilder->getDefinition('http_kernel')->getArguments();

    // Check the stacked kernel args.
    $this->assertSame('http_kernel.one', (string) $stacked_kernel_args[0]);
    $this->assertCount(4, $stacked_kernel_args[1]);
    $this->assertSame('http_kernel.one', (string) $stacked_kernel_args[1][0]);
    $this->assertSame('http_kernel.two', (string) $stacked_kernel_args[1][1]);
    $this->assertSame('http_kernel.three', (string) $stacked_kernel_args[1][2]);
    $this->assertSame('http_kernel.basic', (string) $stacked_kernel_args[1][3]);

    // Check the modified definitions.
    $definition = $this->containerBuilder->getDefinition('http_kernel.one');
    $args = $definition->getArguments();
    $this->assertSame('http_kernel.two', (string) $args[0]);
    $this->assertSame('test', $args[1]);

    $definition = $this->containerBuilder->getDefinition('http_kernel.two');
    $args = $definition->getArguments();
    $this->assertSame('http_kernel.three', (string) $args[0]);
    $this->assertSame('test', $args[1]);

    $definition = $this->containerBuilder->getDefinition('http_kernel.three');
    $args = $definition->getArguments();
    $this->assertSame('http_kernel.basic', (string) $args[0]);
    $this->assertSame('test', $args[1]);
  }

  /**
   * @covers ::process
   */
  public function testProcessWithHttpKernel() {
    $kernel = new Definition('Symfony\Component\HttpKernel\HttpKernelInterface');
    $kernel->setPublic(TRUE);
    $this->containerBuilder->setDefinition('http_kernel', $kernel);
    $this->stackedKernelPass->process($this->containerBuilder);

    $unprocessed_kernel = $this->containerBuilder->getDefinition('http_kernel');

    $this->assertSame($kernel, $unprocessed_kernel);
    $this->assertSame($kernel->getArguments(), $unprocessed_kernel->getArguments());
  }

  /**
   * Creates a middleware definition.
   *
   * @param bool $tag
   *   Whether or not to set the http_middleware tag.
   * @param int $priority
   *   The priority to be used for the tag.
   *
   * @return \Symfony\Component\DependencyInjection\Definition
   */
  protected function createMiddlewareServiceDefinition($tag = TRUE, $priority = 0) {
    $definition = new Definition('Symfony\Component\HttpKernel\HttpKernelInterface', ['test']);
    $definition->setPublic(TRUE);

    if ($tag) {
      $definition->addTag('http_middleware', ['priority' => $priority]);
    }

    return $definition;
  }

}
