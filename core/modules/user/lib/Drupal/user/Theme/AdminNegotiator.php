<?php

/**
 * @file
 * Contains \Drupal\user\Theme\AdminNegotiator.
 */

namespace Drupal\user\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sets the active theme on admin pages.
 */
class AdminNegotiator implements ThemeNegotiatorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(AccountInterface $user, ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, AdminContext $admin_context) {
    $this->user = $user;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return ($this->entityManager->hasController('user_role', 'storage') && $this->user->hasPermission('view the administration theme') && $this->adminContext->isAdminRoute($request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(Request $request) {
    return $this->configFactory->get('system.theme')->get('admin');
  }

}
