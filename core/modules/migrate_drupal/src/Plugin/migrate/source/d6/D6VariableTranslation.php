<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Gets Drupal i18n_variable source from database.
 *
 * @deprecated in Drupal 8.7.x and will be removed in Drupal 9.0.x.
 * Use \Drupal\migrate_drupal\Plugin\migrate\source\d6\VariableTranslation.
 *
 * @see https://www.drupal.org/node/3006487
 *
 * @MigrateSource(
 *   id = "variable_translation",
 *   source_module = "system",
 * )
 */
class D6VariableTranslation extends VariableTranslation {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager) {
    @trigger_error('The ' . __NAMESPACE__ . '\D6VariableTranslation is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Instead, use ' . __NAMESPACE__ . '\VariableTranslation. See https://www.drupal.org/node/3006487.', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
  }

}
