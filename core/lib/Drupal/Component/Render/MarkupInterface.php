<?php

/**
 * @file
 * Contains \Drupal\Component\Render\MarkupInterface.
 */

namespace Drupal\Component\Render;

/**
 * Marks an object's __toString() method as returning markup.
 *
 * Objects that implement this interface will not be automatically XSS filtered
 * by the render system or automatically escaped by the theme engine.
 *
 * If there is any risk of the object's __toString() method returning
 * user-entered data that has not been filtered first, it must not be used. If
 * the object that implements this does not perform automatic escaping or
 * filtering itself, then it must be marked as "@internal". For example, Views
 * has the internal ViewsRenderPipelineMarkup object to provide a custom render
 * pipeline in order to render JSON and to fast render fields. By contrast,
 * FormattableMarkup and TranslatableMarkup always sanitize their output when
 * used correctly.
 *
 * If the object is going to be used directly in Twig templates it should
 * implement \Countable so it can be used in if statements.
 *
 * @see \Drupal\Component\Render\MarkupTrait
 * @see \Drupal\Component\Utility\SafeMarkup::isSafe()
 * @see \Drupal\Core\Template\TwigExtension::escapeFilter()
 * @see \Drupal\Component\Render\FormattableMarkup
 * @see \Drupal\Core\StringTranslation\TranslatableMarkup
 * @see \Drupal\views\Render\ViewsRenderPipelineMarkup
 */
interface MarkupInterface extends \JsonSerializable {

  /**
   * Returns markup.
   *
   * @return string
   *   The markup.
   */
  public function __toString();

}
