<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterCaption.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to display image captions and align images.
 *
 * @Filter(
 *   id = "filter_caption",
 *   title = @Translation("Display image captions and align images"),
 *   description = @Translation("Uses data-caption and data-align attributes on &lt;img&gt; tags to caption and align images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterCaption extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-caption') !== FALSE || stristr($text, 'data-align') !== FALSE) {
      $caption_found = FALSE;
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-caption or @data-align]') as $node) {
        $caption = NULL;
        $align = NULL;

        // Retrieve, then remove the data-caption and data-align attributes.
        if ($node->hasAttribute('data-caption')) {
          $caption = String::checkPlain($node->getAttribute('data-caption'));
          $node->removeAttribute('data-caption');
          // Sanitize caption: decode HTML encoding, limit allowed HTML tags;
          // only allow inline tags that are allowed by default, plus <br>.
          $caption = String::decodeEntities($caption);
          $caption = Xss::filter($caption, array('a', 'em', 'strong', 'cite', 'code', 'br'));
          // The caption must be non-empty.
          if (Unicode::strlen($caption) === 0) {
            $caption = NULL;
          }
        }
        if ($node->hasAttribute('data-align')) {
          $align = $node->getAttribute('data-align');
          $node->removeAttribute('data-align');
          // Only allow 3 values: 'left', 'center' and 'right'.
          if (!in_array($align, array('left', 'center', 'right'))) {
            $align = NULL;
          }
        }

        // Don't transform the HTML if there isn't a caption after validation.
        if ($caption === NULL) {
          // If there is a valid alignment, then transform the data-align
          // attribute to a corresponding alignment class.
          if ($align !== NULL) {
            $classes = $node->getAttribute('class');
            $classes = (strlen($classes) > 0) ? explode(' ', $classes) : array();
            $classes[] = 'align-' . $align;
            $node->setAttribute('class', implode(' ', $classes));
          }
          continue;
        }
        else {
          $caption_found = TRUE;
        }

        // Given the updated node, caption and alignment: re-render it with a
        // caption.
        $filter_caption = array(
          '#theme' => 'filter_caption',
          '#node' => $node->C14N(),
          '#tag' => $node->tagName,
          '#caption' => $caption,
          '#align' => $align,
        );
        $altered_html = drupal_render($filter_caption);

        // Load the altered HTML into a new DOMDocument and retrieve the element.
        $updated_node = Html::load($altered_html)->getElementsByTagName('body')
          ->item(0)
          ->childNodes
          ->item(0);

        // Import the updated node from the new DOMDocument into the original
        // one, importing also the child nodes of the updated node.
        $updated_node = $dom->importNode($updated_node, TRUE);
        // Finally, replace the original image node with the new image node!
        $node->parentNode->replaceChild($updated_node, $node);
      }

      $result->setProcessedText(Html::serialize($dom));

      if ($caption_found) {
        $result->addAssets(array(
          'library' => array(
            'filter/caption',
          ),
        ));
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can add image captions and align images left, right or centered. Examples:</p>
        <ul>
          <li>Caption an image: <code>&lt;img src="" data-caption="This is a caption" /&gt;</code></li>
          <li>Align an image: <code>&lt;img src="" data-align="center" /&gt;</code></li>
          <li>Caption & align an image: <code>&lt;img src="" data-caption="Alpaca" data-align="right" /&gt;</code></li>
        </ul>');
    }
    else {
      return $this->t('You can caption (data-caption="Text") and align images (data-align="center"), but also video, blockquotes, and so on.');
    }
  }
}
