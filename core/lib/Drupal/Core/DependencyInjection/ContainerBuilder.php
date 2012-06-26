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

    public function addCompilerPass(CompilerPassInterface $pass, $type = PassConfig::TYPE_BEFORE_OPTIMIZATION)
    {
      if (!isset($this->compiler) || null === $this->compiler) {
        $this->compiler = new Compiler();
      }

      $this->compiler->addPass($pass, $type);
    }

    public function compile()
    {
        if (null === $this->compiler) {
            $this->compiler = new Compiler();
        }

        $this->compiler->compile($this);
        $this->parameterBag->resolve();
        // TODO: The line below is commented out because there is code that calls
        // the set() method on the container after it has been built - that method
        // throws an exception if the container's parameters have been frozen.
        //$this->parameterBag = new FrozenParameterBag($this->parameterBag->all());
    }

}
