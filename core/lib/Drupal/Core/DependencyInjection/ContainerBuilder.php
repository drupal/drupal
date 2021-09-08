<?php

// phpcs:ignoreFile Portions of this file are a direct copy of
// \Symfony\Component\DependencyInjection\Container.

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\LazyProxy\Instantiator\RealServiceInstantiator;
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
   * @var \Doctrine\Instantiator\InstantiatorInterface|null
   */
  private $proxyInstantiator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ParameterBagInterface $parameterBag = NULL) {
    parent::__construct($parameterBag);
    $this->setResourceTracking(FALSE);
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
   * A 1to1 copy of parent::shareService.
   *
   * @todo https://www.drupal.org/project/drupal/issues/2937010 Since Symfony
   *   3.4 this is not a 1to1 copy.
   */
  protected function shareService(Definition $definition, $service, $id, array &$inlineServices)
  {
    if ($definition->isShared()) {
      $this->services[$lowerId = strtolower($id)] = $service;
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
  public function set($id, $service) {
    if (strtolower($id) !== $id) {
      throw new \InvalidArgumentException("Service ID names must be lowercase: $id");
    }
    SymfonyContainer::set($id, $service);

    // Ensure that the _serviceId property is set on synthetic services as well.
    if (isset($this->services[$id]) && is_object($this->services[$id]) && !isset($this->services[$id]->_serviceId)) {
      $this->services[$id]->_serviceId = $id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function register($id, $class = null): Definition {
    if (strtolower($id) !== $id) {
      throw new \InvalidArgumentException("Service ID names must be lowercase: $id");
    }
    $definition = new Definition($class);
    // As of Symfony 5.2 all services are private by default, but in Drupal
    // services are still public by default.
    $definition->setPublic(TRUE);
    return $this->setDefinition($id, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function setAlias($alias, $id): Alias {
    $alias = parent::setAlias($alias, $id);
    // As of Symfony 3.4 all aliases are private by default.
    $alias->setPublic(TRUE);
    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefinition($id, Definition $definition): Definition {
    $definition = parent::setDefinition($id, $definition);
    // As of Symfony 3.4 all definitions are private by default.
    // \Symfony\Component\DependencyInjection\Compiler\ResolvePrivatesPassOnly
    // removes services marked as private from the container even if they are
    // also marked as public. Drupal requires services that are public to
    // remain in the container and not be removed.
    if ($definition->isPublic() && $definition->isPrivate()) {
      @trigger_error('Not marking service definitions as public is deprecated in drupal:9.2.0 and is required in drupal:10.0.0. Call $definition->setPublic(TRUE) before calling ::setDefinition(). See https://www.drupal.org/node/3194517', E_USER_DEPRECATED);
      $definition->setPrivate(FALSE);
    }
    return $definition;
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
   *
   * @todo https://www.drupal.org/project/drupal/issues/2937010 Since Symfony
   *   3.4 this is not a 1to1 copy.
   */
  protected function callMethod($service, $call, array &$inlineServices = array()) {
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
