<?php
// @codingStandardsIgnoreFile

namespace Drupal\Core\DependencyInjection;

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
    $this->setResourceTracking(FALSE);
    parent::__construct($parameterBag);
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
