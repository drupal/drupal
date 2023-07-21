<?php

namespace Drupal\Core\Validation;

use Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Plugin\Validation\Constraint\EmailConstraint;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Constraint plugin manager.
 *
 * Manages validation constraints based upon
 * \Symfony\Component\Validator\Constraint, whereas Symfony constraints are
 * added in manually during construction. Constraint options are passed on as
 * plugin configuration during plugin instantiation.
 *
 * While core does not prefix constraint plugins, modules have to prefix them
 * with the module name in order to avoid any naming conflicts; for example, a
 * "profile" module would have to prefix any constraints with "Profile".
 *
 * Constraint plugins may specify data types to which support is limited via the
 * 'type' key of plugin definitions. See
 * \Drupal\Core\Validation\Annotation\Constraint for details.
 *
 * @see \Drupal\Core\Validation\Annotation\Constraint
 */
class ConstraintManager extends DefaultPluginManager {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->factory = new ConstraintFactory($this);
    parent::__construct('Plugin/Validation/Constraint', $namespaces, $module_handler, NULL, 'Drupal\Core\Validation\Annotation\Constraint');
    $this->alterInfo('validation_constraint');
    $this->setCacheBackend($cache_backend, 'validation_constraint_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = parent::getDiscovery();
      $this->discovery = new StaticDiscoveryDecorator($this->discovery, [$this, 'registerDefinitions']);
    }
    return $this->discovery;
  }

  /**
   * Creates a validation constraint.
   *
   * @param string $name
   *   The name or plugin id of the constraint.
   * @param mixed $options
   *   The options to pass to the constraint class. Required and supported
   *   options depend on the constraint class.
   *
   * @return \Symfony\Component\Validator\Constraint
   *   A validation constraint plugin.
   */
  public function create($name, $options) {
    if (!is_array($options)) {
      // Plugins need an array as configuration, so make sure we have one.
      // The constraint classes support passing the options as part of the
      // 'value' key also.
      $options = isset($options) ? ['value' => $options] : [];
    }
    return $this->createInstance($name, $options);
  }

  /**
   * Callback for registering definitions for constraints shipped with Symfony.
   *
   * @see ConstraintManager::__construct()
   */
  public function registerDefinitions() {
    $this->getDiscovery()->setDefinition('Callback', [
      'label' => new TranslatableMarkup('Callback'),
      'class' => Callback::class,
      'type' => FALSE,
    ]);
    $this->getDiscovery()->setDefinition('Blank', [
      'label' => new TranslatableMarkup('Blank'),
      'class' => Blank::class,
      'type' => FALSE,
    ]);
    $this->getDiscovery()->setDefinition('NotBlank', [
      'label' => new TranslatableMarkup('Not blank'),
      'class' => NotBlank::class,
      'type' => FALSE,
    ]);
    $this->getDiscovery()->setDefinition('Email', [
      'label' => new TranslatableMarkup('Email'),
      'class' => EmailConstraint::class,
      'type' => ['string'],
    ]);
    $this->getDiscovery()->setDefinition('Choice', [
      'label' => new TranslatableMarkup('Choice'),
      'class' => Choice::class,
      'type' => FALSE,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    // Make sure 'type' is set and either an array or FALSE.
    if ($definition['type'] !== FALSE && !is_array($definition['type'])) {
      $definition['type'] = [$definition['type']];
    }
  }

  /**
   * Returns a list of constraints that support the given type.
   *
   * @param string $type
   *   The type to filter on.
   *
   * @return array
   *   An array of constraint plugin definitions supporting the given type,
   *   keyed by constraint name (plugin ID).
   */
  public function getDefinitionsByType($type) {
    $definitions = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if ($definition['type'] === FALSE || in_array($type, $definition['type'])) {
        $definitions[$plugin_id] = $definition;
      }
    }
    return $definitions;
  }

}
