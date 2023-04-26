<?php

declare(strict_types=1);

namespace Drupal\announcements_feed;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;

/**
 * Defines a class for lazy building render arrays.
 *
 * @internal
 */
final class LazyBuilders implements TrustedCallbackInterface {

  /**
   * Constructs LazyBuilders object.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $elementInfo
   *   Element info.
   */
  public function __construct(
    protected ElementInfoManagerInterface $elementInfo,
  ) {
  }

  /**
   * Render announcements.
   *
   * @return array
   *   Render array.
   */
  public function renderAnnouncements(): array {
    $build = [
      '#type' => 'link',
      '#cache' => [
        'context' => ['user.permissions'],
      ],
      '#title' => t('Announcements'),
      '#url' => Url::fromRoute('announcements_feed.announcement'),
      '#id' => Html::getId('toolbar-item-announcement'),
      '#attributes' => [
        'title' => t('Announcements'),
        'data-drupal-announce-trigger' => '',
        'class' => [
          'toolbar-icon',
          'toolbar-item',
          'toolbar-icon-announce',
          'use-ajax',
          'announce-canvas-link',
          'announce-default',
        ],
        'data-dialog-renderer' => 'off_canvas',
        'data-dialog-type' => 'dialog',
        'data-dialog-options' => Json::encode(
          [
            'announce' => TRUE,
            'width' => '25%',
            'classes' => [
              'ui-dialog' => 'announce-dialog',
              'ui-dialog-titlebar' => 'announce-titlebar',
              'ui-dialog-title' => 'announce-title',
              'ui-dialog-titlebar-close' => 'announce-close',
              'ui-dialog-content' => 'announce-body',
            ],
          ]),
      ],
      '#attached' => [
        'library' => [
          'announcements_feed/drupal.announcements_feed.toolbar',
        ],
      ],
    ];

    // The renderer has already added element defaults by the time the lazy
    // builder is run.
    // @see https://www.drupal.org/project/drupal/issues/2609250
    $build += $this->elementInfo->getInfo('link');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderAnnouncements'];
  }

}
