<?php
/**
 * @file
 * Contains \Drupal\Core\PageCache\ChainResponsePolicy.
 */

namespace Drupal\Core\PageCache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements a compound response policy.
 *
 * When evaluating the compound policy, all of the contained rules are applied
 * to the response. The overall result is computed according to the following
 * rules:
 *
 * <ol>
 *   <li>Returns static::DENY if any of the rules evaluated to static::DENY</li>
 *   <li>Otherwise returns NULL</li>
 * </ol>
 */
class ChainResponsePolicy implements ChainResponsePolicyInterface {

  /**
   * A list of policy rules to apply when this policy is checked.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface[]
   */
  protected $rules = [];

  /**
   * {@inheritdoc}
   */
  public function check(Response $response, Request $request) {
    foreach ($this->rules as $rule) {
      $result = $rule->check($response, $request);
      if ($result === static::DENY) {
        return $result;
      }
      elseif (isset($result)) {
        throw new \UnexpectedValueException('Return value of ResponsePolicyInterface::check() must be one of ResponsePolicyInterface::DENY or NULL');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addPolicy(ResponsePolicyInterface $policy) {
    $this->rules[] = $policy;
    return $this;
  }

}
