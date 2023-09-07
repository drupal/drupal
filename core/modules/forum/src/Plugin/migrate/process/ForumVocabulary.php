<?php

namespace Drupal\forum\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Checks if the vocabulary being migrated is the one used for forums.
 *
 * Drupal 8 Forum is expecting specific machine names for its field and
 * vocabulary names. This process plugin forces a given machine name to the
 * field or vocabulary that is being migrated.
 *
 * The 'forum_vocabulary' source property is evaluated in the
 * d6_taxonomy_vocabulary or d7_taxonomy_vocabulary source plugins and is set to
 * true if the vocabulary vid being migrated is the same as the one in the
 * 'forum_nav_vocabulary' variable on the source site.
 *
 * Example:
 *
 * @code
 * process:
 *   field_name:
 *     plugin: forum_vocabulary
 *     machine_name: taxonomy_forums
 * @endcode
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
    if ($row->getSourceProperty('forum_vocabulary') && !empty($this->configuration['machine_name'])) {
      $value = $this->configuration['machine_name'];
    }
    return $value;
  }

}
