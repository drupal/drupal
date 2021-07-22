<?php

namespace Drupal\migrate_destination_test\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * A simple migrate source for our tests.
 *
 * @MigrateSource(
 *   id = "migrate_destination_test"
 * )
 */
class MigrateDestinationTestSource extends SourcePluginBase {

  /**
   * The data to import.
   *
   * @var array
   */
  protected $import = [
    ['title' => 'Cat'],
    ['title' => 'Dog'],
    ['title' => 'Monkey'],
  ];

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'title' => $this->t('Title'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['title']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $data = [];
    foreach ($this->import as $row) {
      $data[] = $row;
    }

    return new \ArrayIterator($data);
  }

}
