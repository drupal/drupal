<?php

namespace Drupal\Core\Theme;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a theme negotiator that deals with the active theme on ajax requests.
 *
 * Many different pages can invoke an Ajax request to a generic Ajax path. It is
 * almost always desired for an Ajax response to be rendered using the same
 * theme as the base page, because most themes are built with the assumption
 * that they control the entire page, so if the CSS for two themes are both
 * loaded for a given page, they may conflict with each other. For example,
 * Bartik is Drupal's default theme, and Seven is Drupal's default
 * administration theme. Depending on whether the "Use the administration theme
 * when editing or creating content" checkbox is checked, the node edit form may
 * be displayed in either theme, but the Ajax response to the Field module's
 * "Add another item" button should be rendered using the same theme as the rest
 * of the page.
 */
class AjaxBasePageNegotiator implements ThemeNegotiatorInterface {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfGenerator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new AjaxBasePageNegotiator.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $token_generator
   *   The CSRF token generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   */
  public function __construct(CsrfTokenGenerator $token_generator, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->csrfGenerator = $token_generator;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $ajax_page_state = $this->requestStack->getCurrentRequest()->request->get('ajax_page_state');
    return !empty($ajax_page_state['theme']) && isset($ajax_page_state['theme_token']);
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $ajax_page_state = $this->requestStack->getCurrentRequest()->request->get('ajax_page_state');
    $theme = $ajax_page_state['theme'];
    $token = $ajax_page_state['theme_token'];

    // Prevent a request forgery from giving a person access to a theme they
    // shouldn't be otherwise allowed to see. However, since everyone is
    // allowed to see the default theme, token validation isn't required for
    // that, and bypassing it allows most use-cases to work even when accessed
    // from the page cache.
    if ($theme === $this->configFactory->get('system.theme')->get('default') || $this->csrfGenerator->validate($token, $theme)) {
      return $theme;
    }
  }

}
