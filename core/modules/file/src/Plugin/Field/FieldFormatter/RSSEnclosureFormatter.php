<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\RSSEnclosureFormatter.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'file_rss_enclosure' formatter.
 *
 * @FieldFormatter(
 *   id = "file_rss_enclosure",
 *   label = @Translation("RSS enclosure"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class RSSEnclosureFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    // Add the first file as an enclosure to the RSS item. RSS allows only one
    // enclosure per item. See: http://en.wikipedia.org/wiki/RSS_enclosure
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $entity->rss_elements[] = array(
        'key' => 'enclosure',
        'attributes' => array(
          // In RSS feeds, it is necessary to use absolute URLs. The 'url.site'
          // cache context is already associated with RSS feed responses, so it
          // does not need to be specified here.
          'url' => file_create_url($file->getFileUri()),
          'length' => $file->getSize(),
          'type' => $file->getMimeType(),
        ),
      );
    }
    return [];
  }

}
