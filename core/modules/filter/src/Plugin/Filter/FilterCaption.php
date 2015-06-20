<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterCaption.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to caption elements.
 *
 * When used in combination with the filter_align filter, this must run last.
 *
 * @Filter(
 *   id = "filter_caption",
 *   title = @Translation("Caption images"),
 *   description = @Translation("Uses a <code>data-caption</code> attribute on <code>&lt;img&gt;</code> tags to caption images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterCaption extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-caption') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-caption]') as $node) {
        // Read the data-caption attribute's value, then delete it.
        $caption = SafeMarkup::checkPlain($node->getAttribute('data-caption'));
        $node->removeAttribute('data-caption');

        // Sanitize caption: decode HTML encoding, limit allowed HTML tags; only
        // allow inline tags that are allowed by default, plus <br>.
        $caption = Html::decodeEntities($caption);
        $caption = Xss::filter($caption, array('a', 'em', 'strong', 'cite', 'code', 'br'));

        // The caption must be non-empty.
        if (Unicode::strlen($caption) === 0) {
          continue;
        }

        // Given the updated node and caption: re-render it with a caption, but
        // bubble up the value of the class attribute of the captioned element,
        // this allows it to collaborate with e.g. the filter_align filter.
        $classes = $node->getAttribute('class');
        $node->removeAttribute('class');
        $filter_caption = array(
          '#theme' => 'filter_caption',
          '#node' => SafeMarkup::set($node->C14N()),
          '#tag' => $node->tagName,
          '#caption' => $caption,
          '#classes' => $classes,
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

      $result->setProcessedText(Html::serialize($dom))
        ->addAttachments(array(
          'library' => array(
            'filter/caption',
          ),
        ));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>You can caption images, videos, blockquotes, and so on. Examples:</p>
        <ul>
            <li><code>&lt;img src="" data-caption="This is a caption" /&gt;</code></li>
            <li><code>&lt;video src="" data-caption="The Drupal Dance" /&gt;</code></li>
            <li><code>&lt;blockquote data-caption="Dries Buytaert"&gt;Drupal is awesome!&lt;/blockquote&gt;</code></li>
            <li><code>&lt;code data-caption="Hello world in JavaScript."&gt;alert("Hello world!");&lt;/code&gt;</code></li>
        </ul>');
    }
    else {
      return $this->t('You can caption images (<code>data-caption="Text"</code>), but also videos, blockquotes, and so on.');
    }
  }

}
