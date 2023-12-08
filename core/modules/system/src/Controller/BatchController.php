<?php

namespace Drupal\system\Controller;

use Drupal\Core\Batch\BatchStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for batch routes.
 */
class BatchController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new BatchController.
   */
  public function __construct(
    protected string $root,
    protected BatchStorageInterface $batchStorage
  ) {
    require_once $this->root . '/core/includes/batch.inc';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->getParameter('app.root'),
      $container->get('batch.storage'),
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
    $output = _batch_page($request);

    if ($output === FALSE) {
      throw new AccessDeniedHttpException();
    }
    elseif ($output instanceof Response) {
      return $output;
    }
    elseif (isset($output)) {
      // Directly render a status message placeholder without any messages.
      // Messages are not intended to be show on the batch page, but in the
      // event an error in a AJAX callback the messages will be displayed.
      // @todo Remove in https://drupal.org/i/3396099.
      $output['batch_messages'] = [
        '#theme' => 'status_messages',
        '#message_list' => [],
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
      $title = $output['#title'] ?? NULL;
      $page = [
        '#type' => 'page',
        '#title' => $title,
        '#show_messages' => FALSE,
        'content' => $output,
      ];

      // Also inject title as a page header (if available).
      if ($title) {
        $page['header'] = [
          '#type' => 'page_title',
          '#title' => $title,
        ];
      }

      return $page;
    }
  }

  /**
   * The _title_callback for the system.batch_page.html route.
   *
   * @return string
   *   The page title.
   */
  public function batchPageTitle(Request $request) {
    $batch = &batch_get();

    if (!($request_id = $request->query->get('id'))) {
      return '';
    }

    // Retrieve the current state of the batch.
    if (!$batch) {
      $batch = $this->batchStorage->load($request_id);
    }

    if (!$batch) {
      return '';
    }

    $current_set = _batch_current_set();
    return !empty($current_set['title']) ? $current_set['title'] : '';
  }

}
