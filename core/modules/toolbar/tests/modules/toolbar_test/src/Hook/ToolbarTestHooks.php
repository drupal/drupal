<?php

declare(strict_types=1);

namespace Drupal\toolbar_test\Hook;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for toolbar_test.
 */
class ToolbarTestHooks {

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar() {
    $items['testing'] = [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'link',
        '#title' => t('Test tab'),
        '#url' => Url::fromRoute('<front>'),
        '#options' => [
          'attributes' => [
            'id' => 'toolbar-tab-testing',
            'title' => t('Test tab'),
          ],
        ],
      ],
      'tray' => [
        '#heading' => t('Test tray'),
        '#wrapper_attributes' => [
          'id' => 'toolbar-tray-testing',
        ],
        'content' => [
          '#theme' => 'item_list',
          '#items' => [
            Link::fromTextAndUrl(t('link 1'), Url::fromRoute('<front>', [], [
              'attributes' => [
                'title' => 'Test link 1 title',
              ],
            ]))->toRenderable(),
            Link::fromTextAndUrl(t('link 2'), Url::fromRoute('<front>', [], [
              'attributes' => [
                'title' => 'Test link 2 title',
              ],
            ]))->toRenderable(),
            Link::fromTextAndUrl(t('link 3'), Url::fromRoute('<front>', [], [
              'attributes' => [
                'title' => 'Test link 3 title',
              ],
            ]))->toRenderable(),
          ],
          '#attributes' => [
            'class' => [
              'toolbar-menu',
            ],
          ],
        ],
      ],
      '#weight' => 50,
    ];
    $items['empty'] = ['#type' => 'toolbar_item'];
    return $items;
  }

}
