<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\field\formatter\RSSCategoryFormatter.
 */

namespace Drupal\taxonomy\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldInterface;
use Drupal\taxonomy\Plugin\field\formatter\TaxonomyFormatterBase;

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
  public function viewElements(EntityInterface $entity, $langcode, FieldInterface $items) {
    // Terms whose target_id is 'autocreate' do not exist yet and
    // $item->entity is not set. Theme such terms as just their name.
    foreach ($items as $item) {
      if ($item->target_id) {
        $value = $item->entity->label();

        $uri = $item->entity->uri();
        $uri['options']['absolute'] = TRUE;
        $domain = url($uri['path'], $uri['options']);
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
