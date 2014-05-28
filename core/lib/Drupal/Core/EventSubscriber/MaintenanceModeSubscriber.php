<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Page\DefaultHtmlPageRenderer;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Maintenance mode subscriber for controller requests.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * @defgroup menu_status_codes Menu status codes
   * @{
   * Status codes to be used to check the maintenance code.
   */

  /**
   * Internal menu status code -- Menu item inaccessible because site is offline.
   */
  const SITE_OFFLINE = 4;

  /**
   * Internal menu status code -- Everything is working fine.
   */
  const SITE_ONLINE = 5;

  /**
   * @} End of "defgroup menu_status_codes".
   */

  /**
   * Constructs a new MaintenanceModeSubscriber.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory, TranslationInterface $translation, UrlGeneratorInterface $url_generator, AccountInterface $account) {
    $this->state = $state;
    $this->config = $config_factory;
    $this->stringTranslation = $translation;
    $this->urlGenerator = $url_generator;
    $this->account = $account;
  }

  /**
   * Determine whether the page is configured to be offline.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDetermineSiteStatus(GetResponseEvent $event) {
    // Check if the site is offline.
    $request = $event->getRequest();
    $is_offline = $this->isSiteInMaintenance($request) ? static::SITE_OFFLINE : static::SITE_ONLINE;
    $request->attributes->set('_maintenance', $is_offline);
  }

  /**
   * Returns the site maintenance page if the site is offline.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();
    // Continue if the site is online and the response is not a redirection.
    if ($request->attributes->get('_maintenance') != static::SITE_ONLINE && !($response instanceof RedirectResponse)) {
      // Deliver the 503 page.
      drupal_maintenance_theme();
      $content = Xss::filterAdmin(String::format($this->config->get('system.maintenance')->get('message'), array(
        '@site' => $this->config->get('system.site')->get('name'),
      )));
      $content = DefaultHtmlPageRenderer::renderPage($content, t('Site under maintenance'));
      $response = new Response('Service unavailable', 503);
      $response->setContent($content);
      $event->setResponse($response);
    }

    $can_access_maintenance = $this->account->hasPermission('access site in maintenance mode');
    $is_maintenance = $this->state->get('system.maintenance_mode');
    // Ensure that the maintenance mode message is displayed only once
    // (allowing for page redirects) and specifically suppress its display on
    // the maintenance mode settings page.
    $is_maintenance_route = $request->attributes->get(RouteObjectInterface::ROUTE_NAME) == 'system.site_maintenance_mode';
    if ($is_maintenance && $can_access_maintenance && !$is_maintenance_route) {
      if ($this->account->hasPermission('administer site configuration')) {
        $this->drupalSetMessage($this->t('Operating in maintenance mode. <a href="@url">Go online.</a>', array('@url' => $this->urlGenerator->generate('system.site_maintenance_mode'))), 'status', FALSE);
      }
      else {
        $this->drupalSetMessage($this->t('Operating in maintenance mode.'), 'status', FALSE);
      }
    }
  }

  /**
   * Checks whether the site is in maintenance mode.
   *
   * @return bool
   *   FALSE if the site is not in maintenance mode
   */
  protected function isSiteInMaintenance() {
    // Check if site is in maintenance mode.
    if ($this->state->get('system.maintenance_mode')) {
      if (!$this->account->hasPermission('access site in maintenance mode')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Wraps the drupal_set_message function.
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    return drupal_set_message($message, $type, $repeat);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // In order to change the maintenance status an event subscriber with a
    // priority between 30 and 40 should be added.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDetermineSiteStatus', 40);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestMaintenance', 30);
    return $events;
  }

}
