<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\field\formatter\LinkSeparateFormatter.
 *
 * @todo
 * Merge into 'link' formatter once there is a #type like 'item' that
 * can render a compound label and content outside of a form context.
 * http://drupal.org/node/1829202
 */

namespace Drupal\link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'link_separate' formatter.
 *
 * @FieldFormatter(
 *   id = "link_separate",
 *   label = @Translation("Separate link text and URL"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkSeparateFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '80',
      'rel' => '',
      'target' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the link text field value is available, use it for the text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitized token replacement here because $options['html'] is FALSE
        // by default in l().
        $link_title = \Drupal::token()->replace($item->title, array($entity->getEntityTypeId() => $entity), array('sanitize' => FALSE, 'clear' => TRUE));
      }

      // The link_separate formatter has two titles; the link text (as in the
      // field values) and the URL itself. If there is no link text value,
      // $link_title defaults to the URL, so it needs to be unset.
      // The URL version may need to be trimmed as well.
      if (empty($item->title)) {
        $link_title = NULL;
      }
      $url_title = $url->toString();
      if (!empty($settings['trim_length'])) {
        $link_title = truncate_utf8($link_title, $settings['trim_length'], FALSE, TRUE);
        $url_title = truncate_utf8($url_title, $settings['trim_length'], FALSE, TRUE);
      }

      $element[$delta] = array(
        '#theme' => 'link_formatter_link_separate',
        '#title' => $link_title,
        '#url_title' => $url_title,
        '#url' => $url,
      );

      if (!empty($item->_attributes)) {
        // Set our RDFa attributes on the <a> element that is being built.
        $url->setOption('attributes', $item->_attributes);

        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }
    return $element;
  }
}

