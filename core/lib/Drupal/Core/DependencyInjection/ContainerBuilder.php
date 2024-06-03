<?php

namespace Drupal\Core\DependencyInjection;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\DependencyInjection\ServiceIdHashTrait;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder as SymfonyContainerBuilder;
use Symfony\Component\DependencyInjection\Container as SymfonyContainer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Drupal's dependency injection container builder.
 *
 * @todo Submit upstream patches to Symfony to not require these overrides.
 *
 * @ingroup container
 */
class ContainerBuilder extends SymfonyContainerBuilder implements ContainerInterface {

  use ServiceIdHashTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(?ParameterBagInterface $parameterBag = NULL) {
    parent::__construct($parameterBag);
    $this->setResourceTracking(FALSE);
  }

  /**
   * Overrides Symfony\Component\DependencyInjection\ContainerBuilder::set().
   *
   * Drupal's container builder can be used at runtime after compilation, so we
   * override Symfony's ContainerBuilder's restriction on setting services in a
   * frozen builder.
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   *
   * @todo Restrict this to synthetic services only. Ideally, the upstream
   *   ContainerBuilder class should be fixed to allow setting synthetic
   *   services in a frozen builder.
   */
  public function set($id, $service) {
    SymfonyContainer::set($id, $service);
  }

  /**
   * {@inheritdoc}
   */
  public function register($id, $class = NULL): Definition {
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
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function setParameter($name, $value) {
    if (strtolower($name) !== $name) {
      throw new \InvalidArgumentException("Parameter names must be lowercase: $name");
    }
    parent::setParameter($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    assert(FALSE, 'The container was serialized.');
    return array_keys(get_object_vars($this));
  }

}
