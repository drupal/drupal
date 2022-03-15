<?php

namespace Drupal\Core\Site;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provides the default implementation of the maintenance mode service.
 */
class MaintenanceMode implements MaintenanceModeInterface {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new maintenance mode service.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory) {
    $this->state = $state;
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if (!$this->state->get('system.maintenance_mode')) {
      return FALSE;
    }

    if ($route = $route_match->getRouteObject()) {
      if ($route->getOption('_maintenance_access')) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exempt(AccountInterface $account) {
    return $account->hasPermission('access site in maintenance mode');
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteMaintenanceMessage() {
    return new FormattableMarkup($this->config->get('system.maintenance')->get('message'), [
      '@site' => $this->config->get('system.site')->get('name'),
    ]);
  }

}
