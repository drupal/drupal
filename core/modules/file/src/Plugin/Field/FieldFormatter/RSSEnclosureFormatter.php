<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'file_rss_enclosure' formatter.
 */
#[FieldFormatter(
  id: 'file_rss_enclosure',
  label: new TranslatableMarkup('RSS enclosure'),
  field_types: [
    'file',
  ],
)]
class RSSEnclosureFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    // Add the first file as an enclosure to the RSS item. RSS allows only one
    // enclosure per item. See: http://wikipedia.org/wiki/RSS_enclosure
    foreach ($this->getEntitiesToView($items, $langcode) as $file) {
      /** @var \Drupal\file\FileInterface $file */
      $entity->rss_elements[] = [
        'key' => 'enclosure',
        'attributes' => [
          // In RSS feeds, it is necessary to use absolute URLs. The 'url.site'
          // cache context is already associated with RSS feed responses, so it
          // does not need to be specified here.
          'url' => $file->createFileUrl(FALSE),
          'length' => $file->getSize(),
          'type' => $file->getMimeType(),
        ],
      ];
    }
    return [];
  }

}
