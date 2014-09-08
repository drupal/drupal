<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedStorageSchema.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the feed schema handler.
 */
class FeedStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['aggregator_feed']['fields']['url']['not null'] = TRUE;
    $schema['aggregator_feed']['fields']['queued']['not null'] = TRUE;
    $schema['aggregator_feed']['fields']['title']['not null'] = TRUE;

    $schema['aggregator_feed']['indexes'] += array(
      'aggregator_feed__url'  => array(array('url', 255)),
      'aggregator_feed__queued' => array('queued'),
    );
    $schema['aggregator_feed']['unique keys'] += array(
      'aggregator_feed__title' => array('title'),
    );

    return $schema;
  }

}
