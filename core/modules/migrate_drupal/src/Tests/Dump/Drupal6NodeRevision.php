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
        'body' => 'body test rev 2',
        'teaser' => 'teaser test rev 2',
        'log' => 'modified rev 2',
        'timestamp' => 1390095702,
        'format' => 1,
      ))
      ->values(array(
        'nid' => 1,
        'vid' => 4,
        'uid' => 1,
        'title' => 'Test title rev 3',
        'body' => 'body test rev 3',
        'teaser' => 'teaser test rev 3',
        'log' => 'modified rev 3',
        'timestamp' => 1390095703,
        'format' => 1,
      ))
      ->execute();
  }
}
