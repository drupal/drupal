<?php

namespace Drupal\Core\PageCache;

/**
 * Defines the interface for compound request policies.
 */
interface ChainRequestPolicyInterface extends RequestPolicyInterface {

  /**
   * Add a policy to the list of policy rules.
   *
   * @param \Drupal\Core\PageCache\RequestPolicyInterface $policy
   *   The request policy rule to add.
   *
   * @return $this
   */
  public function addPolicy(RequestPolicyInterface $policy);

}
