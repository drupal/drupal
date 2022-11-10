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
  public function __construct(ParameterBagInterface $parameterBag = NULL) {
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
   * {@inheritdoc}
   */
  public function __sleep() {
    assert(FALSE, 'The container was serialized.');
    return array_keys(get_object_vars($this));
  }

}
