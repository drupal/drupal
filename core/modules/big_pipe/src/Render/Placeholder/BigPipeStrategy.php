<?php

namespace Drupal\big_pipe\Render\Placeholder;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the BigPipe placeholder strategy, to send HTML in chunks.
 *
 * First: the BigPipe placeholder strategy only activates if the current request
 * is associated with a session. Without a session, it is assumed this response
 * is not actually dynamic: if none of the placeholders show session-dependent
 * information, then none of the placeholders are uncacheable or poorly
 * cacheable, which means the Page Cache (for anonymous users) can deal with it.
 * In other words: BigPipe works for all authenticated users and for anonymous
 * users that have a session (typical example: a shopping cart).
 *
 * (This is the default, and other modules can subclass this placeholder
 * strategy to have different rules for enabling BigPipe.)
 *
 * The BigPipe placeholder strategy actually consists of two substrategies,
 * depending on whether the current session is in a browser with JavaScript
 * enabled or not:
 * 1. with JavaScript enabled: #attached[big_pipe_js_placeholders]. Their
 *    replacements are streamed at the end of the page: chunk 1 is the entire
 *    page until the closing </body> tag, chunks 2 to (N-1) are replacement
 *    values for the placeholders, chunk N is </body> and everything after it.
 * 2. with JavaScript disabled: #attached[big_pipe_nojs_placeholders]. Their
 *    replacements are streamed in situ: chunk 1 is the entire page until the
 *    first no-JS BigPipe placeholder, chunk 2 is the replacement for that
 *    placeholder, chunk 3 is the chunk from after that placeholder until the
 *    next no-JS BigPipe placeholder, et cetera.
 *
 * JS BigPipe placeholders are preferred because they result in better perceived
 * performance: the entire page can be sent, minus the placeholders. But it
 * requires JavaScript.
 *
 * No-JS BigPipe placeholders result in more visible blocking: only the part of
 * the page can be sent until the first placeholder, after it is rendered until
 * the second, et cetera. (In essence: multiple flushes.)
 *
 * Finally, both of those substrategies can also be combined: some placeholders
 * live in places that cannot be efficiently replaced by JavaScript, for example
 * CSRF tokens in URLs. Using no-JS BigPipe placeholders in those cases allows
 * the first part of the page (until the first no-JS BigPipe placeholder) to be
 * sent sooner than when they would be replaced using SingleFlushStrategy, which
 * would prevent anything from being sent until all those non-HTML placeholders
 * would have been replaced.
 *
 * See \Drupal\big_pipe\Render\BigPipe for detailed documentation on how those
 * different placeholders are actually replaced.
 *
 * @see \Drupal\big_pipe\Render\BigPipe
 */
class BigPipeStrategy implements PlaceholderStrategyInterface {

  /**
   * BigPipe no-JS cookie name.
   */
  const NOJS_COOKIE = 'big_pipe_nojs';

  /**
   * The session configuration.
   *
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new BigPipeStrategy class.
   *
   * @param \Drupal\Core\Session\SessionConfigurationInterface $session_configuration
   *   The session configuration.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(SessionConfigurationInterface $session_configuration, RequestStack $request_stack, RouteMatchInterface $route_match) {
    $this->sessionConfiguration = $session_configuration;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {
    $request = $this->requestStack->getCurrentRequest();

    // @todo remove this check when https://www.drupal.org/node/2367555 lands.
    if (!$request->isMethodCacheable()) {
      return [];
    }

    // Routes can opt out from using the BigPipe HTML delivery technique.
    if ($this->routeMatch->getRouteObject()->getOption('_no_big_pipe')) {
      return [];
    }

    if (!$this->sessionConfiguration->hasSession($request)) {
      return [];
    }

    return $this->doProcessPlaceholders($placeholders);
  }

  /**
   * Transforms placeholders to BigPipe placeholders, either no-JS or JS.
   *
   * @param array $placeholders
   *   The placeholders to process.
   *
   * @return array
   *   The BigPipe placeholders.
   */
  protected function doProcessPlaceholders(array $placeholders) {
    $overridden_placeholders = [];
    foreach ($placeholders as $placeholder => $placeholder_elements) {
      // BigPipe uses JavaScript and the DOM to find the placeholder to replace.
      // This means finding the placeholder to replace must be efficient. Most
      // placeholders are HTML, which we can find efficiently thanks to the
      // querySelector API. But some placeholders are HTML attribute values or
      // parts thereof, and potentially even plain text in DOM text nodes. For
      // BigPipe's JavaScript to find those placeholders, it would need to
      // iterate over all DOM text nodes. This is highly inefficient. Therefore,
      // the BigPipe placeholder strategy only converts HTML placeholders into
      // BigPipe placeholders. The other placeholders need to be replaced on the
      // server, not via BigPipe.
      // @see \Drupal\Core\Access\RouteProcessorCsrf::renderPlaceholderCsrfToken()
      // @see \Drupal\Core\Form\FormBuilder::renderFormTokenPlaceholder()
      // @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
      if (static::placeholderIsAttributeSafe($placeholder)) {
        $overridden_placeholders[$placeholder] = static::createBigPipeNoJsPlaceholder($placeholder, $placeholder_elements, TRUE);
      }
      else {
        // If the current request/session doesn't have JavaScript, fall back to
        // no-JS BigPipe.
        if ($this->requestStack->getCurrentRequest()->cookies->has(static::NOJS_COOKIE)) {
          $overridden_placeholders[$placeholder] = static::createBigPipeNoJsPlaceholder($placeholder, $placeholder_elements, FALSE);
        }
        else {
          $overridden_placeholders[$placeholder] = static::createBigPipeJsPlaceholder($placeholder, $placeholder_elements);
        }
        $overridden_placeholders[$placeholder]['#cache']['contexts'][] = 'cookies:' . static::NOJS_COOKIE;
      }
    }

    return $overridden_placeholders;
  }

  /**
   * Determines whether the given placeholder is attribute-safe or not.
   *
   * @param string $placeholder
   *   A placeholder.
   *
   * @return bool
   *   Whether the placeholder is safe for use in an HTML attribute (in case
   *   it's a placeholder for an HTML attribute value or a subset of it).
   */
  protected static function placeholderIsAttributeSafe($placeholder) {
    assert(is_string($placeholder));
    return $placeholder[0] !== '<' || $placeholder !== Html::normalize($placeholder);
  }

  /**
   * Creates a BigPipe JS placeholder.
   *
   * @param string $original_placeholder
   *   The original placeholder.
   * @param array $placeholder_render_array
   *   The render array for a placeholder.
   *
   * @return array
   *   The resulting BigPipe JS placeholder render array.
   */
  protected static function createBigPipeJsPlaceholder($original_placeholder, array $placeholder_render_array) {
    $big_pipe_placeholder_id = static::generateBigPipePlaceholderId($original_placeholder, $placeholder_render_array);

    return [
      '#markup' => '<span data-big-pipe-placeholder-id="' . Html::escape($big_pipe_placeholder_id) . '"></span>',
      '#cache' => [
        'max-age' => 0,
        'contexts' => [
          'session.exists',
        ],
      ],
      '#attached' => [
        'library' => [
          'big_pipe/big_pipe',
        ],
        // Inform BigPipe' JavaScript known BigPipe placeholder IDs.
        'drupalSettings' => [
          'bigPipePlaceholderIds' => [$big_pipe_placeholder_id => TRUE],
        ],
        'big_pipe_placeholders' => [
          Html::escape($big_pipe_placeholder_id) => $placeholder_render_array,
        ],
      ],
    ];
  }

  /**
   * Creates a BigPipe no-JS placeholder.
   *
   * @param string $original_placeholder
   *   The original placeholder.
   * @param array $placeholder_render_array
   *   The render array for a placeholder.
   * @param bool $placeholder_must_be_attribute_safe
   *   Whether the placeholder must be safe for use in an HTML attribute (in
   *   case it's a placeholder for an HTML attribute value or a subset of it).
   *
   * @return array
   *   The resulting BigPipe no-JS placeholder render array.
   */
  protected static function createBigPipeNoJsPlaceholder($original_placeholder, array $placeholder_render_array, $placeholder_must_be_attribute_safe = FALSE) {
    if (!$placeholder_must_be_attribute_safe) {
      $big_pipe_placeholder = '<span data-big-pipe-nojs-placeholder-id="' . Html::escape(static::generateBigPipePlaceholderId($original_placeholder, $placeholder_render_array)) . '"></span>';
    }
    else {
      $big_pipe_placeholder = 'big_pipe_nojs_placeholder_attribute_safe:' . Html::escape($original_placeholder);
    }

    return [
      '#markup' => $big_pipe_placeholder,
      '#cache' => [
        'max-age' => 0,
        'contexts' => [
          'session.exists',
        ],
      ],
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          $big_pipe_placeholder => $placeholder_render_array,
        ],
      ],
    ];
  }

  /**
   * Generates a BigPipe placeholder ID.
   *
   * @param string $original_placeholder
   *   The original placeholder.
   * @param array $placeholder_render_array
   *   The render array for a placeholder.
   *
   * @return string
   *   The generated BigPipe placeholder ID.
   */
  protected static function generateBigPipePlaceholderId($original_placeholder, array $placeholder_render_array) {
    // Generate a BigPipe placeholder ID (to be used by BigPipe's JavaScript).
    // @see \Drupal\Core\Render\PlaceholderGenerator::createPlaceholder()
    if (isset($placeholder_render_array['#lazy_builder'])) {
      $callback = $placeholder_render_array['#lazy_builder'][0];
      $arguments = $placeholder_render_array['#lazy_builder'][1];
      $token = Crypt::hashBase64(serialize($placeholder_render_array));
      return UrlHelper::buildQuery(['callback' => $callback, 'args' => $arguments, 'token' => $token]);
    }
    // When the placeholder's render array is not using a #lazy_builder,
    // anything could be in there: only #lazy_builder has a strict contract that
    // allows us to create a more sane selector. Therefore, simply the original
    // placeholder into a usable placeholder ID, at the cost of it being obtuse.
    else {
      return Html::getId($original_placeholder);
    }
  }

}
