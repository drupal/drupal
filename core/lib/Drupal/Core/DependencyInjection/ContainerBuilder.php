<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\ContainerBuilder.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Drupal's dependency injection container builder.
 *
 * @todo Submit upstream patches to Symfony to not require these overrides.
 *
 * @ingroup container
 */
class ContainerBuilder extends SymfonyContainerBuilder {

  /**
   * {@inheritdoc}
   */
  public function __construct(ParameterBagInterface $parameterBag = NULL) {
    $this->setResourceTracking(FALSE);
    parent::__construct($parameterBag);
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
   * Synchronizes a service change.
   *
   * This method is a copy of the ContainerBuilder of symfony.
   *
   * This method updates all services that depend on the given
   * service by calling all methods referencing it.
   *
   * @param string $id A service id
   */
  private function synchronize($id) {
    foreach ($this->getDefinitions() as $definitionId => $definition) {
      // only check initialized services
      if (!$this->initialized($definitionId)) {
        continue;
      }

      foreach ($definition->getMethodCalls() as $call) {
        foreach ($call[1] as $argument) {
          if ($argument instanceof Reference && $id == (string) $argument) {
            $this->callMethod($this->get($definitionId), $call);
          }
        }
      }
    }
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
