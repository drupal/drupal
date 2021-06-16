<?php

namespace Drupal\link\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'link_separate' formatter.
 *
 * @todo https://www.drupal.org/node/1829202 Merge into 'link' formatter once
 *   there is a #type like 'item' that can render a compound label and content
 *   outside of a form context.
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
    return [
      'trim_length' => 80,
      'rel' => '',
      'target' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $entity = $items->getEntity();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);
      $link_title = $url->toString();

      // If the link text field value is available, use it for the text.
      if (empty($settings['url_only']) && !empty($item->title)) {
        // Unsanitized token replacement here because the entire link title
        // gets auto-escaped during link generation in
        // \Drupal\Core\Utility\LinkGenerator::generate().
        $link_title = \Drupal::token()->replace($item->title, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
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
        $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
        $url_title = Unicode::truncate($url_title, $settings['trim_length'], FALSE, TRUE);
      }

      $element[$delta] = [
        '#theme' => 'link_formatter_link_separate',
        '#title' => $link_title,
        '#url_title' => $url_title,
        '#url' => $url,
      ];

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
