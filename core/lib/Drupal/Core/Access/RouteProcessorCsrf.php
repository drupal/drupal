<?php

namespace Drupal\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Processes the outbound route to handle the CSRF token.
 */
class RouteProcessorCsrf implements OutboundRouteProcessorInterface, TrustedCallbackInterface {

  use RoutePathGenerationTrait;

  /**
   * Constructs a RouteProcessorCsrf object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $requestStack
   *   The request stack.
   */
  public function __construct(
    protected CsrfTokenGenerator $csrfToken,
    protected ?RequestStack $requestStack = NULL,
  ) {
    if ($requestStack === NULL) {
      @trigger_error('Calling ' . __CLASS__ . ' constructor without the $requestStack argument is deprecated in drupal:11.2.0 and it will be required in drupal:12.0.0. See https://www.drupal.org/project/drupal/issues/3485174', E_USER_DEPRECATED);
      $this->requestStack = \Drupal::service('request_stack');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($route_name, Route $route, array &$parameters, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($route->hasRequirement('_csrf_token')) {
      $path = $this->generateRoutePath($route, $parameters);
      // Adding this to the parameters means it will get merged into the query
      // string when the route is compiled.
      if (!$bubbleable_metadata || $this->requestStack->getCurrentRequest()->getRequestFormat() !== 'html') {
        $parameters['token'] = $this->csrfToken->get($path);
      }
      else {
        // Generate a placeholder and a render array to replace it.
        $placeholder = Crypt::hashBase64($path);
        $placeholder_render_array = [
          '#lazy_builder' => ['route_processor_csrf:renderPlaceholderCsrfToken', [$path]],
        ];
        // Instead of setting an actual CSRF token as the query string, we set
        // the placeholder, which will be replaced at the very last moment. This
        // ensures links with CSRF tokens don't break cacheability.
        $parameters['token'] = $placeholder;
        $bubbleable_metadata->addAttachments(['placeholders' => [$placeholder => $placeholder_render_array]]);
      }
    }
  }

  /**
   * Render API callback: Adds a CSRF token for the given path to the markup.
   *
   * This function is assigned as a #lazy_builder callback.
   *
   * @param string $path
   *   The path to get a CSRF token for.
   *
   * @return array
   *   A renderable array representing the CSRF token.
   */
  public function renderPlaceholderCsrfToken($path) {
    return [
      '#markup' => $this->csrfToken->get($path),
      // Tokens are per session.
      '#cache' => [
        'contexts' => [
          'session',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderPlaceholderCsrfToken'];
  }

}
