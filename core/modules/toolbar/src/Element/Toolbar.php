<?php

namespace Drupal\toolbar\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;
use Drupal\Core\Render\Element;

/**
 * Provides a render element for the default Drupal toolbar.
 */
#[RenderElement('toolbar')]
class Toolbar extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [static::class, 'preRenderToolbar'],
      ],
      '#theme' => 'toolbar',
      '#attached' => [
        'library' => [
          'toolbar/toolbar',
        ],
      ],
      // Metadata for the toolbar wrapping element.
      '#attributes' => [
        'id' => 'toolbar-administration',
        'role' => 'group',
        'aria-label' => $this->t('Site administration toolbar'),
      ],
      // Metadata for the administration bar.
      '#bar' => [
        '#heading' => $this->t('Toolbar items'),
        '#attributes' => [
          'id' => 'toolbar-bar',
          'role' => 'navigation',
          'aria-label' => $this->t('Toolbar items'),
        ],
      ],
    ];
  }

  /**
   * Builds the Toolbar as a structured array ready for rendering.
   *
   * Since building the toolbar takes some time, it is done just prior to
   * rendering to ensure that it is built only if it will be displayed.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   A renderable array.
   *
   * @see toolbar_page_top()
   */
  public static function preRenderToolbar($element) {
    // Get the configured breakpoints to switch from vertical to horizontal
    // toolbar presentation.
    $breakpoints = static::breakpointManager()->getBreakpointsByGroup('toolbar');
    if (!empty($breakpoints)) {
      $media_queries = [];
      foreach ($breakpoints as $id => $breakpoint) {
        $media_queries[$id] = $breakpoint->getMediaQuery();
      }

      $element['#attached']['drupalSettings']['toolbar']['breakpoints'] = $media_queries;
    }

    $module_handler = static::moduleHandler();
    // Get toolbar items from all modules that implement hook_toolbar().
    $items = $module_handler->invokeAll('toolbar');
    // Allow for altering of hook_toolbar().
    $module_handler->alter('toolbar', $items);
    // Sort the children.
    uasort($items, ['\Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    // Merge in the original toolbar values.
    $element = array_merge($element, $items);

    // Assign each item a unique ID, based on its key.
    foreach (Element::children($element) as $key) {
      $element[$key]['#id'] = Html::getId('toolbar-item-' . $key);
    }

    return $element;
  }

  /**
   * Wraps the breakpoint manager.
   *
   * @return \Drupal\breakpoint\BreakpointManagerInterface
   *   The breakpoint manager service.
   */
  protected static function breakpointManager() {
    return \Drupal::service('breakpoint.manager');
  }

  /**
   * Wraps the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  protected static function moduleHandler() {
    return \Drupal::moduleHandler();
  }

}
