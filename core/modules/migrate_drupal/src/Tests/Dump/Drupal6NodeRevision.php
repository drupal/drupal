<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Dump\Drupal6NodeRevision.
 */


namespace Drupal\migrate_drupal\Tests\Dump;

class Drupal6NodeRevision extends Drupal6DumpBase {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $this->database->insert('node_revisions')->fields(
      array(
        'nid',
        'vid',
        'uid',
        'title',
        'body',
        'teaser',
        'log',
        'timestamp',
        'format',
      ))
      ->values(array(
        'nid' => 1,
        'vid' => 2,
        'uid' => 2,
        'title' => 'Test title rev 2',
        'body' => 'test rev 2',
        'teaser' => 'test rev 2',
        'log' => '',
        'timestamp' => 1390095701,
        'format' => 1,
      ))
      ->execute();
  }
}
