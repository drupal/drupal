<?php

namespace Drupal\taxonomy\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Checks if the vocabulary being migrated is the one used for forums.
 *
 * The forum module is expecting 'taxonomy_forums' as the field name for the
 * forum nodes. The 'forum_vocabulary' source property is evaluated in
 * Drupal\taxonomy\Plugin\migrate\source\d6\Vocabulary and is set to true if
 * the vocabulary vid being migrated is the same as the one in the
 * 'forum_nav_vocabulary' variable on the source site.
 *
 * @MigrateProcessPlugin(
 *   id = "forum_vocabulary"
 * )
 */
class ForumVocabulary extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('forum_vocabulary')) {
      $value = 'taxonomy_forums';
    }
    return $value;
  }

}
