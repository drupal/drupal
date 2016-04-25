<?php

namespace Drupal\Core\PageCache;

/**
 * Defines the interface for compound request policies.
 */
interface ChainResponsePolicyInterface extends ResponsePolicyInterface {

  /**
   * Add a policy to the list of policy rules.
   *
   * @param \Drupal\Core\PageCache\ResponsePolicyInterface $policy
   *   The request policy rule to add.
   *
   * @return $this
   */
  public function addPolicy(ResponsePolicyInterface $policy);

}
