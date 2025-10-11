<?php

declare(strict_types=1);

namespace Drupal\block_content\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Theme hook implementations for block_content.
 */
class BlockContentThemeHooks {

  public function __construct(
    protected readonly RequestStack $requestStack,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for entity_add_list.
   *
   * Adds query parameters to the add links on the block add page so they are
   * forwarded to the add form, this allows a theme to be set automatically
   * on the block.
   *
   * @see \Drupal\block_content\Controller\BlockContentController::addForm()
   */
  #[Hook('preprocess_entity_add_list')]
  public function preprocessEntityAddList(&$variables): void {
    $query = $this->requestStack->getCurrentRequest()->query->all();
    if (count($query) === 0) {
      return;
    }

    foreach ($variables['bundles'] as $bundleId => $bundle) {
      if ($bundle['add_link'] instanceof Link) {
        $url = $bundle['add_link']->getUrl();
        if ($url->getRouteName() !== 'entity.block_content.add_form') {
          continue;
        }
        $url->setOption('query', $query);
        $variables['bundles'][$bundleId]['add_link']->setUrl($url);
      }
    }
  }

}
