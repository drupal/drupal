<?php

declare(strict_types=1);

namespace Drupal\toolbar;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Lazy builder for the announcements toolbar tab.
 *
 * Fills out the placeholder generated in
 * ToolbarHooks::announcementsFeedToolbar().
 *
 * @internal
 */
final class AnnouncementsFeedToolbarLinkBuilder implements TrustedCallbackInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ElementInfoManagerInterface $elementInfo,
  ) {}

  /**
   * Lazy builder callback for rendering the announcements toolbar tab.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function renderAnnouncements(): array {
    $build = [
      '#type' => 'link',
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
      '#title' => $this->t('Announcements'),
      '#url' => Url::fromRoute('announcements_feed.announcement'),
      '#id' => Html::getId('toolbar-item-announcement'),
      '#attributes' => [
        'title' => $this->t('Announcements'),
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
          'toolbar/toolbar.announcements_feed',
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
