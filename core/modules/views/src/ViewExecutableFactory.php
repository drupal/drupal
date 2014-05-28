<?php

/**
 * @file
 * Contains \Drupal\views\ViewExecutableFactory.
 */

namespace Drupal\views;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\ViewStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the cache backend factory.
 */
class ViewExecutableFactory {

  /**
   * Stores the current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new ViewExecutableFactory
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(AccountInterface $user, RequestStack $request_stack) {
    $this->user = $user;
    $this->requestStack = $request_stack;
  }

  /**
   * Instantiates a ViewExecutable class.
   *
   * @param \Drupal\views\ViewStorageInterface $view
   *   A view entity instance.
   *
   * @return \Drupal\views\ViewExecutable
   *   A ViewExecutable instance.
   */
  public function get(ViewStorageInterface $view) {
    $view = new ViewExecutable($view, $this->user);
    $view->setRequest($this->requestStack->getCurrentRequest());
    return $view;
  }

}
