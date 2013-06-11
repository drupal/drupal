<?php

/**
 * @file
 * Contains \Drupal\common_test\Controller\CommonTestController.
 */

namespace Drupal\common_test\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for common_test routes.
 */
class CommonTestController implements ControllerInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Returns links to the current page, with and without query strings.
   *
   * Using #type causes these links to be rendered with l().
   */
  public function lActiveClass() {
    return array(
      'no_query' => array(
        '#type' => 'link',
        '#title' => t('Link with no query string'),
        '#href' => current_path(),
      ),
      'with_query' => array(
        '#type' => 'link',
        '#title' => t('Link with a query string'),
        '#href' => current_path(),
        '#options' => array(
          'query' => array(
            'foo' => 'bar',
            'one' => 'two',
          ),
        ),
      ),
      'with_query_reversed' => array(
        '#type' => 'link',
        '#title' => t('Link with the same query string in reverse order'),
        '#href' => current_path(),
        '#options' => array(
          'query' => array(
            'one' => 'two',
            'foo' => 'bar',
          ),
        ),
      ),
    );
  }

  /**
   * Returns links to the current page, with and without query strings.
   *
   * Using #theme causes these links to be rendered with theme_link().
   */
  public function themeLinkActiveClass() {
    return array(
      'no_query' => array(
        '#theme' => 'link',
        '#text' => t('Link with no query string'),
        '#path' => current_path(),
      ),
      'with_query' => array(
        '#theme' => 'link',
        '#text' => t('Link with a query string'),
        '#path' => current_path(),
        '#options' => array(
          'query' => array(
            'foo' => 'bar',
            'one' => 'two',
          ),
        ),
      ),
      'with_query_reversed' => array(
        '#theme' => 'link',
        '#text' => t('Link with the same query string in reverse order'),
        '#path' => current_path(),
        '#options' => array(
          'query' => array(
            'one' => 'two',
            'foo' => 'bar',
          ),
        ),
      ),
    );
  }

}
