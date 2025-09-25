<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Drupal\Core\StackMiddleware\StackedHttpKernel;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\AbstractRecursivePass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides a compiler pass for stacked HTTP kernels.
 *
 * Builds the HTTP kernel by collecting all services tagged 'http_middleware'
 * and assembling them into a StackedHttpKernel. The middleware with the highest
 * priority ends up as the outermost while the lowest priority middleware wraps
 * the actual HTTP kernel defined by the http_kernel.basic service.
 *
 * A HTTP middleware may act on a request before and/or after it is delegated to
 * the next inner layer. The inner layer is injected into the middleware in the
 * first constructor argument. The following type hints are supported for the
 * argument: Either Symfony\Component\HttpKernel\HttpKernelInterface or
 * \Closure or an union of both to retain backward compatibility. If the
 * middleware type hint contains a \Closure, the inner layer is injected as a
 * service closure.
 *
 * In general middlewares should not have heavy dependencies. This is especially
 * important for high-priority services which need to run before the internal
 * page cache.
 *
 * An example of a high priority middleware.
 * @code
 * http_middleware.reverse_proxy:
 *   class: Drupal\Core\StackMiddleware\ReverseProxyMiddleware
 *   arguments: ['@settings']
 *   tags:
 *     - { name: http_middleware, priority: 300 }
 * @endcode
 *
 * @see \Drupal\Core\StackMiddleware\StackedHttpKernel
 */
class StackedKernelPass extends AbstractRecursivePass implements CompilerPassInterface {

  use PriorityTaggedServiceTrait;

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {

    if (!$container->hasDefinition('http_kernel')) {
      return;
    }

    $stacked_kernel = $container->getDefinition('http_kernel');

    // Return now if this is not a stacked kernel.
    if ($stacked_kernel->getClass() !== StackedHttpKernel::class) {
      return;
    }

    $decorated_id = 'http_kernel.basic';
    $middlewares_param = [new Reference($decorated_id)];

    foreach (array_reverse($this->findAndSortTaggedServices('http_middleware', $container)) as $ref) {
      // Prepend a reference to the middlewares container parameter.
      array_unshift($middlewares_param, $ref);

      // Setup an alias on the outer middleware pointing to the inner one.
      $decorator_id = (string) $ref;
      $container->setAlias($decorator_id . '.http_middleware_inner', $decorated_id);
      $decorated_id = $decorator_id;
    }

    $arguments = [new Reference($decorated_id), new IteratorArgument($middlewares_param)];
    $stacked_kernel->setArguments($arguments);

    parent::process($container);
  }

  /**
   * {@inheritdoc}
   */
  protected function processValue(mixed $value, bool $isRoot = FALSE): mixed {
    $value = parent::processValue($value, $isRoot);

    if (!$value instanceof Definition || !$value->hasTag('http_middleware')) {
      return $value;
    }

    $constructor = $this->getConstructor($value, TRUE);
    $params = $constructor->getParameters();
    $innerType = $params[0]->getType();
    $innerParamTypes = ($innerType instanceof \ReflectionUnionType || $innerType instanceof \ReflectionIntersectionType) ? $innerType->getTypes() : [$innerType];
    $paramTypeNames = array_map(fn ($param) => (string) $param, $innerParamTypes);

    $inner = new Reference($this->currentId . '.http_middleware_inner');
    if (in_array(\Closure::class, $paramTypeNames, TRUE)) {
      $inner = new ServiceClosureArgument($inner);
    }

    $arguments = $value->getArguments();
    array_unshift($arguments, $inner);
    $value->setArguments($arguments);

    return $value;
  }

}
