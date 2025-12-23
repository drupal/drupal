<?php

namespace Drupal\contextual\Hook;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for contextual.
 */
class ContextualHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    $items = [];
    $items['contextual'] = ['#cache' => ['contexts' => ['user.permissions']]];
    if (!\Drupal::currentUser()->hasPermission('access contextual links')) {
      return $items;
    }
    $items['contextual'] += [
      '#type' => 'toolbar_item',
      'tab' => [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $this->t('Edit'),
        '#attributes' => [
          'class' => [
            'toolbar-icon',
            'toolbar-icon-edit',
          ],
          'aria-pressed' => 'false',
          'type' => 'button',
        ],
      ],
      '#wrapper_attributes' => [
        'class' => [
          'hidden',
          'contextual-toolbar-tab',
        ],
      ],
      '#attached' => [
        'library' => [
          'contextual/drupal.contextual-toolbar',
        ],
      ],
    ];
    return $items;
  }

  /**
   * Implements hook_page_attachments().
   *
   * Adds the drupal.contextual-links library to the page for any user who has
   * the 'access contextual links' permission.
   *
   * @see contextual_preprocess()
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    if (!\Drupal::currentUser()->hasPermission('access contextual links')) {
      return;
    }
    $page['#attached']['library'][] = 'contextual/drupal.contextual-links';
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.contextual':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Contextual links module gives users with the <em>Use contextual links</em> permission quick access to tasks associated with certain areas of pages on your site. For example, a menu displayed as a block has links to edit the menu and configure the block. For more information, see the <a href=":contextual">online documentation for the Contextual Links module</a>.', [':contextual' => 'https://www.drupal.org/docs/8/core/modules/contextual']) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Displaying contextual links') . '</dt>';
        $sample_picture = [
          '#theme' => 'image',
          '#uri' => 'core/misc/icons/bebebe/pencil.svg',
          '#alt' => $this->t('contextual links button'),
        ];
        $sample_picture = \Drupal::service('renderer')->render($sample_picture);
        $output .= '<dd>' . $this->t('Contextual links for an area on a page are displayed using a contextual links button. Hovering over the area of interest will temporarily make the contextual links button visible (which looks like a pencil in most themes, and is normally displayed in the upper right corner of the area). The icon typically looks like this: @picture Once the contextual links button for the area of interest is visible, click the button to display the links.', ['@picture' => $sample_picture]) . '</dd>';
        $output .= '</dl>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_contextual_links_view_alter().
   *
   * @see \Drupal\contextual\Plugin\views\field\ContextualLinks::render()
   */
  #[Hook('contextual_links_view_alter')]
  public function contextualLinksViewAlter(&$element, $items): void {
    if (isset($element['#contextual_links']['contextual'])) {
      $encoded_links = $element['#contextual_links']['contextual']['metadata']['contextual-views-field-links'];
      $element['#links'] = Json::decode(rawurldecode($encoded_links));
    }
  }

}
