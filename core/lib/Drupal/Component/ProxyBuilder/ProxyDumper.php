<?php

/**
 * @file
 * Contains \Drupal\Component\ProxyBuilder\ProxyDumper.
 */

namespace Drupal\Component\ProxyBuilder;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\LazyProxy\PhpDumper\DumperInterface;

/**
 * Dumps the proxy service into the dumped PHP container file.
 */
class ProxyDumper implements DumperInterface {

  /**
   * Keeps track of already existing proxy classes.
   *
   * @var array
   */
  protected $buildClasses = [];

  /**
   * The proxy builder.
   *
   * @var \Drupal\Component\ProxyBuilder\ProxyBuilder
   */
  protected $builder;

  public function __construct(ProxyBuilder $builder) {
    $this->builder = $builder;
  }

  /**
   * {@inheritdoc}
   */
  public function isProxyCandidate(Definition $definition) {
    return $definition->isLazy() && ($class = $definition->getClass()) && class_exists($class);
  }

  /**
   * {@inheritdoc}
   */
  public function getProxyFactoryCode(Definition $definition, $id) {
    // Note: the specific get method is called initially with $lazyLoad=TRUE;
    // When you want to retrieve the actual service, the code generated in
    // ProxyBuilder calls the method with lazy loading disabled.
    $output = <<<'EOS'
        if ($lazyLoad) {
            return $this->services['{{ id }}'] = new {{ class_name }}($this, '{{ id }}');
        }

EOS;
    $output = str_replace('{{ id }}', $id, $output);
    $output = str_replace('{{ class_name }}', $this->builder->buildProxyClassName($definition->getClass()), $output);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getProxyCode(Definition $definition) {
    // Maybe the same class is used in different services, which are both marked
    // as lazy (just think about 2 database connections).
    // In those cases we should not generate proxy code the second time.
    if (!isset($this->buildClasses[$definition->getClass()])) {
      $this->buildClasses[$definition->getClass()] = TRUE;
      return $this->builder->build($definition->getClass());
    }
    else {
      return '';
    }
  }

}
