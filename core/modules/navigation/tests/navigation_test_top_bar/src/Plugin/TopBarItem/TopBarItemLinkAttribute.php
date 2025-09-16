<?php

declare(strict_types=1);

namespace Drupal\navigation_test_top_bar\Plugin\TopBarItem;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;

/**
 * Provides a top bar item plugin for testing link attributes in the top bar.
 */
#[TopBarItem(
  id: 'test_item_link_attribute',
  region: TopBarRegion::Actions,
  label: new TranslatableMarkup('Test Item Link Attribute'),
)]
class TopBarItemLinkAttribute extends TopBarItemBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $links = [];
    $featuredLinks['test_link'] = [
      'page_action' => [
        '#theme' => 'top_bar_page_action',
        '#link' => [
          '#type' => 'link',
          '#title' => $this->t('Test link'),
          '#url' => Url::fromRoute('entity.node.canonical', ['node' => 1]),
          '#attributes' => [
            'title' => $this->t('Test link with attributes'),
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => 700]),
          ],
        ],
      ],
      'icon' => [
        'icon_id' => 'database',
      ],
    ];

    return [
      '#theme' => 'top_bar_page_actions',
      '#page_actions' => $links,
      '#featured_page_actions' => $featuredLinks,
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
  }

}
