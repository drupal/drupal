<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\ContextRepositoryInterface.
 */

namespace Drupal\Core\Plugin\Context;

/**
 * Offers a global context repository.
 *
 * Provides a list of all available contexts, which is mostly useful for
 * configuration on forms, as well as a method to get the concrete contexts with
 * their values, given a list of fully qualified context IDs.
 *
 * @see \Drupal\Core\Plugin\Context\ContextProviderInterface
 */
interface ContextRepositoryInterface {

  /**
   * Gets runtime context values for the given context IDs.
   *
   * Given that context providers might not return contexts for the given
   * context IDs, it is also not guaranteed that the context repository returns
   * contexts for all specified IDs.
   *
   * @param string[] $context_ids
   *   Fully qualified context IDs, which looks like
   *   @{service_id}:{unqualified_context_id}, so for example
   *   node.node_route_context:node.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The determined contexts, keyed by the fully qualified context ID.
   */
  public function getRuntimeContexts(array $context_ids);

  /**
   * Gets all available contexts for the purposes of configuration.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   All available contexts.
   */
  public function getAvailableContexts();

}
