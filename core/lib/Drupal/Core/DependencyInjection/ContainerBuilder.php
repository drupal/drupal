<?php

/**
 * @file
 * Definition of Drupal\Core\DependencyInjection\Container.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Drupal's dependency injection container builder.
 *
 * @todo Submit upstream patches to Symfony to not require these overrides.
 */
class ContainerBuilder extends SymfonyContainerBuilder {

  /**
   * Overrides Symfony\Component\DependencyInjection\ContainerBuilder::addObjectResource().
   *
   * Drupal does not use Symfony's Config component, so we override
   * addObjectResource() with an empty implementation to prevent errors during
   * container compilation.
   */
  public function addObjectResource($object) {
  }

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE) {
    $service = parent::get($id, $invalidBehavior);
    // Some services are called but do not exist, so the parent returns nothing.
    if (is_object($service)) {
      $service->_serviceId = $id;
    }

    return $service;
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
    SymfonyContainer::set($id, $service, $scope);

    if ($this->hasDefinition($id) && ($definition = $this->getDefinition($id)) && $definition->isSynchronized()) {
      $this->synchronize($id);
    }
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
    trigger_error('The container was serialized.', E_USER_ERROR);
    return array_keys(get_object_vars($this));
  }

}
