<?php

namespace Drupal\outside_in;

use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Manages information related to Settings Tray.
 */
class OutsideInManager implements OutsideInManagerInterface {

  /**
   * The admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * OutsideInManager constructor.
   *
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   */
  public function __construct(AdminContext $admin_context, RouteMatchInterface $route_match, AccountInterface $account) {
    $this->adminContext = $admin_context;
    $this->routeMatch = $route_match;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    // Remove on Admin routes.
    $is_admin_route = $this->adminContext->isAdminRoute();

    // Remove on Block Demo page.
    $is_admin_demo_route = $this->routeMatch->getRouteName() === 'block.admin_demo';

    // @todo Check if there is actually a different admin theme.
    //   https://www.drupal.org/node/2784853
    return $this->account->hasPermission('administer blocks') && !$is_admin_route && !$is_admin_demo_route;
  }

}
