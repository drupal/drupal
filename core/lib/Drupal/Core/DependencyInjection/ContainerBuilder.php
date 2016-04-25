<?php

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\LazyProxy\Instantiator\RealServiceInstantiator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;

/**
 * Drupal's dependency injection container builder.
 *
 * @todo Submit upstream patches to Symfony to not require these overrides.
 *
 * @ingroup container
 */
class ContainerBuilder extends SymfonyContainerBuilder {

  /**
   * @var \Doctrine\Instantiator\InstantiatorInterface|null
   */
  private $proxyInstantiator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ParameterBagInterface $parameterBag = NULL) {
    $this->setResourceTracking(FALSE);
    parent::__construct($parameterBag);
  }

  /**
   * Creates a service for a service definition.
   *
   * Overrides the parent implementation, but just changes one line about
   * deprecations, see below.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $definition
   * @param string $id
   * @param bool|true $tryProxy
   *
   * @return mixed|object
   */
  public function createService(Definition $definition, $id, $tryProxy = true)
  {
    if ($definition->isSynthetic()) {
      throw new RuntimeException(sprintf('You have requested a synthetic service ("%s"). The DIC does not know how to construct this service.', $id));
    }

    if ($definition->isDeprecated()) {
      // Suppress deprecation warnings when a service is marked as
      // 'deprecated: %service_id%-no-warning'
      if ($definition->getDeprecationMessage($id) != ($id . '-no-warning')) {
        @trigger_error($definition->getDeprecationMessage($id), E_USER_DEPRECATED);
      }
    }

    if ($tryProxy && $definition->isLazy()) {
      $container = $this;

      $proxy = $this
        ->getProxyInstantiator()
        ->instantiateProxy(
          $container,
          $definition,
          $id, function () use ($definition, $id, $container) {
          return $container->createService($definition, $id, false);
        }
        );
      $this->shareService($definition, $proxy, $id);

      return $proxy;
    }

    $parameterBag = $this->getParameterBag();

    if (null !== $definition->getFile()) {
      require_once $parameterBag->resolveValue($definition->getFile());
    }

    $arguments = $this->resolveServices($parameterBag->unescapeValue($parameterBag->resolveValue($definition->getArguments())));

    if (null !== $factory = $definition->getFactory()) {
      if (is_array($factory)) {
        $factory = array($this->resolveServices($parameterBag->resolveValue($factory[0])), $factory[1]);
      } elseif (!is_string($factory)) {
        throw new RuntimeException(sprintf('Cannot create service "%s" because of invalid factory', $id));
      }

      $service = call_user_func_array($factory, $arguments);

      if (!$definition->isDeprecated() && is_array($factory) && is_string($factory[0])) {
        $r = new \ReflectionClass($factory[0]);

        if (0 < strpos($r->getDocComment(), "\n * @deprecated ")) {
          @trigger_error(sprintf('The "%s" service relies on the deprecated "%s" factory class. It should either be deprecated or its factory upgraded.', $id, $r->name), E_USER_DEPRECATED);
        }
      }
    } elseif (null !== $definition->getFactoryMethod(false)) {
      if (null !== $definition->getFactoryClass(false)) {
        $factory = $parameterBag->resolveValue($definition->getFactoryClass(false));
      } elseif (null !== $definition->getFactoryService(false)) {
        $factory = $this->get($parameterBag->resolveValue($definition->getFactoryService(false)));
      } else {
        throw new RuntimeException(sprintf('Cannot create service "%s" from factory method without a factory service or factory class.', $id));
      }

      $service = call_user_func_array(array($factory, $definition->getFactoryMethod(false)), $arguments);
    } else {
      $r = new \ReflectionClass($parameterBag->resolveValue($definition->getClass()));

      $service = null === $r->getConstructor() ? $r->newInstance() : $r->newInstanceArgs($arguments);

      if (!$definition->isDeprecated() && 0 < strpos($r->getDocComment(), "\n * @deprecated ")) {
        // Skip deprecation notices for deprecations which opt out.
        @trigger_error(sprintf('The "%s" service relies on the deprecated "%s" class. It should either be deprecated or its implementation upgraded.', $id, $r->name), E_USER_DEPRECATED);
      }
    }

    if ($tryProxy || !$definition->isLazy()) {
      // share only if proxying failed, or if not a proxy
      $this->shareService($definition, $service, $id);
    }

    foreach ($definition->getMethodCalls() as $call) {
      $this->callMethod($service, $call);
    }

    $properties = $this->resolveServices($parameterBag->unescapeValue($parameterBag->resolveValue($definition->getProperties())));
    foreach ($properties as $name => $value) {
      $service->$name = $value;
    }

    if ($callable = $definition->getConfigurator()) {
      if (is_array($callable)) {
        $callable[0] = $parameterBag->resolveValue($callable[0]);

        if ($callable[0] instanceof Reference) {
          $callable[0] = $this->get((string) $callable[0], $callable[0]->getInvalidBehavior());
        } elseif ($callable[0] instanceof Definition) {
          $callable[0] = $this->createService($callable[0], null);
        }
      }

      if (!is_callable($callable)) {
        throw new InvalidArgumentException(sprintf('The configure callable for class "%s" is not a callable.', get_class($service)));
      }

      call_user_func($callable, $service);
    }

    return $service;
  }

  /**
   * Retrieves the currently set proxy instantiator or instantiates one.
   *
   * @return InstantiatorInterface
   */
  private function getProxyInstantiator()
  {
    if (!$this->proxyInstantiator) {
      $this->proxyInstantiator = new RealServiceInstantiator();
    }

    return $this->proxyInstantiator;
  }

  /**
   * Direct copy of the parent function.
   */
  protected function shareService(Definition $definition, $service, $id)
  {
    if ($definition->isShared() && self::SCOPE_PROTOTYPE !== $scope = $definition->getScope(false)) {
      if (self::SCOPE_CONTAINER !== $scope && !isset($this->scopedServices[$scope])) {
        throw new InactiveScopeException($id, $scope);
      }

      $this->services[$lowerId = strtolower($id)] = $service;

      if (self::SCOPE_CONTAINER !== $scope) {
        $this->scopedServices[$scope][$lowerId] = $service;
      }
    }
  }

  /**
   * Overrides Symfony\Component\DependencyInjection\ContainerBuilder::set().
   *
   * Drupal's container builder can be used at runtime after compilation, so we
   * override Symfony's ContainerBuilder's restriction on setting services in a
   * frozen builder.
   *
   * @todo Restrict this to synthetic services only. Ideally, the upstream
   *   ContainerBuilder class should be fixed to allow setting synthetic
   *   services in a frozen builder.
   */
  public function set($id, $service, $scope = self::SCOPE_CONTAINER) {
    if (strtolower($id) !== $id) {
      throw new \InvalidArgumentException("Service ID names must be lowercase: $id");
    }
    SymfonyContainer::set($id, $service, $scope);

    // Ensure that the _serviceId property is set on synthetic services as well.
    if (isset($this->services[$id]) && is_object($this->services[$id]) && !isset($this->services[$id]->_serviceId)) {
      $this->services[$id]->_serviceId = $id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function register($id, $class = null) {
    if (strtolower($id) !== $id) {
      throw new \InvalidArgumentException("Service ID names must be lowercase: $id");
    }
    return parent::register($id, $class);
  }

  /**
   * {@inheritdoc}
   */
  public function setParameter($name, $value) {
    if (strtolower($name) !== $name) {
      throw new \InvalidArgumentException("Parameter names must be lowercase: $name");
    }
    parent::setParameter($name, $value);
  }

  /**
   * A 1to1 copy of parent::callMethod.
   */
  protected function callMethod($service, $call) {
    $services = self::getServiceConditionals($call[1]);

    foreach ($services as $s) {
      if (!$this->has($s)) {
        return;
      }
    }

    call_user_func_array(array($service, $call[0]), $this->resolveServices($this->getParameterBag()->resolveValue($call[1])));
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    assert(FALSE, 'The container was serialized.');
    return array_keys(get_object_vars($this));
  }

}
