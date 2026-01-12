<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\DependencyInjection\Compiler\StackedKernelPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StackMiddleware\StackedHttpKernel;
use Drupal\Tests\Core\DependencyInjection\Fixture\FinalTestHttpMiddlewareClass;
use Drupal\Tests\Core\DependencyInjection\Fixture\FinalTestNonTerminableHttpMiddlewareClass;
use Drupal\Tests\Core\DependencyInjection\Fixture\TestClosureHttpMiddlewareClass;
use Drupal\Tests\Core\DependencyInjection\Fixture\TestCompatClosureHttpMiddlewareClass;
use Drupal\Tests\Core\DependencyInjection\Fixture\TestHttpMiddlewareClass;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\Core\DependencyInjection\Compiler\StackedKernelPass.
 */
#[CoversClass(StackedKernelPass::class)]
#[Group('DependencyInjection')]
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
   * Tests process with stacked kernel.
   */
  public function testProcessWithStackedKernel(): void {
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
    $this->assertInstanceOf(IteratorArgument::class, $stacked_kernel_args[1]);
    $middlewares = $stacked_kernel_args[1]->getValues();
    $this->assertCount(4, $middlewares);
    $this->assertSame('http_kernel.one', (string) $middlewares[0]);
    $this->assertSame('http_kernel.two', (string) $middlewares[1]);
    $this->assertSame('http_kernel.three', (string) $middlewares[2]);
    $this->assertSame('http_kernel.basic', (string) $middlewares[3]);

    // Check the modified definitions.
    $definition = $this->containerBuilder->getDefinition('http_kernel.one');
    $args = $definition->getArguments();
    $this->assertSame('http_kernel.one.http_middleware_inner', (string) $args[0]);
    $this->assertSame('test', $args[1]);

    $alias = $this->containerBuilder->getAlias('http_kernel.one.http_middleware_inner');
    $this->assertSame('http_kernel.two', (string) $alias);

    $definition = $this->containerBuilder->getDefinition('http_kernel.two');
    $args = $definition->getArguments();
    $this->assertSame('http_kernel.two.http_middleware_inner', (string) $args[0]);
    $this->assertSame('test', $args[1]);

    $alias = $this->containerBuilder->getAlias('http_kernel.two.http_middleware_inner');
    $this->assertSame('http_kernel.three', (string) $alias);

    $definition = $this->containerBuilder->getDefinition('http_kernel.three');
    $args = $definition->getArguments();
    $this->assertSame('http_kernel.three.http_middleware_inner', (string) $args[0]);
    $this->assertSame('test', $args[1]);

    $alias = $this->containerBuilder->getAlias('http_kernel.three.http_middleware_inner');
    $this->assertSame('http_kernel.basic', (string) $alias);
  }

  /**
   * Tests process with http kernel.
   */
  public function testProcessWithHttpKernel(): void {
    $kernel = new Definition(HttpKernelInterface::class);
    $kernel->setPublic(TRUE);
    $this->containerBuilder->setDefinition('http_kernel', $kernel);
    $this->stackedKernelPass->process($this->containerBuilder);

    $unprocessed_kernel = $this->containerBuilder->getDefinition('http_kernel');

    $this->assertSame($kernel, $unprocessed_kernel);
    $this->assertSame($kernel->getArguments(), $unprocessed_kernel->getArguments());
  }

  /**
   * Tests that class declared 'final' can be added as http_middleware.
   */
  public function testProcessWithStackedKernelAndFinalHttpMiddleware(): void {
    $stacked_kernel = new Definition(StackedHttpKernel::class);
    $stacked_kernel->setPublic(TRUE);
    $this->containerBuilder->setDefinition('http_kernel', $stacked_kernel);
    $basic_kernel = $this->createMock(HttpKernelInterface::class);
    $basic_definition = (new Definition($basic_kernel::class))
      ->setPublic(TRUE);
    $this->containerBuilder->setDefinition('http_kernel.basic', $basic_definition);

    $this->containerBuilder->setDefinition('http_kernel.one', (new Definition(TestHttpMiddlewareClass::class))
      ->setPublic(TRUE)
      ->addTag('http_middleware', [
        'priority' => 200,
        'responder' => TRUE,
      ]));
    // First middleware class declared final.
    $this->containerBuilder->setDefinition('http_kernel.two', (new Definition(FinalTestHttpMiddlewareClass::class))
      ->setPublic(TRUE)
      ->addTag('http_middleware', [
        'priority' => 100,
        'responder' => TRUE,
      ]));
    // Second middleware class declared final, this time without implementing
    // TerminableInterface.
    $this->containerBuilder->setDefinition('http_kernel.three', (new Definition(FinalTestNonTerminableHttpMiddlewareClass::class))
      ->setPublic(TRUE)
      ->addTag('http_middleware', [
        'priority' => 50,
        'responder' => TRUE,
      ]));
    $this->stackedKernelPass->process($this->containerBuilder);
    $this->assertIsObject($this->containerBuilder->get('http_kernel'));
  }

  /**
   * Tests that class taking a service closure can be added as http_middleware.
   */
  #[DataProvider('providerTestClosureMiddleware')]
  public function testProcessWithStackedKernelAndServiceClosureMiddleware(string $closureClass): void {
    $stacked_kernel = new Definition(StackedHttpKernel::class);
    $stacked_kernel->setPublic(TRUE);
    $this->containerBuilder->setDefinition('http_kernel', $stacked_kernel);
    $basic_kernel = $this->createMock(HttpKernelInterface::class);
    $basic_definition = (new Definition($basic_kernel::class))
      ->setPublic(TRUE);
    $this->containerBuilder->setDefinition('http_kernel.basic', $basic_definition);

    $this->containerBuilder->setDefinition('http_kernel.one', (new Definition(TestHttpMiddlewareClass::class))
      ->setPublic(TRUE)
      ->addTag('http_middleware', [
        'priority' => 200,
      ]));
    // Middleware class taking a service closure as its inner kernel argument.
    // Inner services will only be constructed when required.
    $this->containerBuilder->setDefinition('http_kernel.two', (new Definition(TestClosureHttpMiddlewareClass::class))
      ->setPublic(TRUE)
      ->addTag('http_middleware', [
        'priority' => 100,
      ]));
    // Middleware class declared final, this time without implementing
    // TerminableInterface.
    $this->containerBuilder->setDefinition('http_kernel.three', (new Definition(FinalTestNonTerminableHttpMiddlewareClass::class))
      ->setPublic(TRUE)
      ->addTag('http_middleware', [
        'priority' => 50,
      ]));
    $this->stackedKernelPass->process($this->containerBuilder);
    $this->assertIsObject($this->containerBuilder->get('http_kernel'));
    $this->assertTrue($this->containerBuilder->initialized('http_kernel'));
    $this->assertTrue($this->containerBuilder->initialized('http_kernel.one'));
    $this->assertTrue($this->containerBuilder->initialized('http_kernel.two'));
    $this->assertFalse($this->containerBuilder->initialized('http_kernel.three'));
    $this->assertFalse($this->containerBuilder->initialized('http_kernel.basic'));
  }

  /**
   * Data provider for stacked kernel service closure middleware test.
   */
  public static function providerTestClosureMiddleware(): array {
    return [
      [TestClosureHttpMiddlewareClass::class],
      [TestCompatClosureHttpMiddlewareClass::class],
    ];
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
   *   The middleware definition.
   */
  protected function createMiddlewareServiceDefinition($tag = TRUE, $priority = 0): Definition {
    $definition = new Definition(TestHttpMiddlewareClass::class, ['test']);
    $definition->setPublic(TRUE);

    if ($tag) {
      $definition->addTag('http_middleware', ['priority' => $priority]);
    }

    return $definition;
  }

}
