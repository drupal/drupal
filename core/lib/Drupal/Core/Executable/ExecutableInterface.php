<?php

/**
 * @file
 * Contains \Drupal\Core\Executable\ExecutableInterface.
 */

namespace Drupal\Core\Executable;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Form\FormInterface;

/**
 * An interface for executable plugins.
 *
 * Executable plugins are context-aware and configurable. They support the
 * following keys in their plugin definitions:
 * - context: An array of context definitions, keyed by context name. Each
 *   context definition is a typed data definition describing the context. Check
 *   the typed data definition docs for details.
 * - configuration: An array of configuration option definitions, keyed by
 *   option name. Each option definition is a typed data definition describing
 *   the configuration option. Check the typed data definition docs for details.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager::create()
 */
interface ExecutableInterface extends ContextAwarePluginInterface, FormInterface {

  /**
   * Executes the plugin.
   */
  public function execute();

  /**
   * Provides a human readable summary of the executable's configuration.
   */
  public function summary();

  /**
   * Sets the executable manager class.
   *
   * @param \Drupal\Core\Condition\ConditionManager $executableManager
   *   The executable manager.
   */
  public function setExecutableManager(ExecutableManagerInterface $executableManager);

}
