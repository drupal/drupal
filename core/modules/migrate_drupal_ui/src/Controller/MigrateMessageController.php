<?php

namespace Drupal\migrate_drupal_ui\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\migrate\Controller\MigrateMessageController as BaseMessageController;

/**
 * Provides controller methods for the Message form.
 */
class MigrateMessageController extends BaseMessageController {

  /**
   * Displays an overview of migrate messages.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function overview(): array {
    $build = parent::overview();

    $description['help'] = [
      '#type' => 'item',
      '#markup' => $this->t('The upgrade process may log messages about steps that require user action or errors. This page allows you to view these messages'),
    ];
    $description['info'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl($this->t('Review the detailed upgrade log.'), Url::fromRoute('migrate_drupal_ui.log'))
        ->toString(),
    ];
    return $description + $build;
  }

}
