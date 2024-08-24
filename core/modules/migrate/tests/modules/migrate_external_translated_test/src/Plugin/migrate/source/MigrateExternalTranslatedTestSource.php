<?php

declare(strict_types=1);

namespace Drupal\migrate_external_translated_test\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * A simple migrate source for our tests.
 *
 * @MigrateSource(
 *   id = "migrate_external_translated_test",
 *   source_module = "migrate_external_translated_test"
 * )
 */
class MigrateExternalTranslatedTestSource extends SourcePluginBase {

  /**
   * The data to import.
   *
   * @var array
   */
  protected $import = [
    ['name' => 'cat', 'title' => 'Cat', 'lang' => 'English'],
    ['name' => 'cat', 'title' => 'Chat', 'lang' => 'French'],
    ['name' => 'cat', 'title' => 'es - Cat', 'lang' => 'Spanish'],
    ['name' => 'dog', 'title' => 'Dog', 'lang' => 'English'],
    ['name' => 'dog', 'title' => 'fr - Dog', 'lang' => 'French'],
    ['name' => 'monkey', 'title' => 'Monkey', 'lang' => 'English'],
  ];

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Unique name'),
      'title' => $this->t('Title'),
      'lang' => $this->t('Language'),
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
    $ids['name']['type'] = 'string';
    if (!$this->configuration['default_lang']) {
      $ids['lang']['type'] = 'string';
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $data = [];

    // Keep the rows with the right languages.
    $want_default = $this->configuration['default_lang'];
    foreach ($this->import as $row) {
      $is_english = $row['lang'] == 'English';
      if ($want_default == $is_english) {
        $data[] = $row;
      }
    }

    return new \ArrayIterator($data);
  }

}
