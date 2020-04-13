<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\LazyContextRepository
 * @group context
 */
class LazyContextRepositoryTest extends UnitTestCase {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
  }

  /**
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeContextsSingle() {
    $contexts = $this->setupContextAndProvider('test_provider', ['test_context']);

    $lazy_context_repository = new LazyContextRepository($this->container, ['test_provider']);
    $run_time_contexts = $lazy_context_repository->getRuntimeContexts(['@test_provider:test_context']);
    $this->assertEquals(['@test_provider:test_context' => $contexts[0]], $run_time_contexts);
  }

  /**
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeMultipleContextsPerService() {
    $contexts = $this->setupContextAndProvider('test_provider', ['test_context0', 'test_context1']);

    $lazy_context_repository = new LazyContextRepository($this->container, ['test_provider']);
    $run_time_contexts = $lazy_context_repository->getRuntimeContexts(['@test_provider:test_context0', '@test_provider:test_context1']);
    $this->assertEquals(['@test_provider:test_context0' => $contexts[0], '@test_provider:test_context1' => $contexts[1]], $run_time_contexts);
  }

  /**
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeMultipleContextProviders() {
    $contexts0 = $this->setupContextAndProvider('test_provider', ['test_context0', 'test_context1'], ['test_context0']);
    $contexts1 = $this->setupContextAndProvider('test_provider2', ['test1_context0', 'test1_context1'], ['test1_context0']);

    $lazy_context_repository = new LazyContextRepository($this->container, ['test_provider']);
    $run_time_contexts = $lazy_context_repository->getRuntimeContexts(['@test_provider:test_context0', '@test_provider2:test1_context0']);
    $this->assertEquals(['@test_provider:test_context0' => $contexts0[0], '@test_provider2:test1_context0' => $contexts1[1]], $run_time_contexts);
  }

  /**
   * @covers ::getRuntimeContexts
   */
  public function testInvalidContextId() {
    $lazy_context_repository = new LazyContextRepository($this->container, ['test_provider']);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('You must provide the context IDs in the @{service_id}:{unqualified_context_id} format.');
    $lazy_context_repository->getRuntimeContexts(['test_context', '@test_provider:test_context1']);
  }

  /**
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeStaticCache() {
    $context0 = new Context(new ContextDefinition('example'));
    $context1 = new Context(new ContextDefinition('example'));

    $context_provider = $this->prophesize('\Drupal\Core\Plugin\Context\ContextProviderInterface');
    $context_provider->getRuntimeContexts(['test_context0', 'test_context1'])
      ->shouldBeCalledTimes(1)
      ->willReturn(['test_context0' => $context0, 'test_context1' => $context1]);
    $context_provider = $context_provider->reveal();
    $this->container->set('test_provider', $context_provider);

    $lazy_context_repository = new LazyContextRepository($this->container, ['test_provider']);
    $lazy_context_repository->getRuntimeContexts(['@test_provider:test_context0', '@test_provider:test_context1']);
    $lazy_context_repository->getRuntimeContexts(['@test_provider:test_context0', '@test_provider:test_context1']);
  }

  /**
   * @covers ::getAvailableContexts
   */
  public function testGetAvailableContexts() {
    $contexts0 = $this->setupContextAndProvider('test_provider0', ['test0_context0', 'test0_context1']);
    $contexts1 = $this->setupContextAndProvider('test_provider1', ['test1_context0', 'test1_context1']);

    $lazy_context_repository = new LazyContextRepository($this->container, ['test_provider0', 'test_provider1']);
    $contexts = $lazy_context_repository->getAvailableContexts();

    $this->assertEquals([
      '@test_provider0:test0_context0' => $contexts0[0],
      '@test_provider0:test0_context1' => $contexts0[1],
      '@test_provider1:test1_context0' => $contexts1[0],
      '@test_provider1:test1_context1' => $contexts1[1],
    ], $contexts);

  }

  /**
   * Sets up contexts and context providers.
   *
   * @param string $service_id
   *   The service ID of the service provider.
   * @param string[] $unqualified_context_ids
   *   An array of context slot names.
   * @param string[] $expected_unqualified_context_ids
   *   The expected unqualified context IDs passed to getRuntimeContexts.
   *
   * @return array
   *   An array of set up contexts.
   */
  protected function setupContextAndProvider($service_id, array $unqualified_context_ids, array $expected_unqualified_context_ids = []) {
    $contexts = [];
    for ($i = 0; $i < count($unqualified_context_ids); $i++) {
      $contexts[] = new Context(new ContextDefinition('example'));
    }

    $expected_unqualified_context_ids = $expected_unqualified_context_ids ?: $unqualified_context_ids;

    $context_provider = $this->prophesize('\Drupal\Core\Plugin\Context\ContextProviderInterface');
    $context_provider->getRuntimeContexts($expected_unqualified_context_ids)
      ->willReturn(array_combine($unqualified_context_ids, $contexts));
    $context_provider->getAvailableContexts()
      ->willReturn(array_combine($unqualified_context_ids, $contexts));
    $context_provider = $context_provider->reveal();
    $this->container->set($service_id, $context_provider);

    return $contexts;
  }

}
