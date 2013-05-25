<?php

/**
 * @file
 * Contains \Drupal\Core\Condition\ConditionManager.
 */

namespace Drupal\Core\Condition;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * A plugin manager for condition plugins.
 */
class ConditionManager extends PluginManagerBase implements ExecutableManagerInterface {

  /**
   * Constructs aa ConditionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('Condition', $namespaces);
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new AlterDecorator($this->discovery, 'condition_info');
    $this->discovery = new CacheDecorator($this->discovery, 'condition:' . language(Language::TYPE_INTERFACE)->langcode);

    $this->factory = new DefaultFactory($this);
  }

  /**
   * Override of Drupal\Component\Plugin\PluginManagerBase::createInstance().
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin = $this->factory->createInstance($plugin_id, $configuration);
    return $plugin->setExecutableManager($this);
  }

  /**
   * Implements Drupal\Core\Executable\ExecutableManagerInterface::execute().
   */
  public function execute(ExecutableInterface $condition) {
    $result = $condition->evaluate();
    return $condition->isNegated() ? !$result : $result;
  }

}
