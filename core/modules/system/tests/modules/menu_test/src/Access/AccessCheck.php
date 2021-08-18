<?php

namespace Drupal\menu_test\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks access based on the 'menu_test' key in session.
 */
class AccessCheck implements AccessInterface, ContainerInjectionInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new AccessCheck class.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Check to see if user accessed this page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $result = AccessResult::allowedIf(
      $this->requestStack->getCurrentRequest()->getSession()->get('menu_test', 0) < 2
    );
    return $result->setCacheMaxAge(0);
  }

  /**
   * @return \Drupal\Core\Access\AccessResultForbidden
   */
  public function menuLocalAction7() {
    return AccessResult::forbidden()->addCacheTags(['menu_local_action7'])->addCacheContexts(['url.query_args:menu_local_action7']);
  }

  /**
   * @return \Drupal\Core\Access\AccessResultAllowed
   */
  public function menuLocalAction8() {
    return AccessResult::allowed()->addCacheTags(['menu_local_action8'])->addCacheContexts(['url.query_args:menu_local_action8']);
  }

}
