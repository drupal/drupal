<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\RSSCategoryFormatter.
 */

namespace Drupal\taxonomy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'taxonomy_term_reference_rss_category' formatter.
 *
 * @FieldFormatter(
 *   id = "taxonomy_term_reference_rss_category",
 *   label = @Translation("RSS category"),
 *   field_types = {
 *     "taxonomy_term_reference"
 *   }
 * )
 */
class RSSCategoryFormatter extends TaxonomyFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $entity = $items->getEntity();

    // Terms whose target_id is 'autocreate' do not exist yet and
    // $item->entity is not set. Theme such terms as just their name.
    foreach ($items as $item) {
      if ($item->target_id) {
        $value = $item->entity->label();

        $domain = $item->entity->url('canonical', array('absolute' => TRUE));
      }
      else {
        $value = $item->entity->label();
        $domain = '';
      }
      $entity->rss_elements[] = array(
        'key' => 'category',
        'value' => $value,
        'attributes' => array(
          'domain' => $domain,
        ),
      );
    }
  }

}
