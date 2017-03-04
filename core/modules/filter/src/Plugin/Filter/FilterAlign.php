<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to align elements.
 *
 * @Filter(
 *   id = "filter_align",
 *   title = @Translation("Align images"),
 *   description = @Translation("Uses a <code>data-align</code> attribute on <code>&lt;img&gt;</code> tags to align images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterAlign extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-align') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-align]') as $node) {
        // Read the data-align attribute's value, then delete it.
        $align = $node->getAttribute('data-align');
        $node->removeAttribute('data-align');

        // If one of the allowed alignments, add the corresponding class.
        if (in_array($align, ['left', 'center', 'right'])) {
          $classes = $node->getAttribute('class');
          $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
          $classes[] = 'align-' . $align;
          $node->setAttribute('class', implode(' ', $classes));
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can align images, videos, blockquotes and so on to the left, right or center. Examples:</p>
        <ul>
          <li>Align an image to the left: <code>&lt;img src="" data-align="left" /&gt;</code></li>
          <li>Align an image to the center: <code>&lt;img src="" data-align="center" /&gt;</code></li>
          <li>Align an image to the right: <code>&lt;img src="" data-align="right" /&gt;</code></li>
          <li>… and you can apply this to other elements as well: <code>&lt;video src="" data-align="center" /&gt;</code></li>
        </ul>');
    }
    else {
      return $this->t('You can align images (<code>data-align="center"</code>), but also videos, blockquotes, and so on.');
    }
  }

}
