<?php

namespace Drupal\system\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for batch routes.
 */
class BatchController implements ContainerInjectionInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new BatchController.
   *
   * @param string $root
   *   The app root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('app.root')
    );
  }

  /**
   * Returns a system batch page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   A \Symfony\Component\HttpFoundation\Response object or render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function batchPage(Request $request) {
    require_once $this->root . '/core/includes/batch.inc';
    $output = _batch_page($request);

    if ($output === FALSE) {
      throw new AccessDeniedHttpException();
    }
    elseif ($output instanceof Response) {
      return $output;
    }
    elseif (isset($output)) {
      $page = [
        '#type' => 'page',
        '#show_messages' => FALSE,
        'content' => $output,
      ];
      return $page;
    }
  }

  /**
   * The _title_callback for the system.batch_page.normal route.
   *
   * @return string
   *   The page title.
   */
  public function batchPageTitle() {
    $current_set = _batch_current_set();
    return !empty($current_set['title']) ? $current_set['title'] : '';
  }

}
