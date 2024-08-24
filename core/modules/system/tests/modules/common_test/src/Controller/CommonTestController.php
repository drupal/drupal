<?php

declare(strict_types=1);

namespace Drupal\common_test\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for common_test routes.
 */
class CommonTestController {

  /**
   * Returns links to the current page, with and without query strings.
   *
   * Using #type 'link' causes these links to be rendered with the link
   * generator.
   */
  public function typeLinkActiveClass() {
    return [
      'no_query' => [
        '#type' => 'link',
        '#title' => t('Link with no query string'),
        '#url' => Url::fromRoute('<current>'),
        '#options' => [
          'set_active_class' => TRUE,
        ],
      ],
      'with_query' => [
        '#type' => 'link',
        '#title' => t('Link with a query string'),
        '#url' => Url::fromRoute('<current>'),
        '#options' => [
          'query' => [
            'foo' => 'bar',
            'one' => 'two',
          ],
          'set_active_class' => TRUE,
        ],
      ],
      'with_query_reversed' => [
        '#type' => 'link',
        '#title' => t('Link with the same query string in reverse order'),
        '#url' => Url::fromRoute('<current>'),
        '#options' => [
          'query' => [
            'one' => 'two',
            'foo' => 'bar',
          ],
          'set_active_class' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Adds a JavaScript file and a CSS file with a query string appended.
   *
   * @return string
   *   An empty string.
   */
  public function jsAndCssQuerystring() {
    $module_extension_list = \Drupal::service('extension.list.module');
    assert($module_extension_list instanceof ExtensionList);
    $attached = [
      '#attached' => [
        'library' => [
          'node/drupal.node',
        ],
        'css' => [
          $module_extension_list->getPath('node') . '/css/node.admin.css' => [],
          // A relative URI may have a query string.
          '/' . $module_extension_list->getPath('node') . '/node-fake.css?arg1=value1&arg2=value2' => [],
        ],
      ],
    ];
    return \Drupal::service('renderer')->renderRoot($attached);
  }

  /**
   * Prints a destination query parameter.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A new Response object containing a string with the destination query
   *   parameter.
   */
  public function destination() {
    $destination = \Drupal::destination()->getAsArray();
    $output = "The destination: " . Html::escape($destination['destination']);
    return new Response($output);
  }

  /**
   * Returns a response with early rendering in common_test_page_attachments.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A new Response object.
   */
  public function attachments() {
    \Drupal::state()->set('common_test.hook_page_attachments.early_rendering', TRUE);
    $build = [
      '#title' => 'A title',
      'content' => ['#markup' => 'Some content'],
    ];
    return \Drupal::service('main_content_renderer.html')->renderResponse($build, \Drupal::requestStack()->getCurrentRequest(), \Drupal::routeMatch());
  }

}
