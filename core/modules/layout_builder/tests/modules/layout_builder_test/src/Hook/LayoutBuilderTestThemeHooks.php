<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Component\Uuid\UuidInterface;

/**
 * Theme hook implementations for layout_builder_test.
 */
class LayoutBuilderTestThemeHooks {

  public function __construct(
    protected readonly UuidInterface $uuid,
  ) {}

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    return [
      'block__preview_aware_block' => [
        'base hook' => 'block',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for one-column layout template.
   */
  #[Hook('preprocess_layout__onecol')]
  public function layoutOneCol(&$vars): void {
    if (!empty($vars['content']['#entity'])) {
      $vars['content']['content'][$this->uuid->generate()] = [
        '#type' => 'markup',
        '#markup' => sprintf('Yes, I can access the %s', $vars['content']['#entity']->label()),
      ];
    }
  }

  /**
   * Implements hook_preprocess_HOOK() for two-column layout template.
   */
  #[Hook('preprocess_layout__twocol_section')]
  public function layoutTwocolSection(&$vars): void {
    if (!empty($vars['content']['#entity'])) {
      $vars['content']['first'][$this->uuid->generate()] = [
        '#type' => 'markup',
        '#markup' => sprintf('Yes, I can access the entity %s in two column', $vars['content']['#entity']->label()),
      ];
    }
  }

}
