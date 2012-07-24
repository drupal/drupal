<?php

/**
 * @file
 * Definition of Drupal\Core\DependencyInjection\Container.
 */

namespace Drupal\Core\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\Compiler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

/**
 * Drupal's dependency injection container.
 */
class ContainerBuilder extends BaseContainerBuilder {

  public function __construct() {
    parent::__construct();
    $this->compiler = new Compiler();
  }

  public function addCompilerPass(CompilerPassInterface $pass, $type = PassConfig::TYPE_BEFORE_OPTIMIZATION) {
    $this->compiler->addPass($pass, $type);
  }

  public function compile() {
    $this->compiler->compile($this);
    $this->parameterBag->resolve();
  }

}
