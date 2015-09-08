<?php

/**
 * @file
 * Contains \Drupal\dynamic_page_cache_test\DynamicPageCacheTestController.
 */

namespace Drupal\dynamic_page_cache_test;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for dynamic_page_cache_test routes.
 */
class DynamicPageCacheTestController {

  /**
   * A route returning a Response object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response object.
   */
  public function response() {
    return new Response('foobar');
  }

  /**
   * A route returning a CacheableResponse object.
   *
   * @return \Drupal\Core\Cache\CacheableResponseInterface
   *   A CacheableResponseInterface object.
   */
  public function cacheableResponse() {
    $user = User::load(1);
    $response = new CacheableResponse($user->label());
    $response->addCacheableDependency($user);
    return $response;
  }

  /**
   * A route returning a render array (without cache contexts, so cacheable).
   *
   * @return array
   *   A render array.
   */
  public function html() {
    return [
      'content' => [
        '#markup' => 'Hello world.',
      ],
    ];
  }

  /**
   * A route returning a render array (with cache contexts, so cacheable).
   *
   * @return array
   *   A render array.
   *
   * @see html()
   */
  public function htmlWithCacheContexts() {
    $build = $this->html();
    $build['dynamic_part'] = [
      '#markup' => SafeMarkup::format('Hello there, %animal.', ['%animal' => \Drupal::requestStack()->getCurrentRequest()->query->get('animal')]),
      '#cache' => [
        'contexts' => [
          'url.query_args:animal',
        ],
      ],
    ];
    return $build;
  }

  /**
   * A route returning a render array (with max-age=0, so uncacheable)
   *
   * @return array
   *   A render array.
   *
   * @see html()
   */
  public function htmlUncacheableMaxAge() {
    $build = $this->html();
    $build['very_dynamic_part'] = [
      '#markup' => 'Drupal cannot handle the awesomeness of llamas.',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $build;
  }

  /**
   * A route returning a render array (with 'user' context, so uncacheable)
   *
   * @return array
   *   A render array.
   *
   * @see html()
   */
  public function htmlUncacheableContexts() {
    $build = $this->html();
    $build['very_dynamic_part'] = [
      '#markup' => 'Drupal cannot handle the awesomeness of llamas.',
      '#cache' => [
        'contexts' => [
          'user',
        ],
      ],
    ];
    return $build;
  }

  /**
   * A route returning a render array (with max-age=0, so uncacheable)
   *
   * @return array
   *   A render array.
   *
   * @see html()
   */
  public function htmlUncacheableTags() {
    $build = $this->html();
    $build['very_dynamic_part'] = [
      '#markup' => 'Drupal cannot handle the awesomeness of llamas.',
      '#cache' => [
        'tags' => [
          'current-temperature',
        ],
      ],
    ];
    return $build;
  }

}
