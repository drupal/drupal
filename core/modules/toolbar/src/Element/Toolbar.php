<?php

/**
 * @file
 * Contains \Drupal\toolbar\Element\Toolbar.
 */

namespace Drupal\toolbar\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element;

/**
 * Provides a render element for the default Drupal toolbar.
 *
 * @RenderElement("toolbar")
 */
class Toolbar extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#pre_render' => array(
        array($class, 'preRenderToolbar'),
      ),
      '#theme' => 'toolbar',
      '#attached' => array(
        'library' => array(
          'toolbar/toolbar',
        ),
      ),
      // Metadata for the toolbar wrapping element.
      '#attributes' => array(
        // The id cannot be simply "toolbar" or it will clash with the
        // simpletest tests listing which produces a checkbox with attribute
        // id="toolbar".
        'id' => 'toolbar-administration',
        'role' => 'group',
        'aria-label' => $this->t('Site administration toolbar'),
      ),
      // Metadata for the administration bar.
      '#bar' => array(
        '#heading' => $this->t('Toolbar items'),
        '#attributes' => array(
          'id' => 'toolbar-bar',
          'role' => 'navigation',
          'aria-label' => $this->t('Toolbar items'),
        ),
      ),
    );
  }

  /**
   * Builds the Toolbar as a structured array ready for drupal_render().
   *
   * Since building the toolbar takes some time, it is done just prior to
   * rendering to ensure that it is built only if it will be displayed.
   *
   * @param array $element
   *  A renderable array.
   *
   * @return array
   *  A renderable array.
   *
   * @see toolbar_page_top().
   */
  public static function preRenderToolbar($element) {
    // Get the configured breakpoints to switch from vertical to horizontal
    // toolbar presentation.
    $breakpoints = static::breakpointManager()->getBreakpointsByGroup('toolbar');
    if (!empty($breakpoints)) {
      $media_queries =  array();
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
    uasort($items, array('\Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));

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
   */
  protected static function breakpointManager() {
    return \Drupal::service('breakpoint.manager');
  }

  /**
   * Wraps the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected static function moduleHandler() {
    return \Drupal::moduleHandler();
  }

}
