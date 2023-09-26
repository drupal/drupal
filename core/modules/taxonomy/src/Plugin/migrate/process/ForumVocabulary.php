<?php

namespace Drupal\taxonomy\Plugin\migrate\process;

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
 * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use
 *   \Drupal\forum\Plugin\migrate\process\ForumVocabulary instead.
 *
 * @see https://www.drupal.org/node/3387830
 */
class ForumVocabulary extends ProcessPluginBase {

  /**
   * Constructs a MigrationLookup object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . 'is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use \Drupal\forum\Plugin\migrate\process\ForumVocabulary instead. See https://www.drupal.org/node/3387830', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
