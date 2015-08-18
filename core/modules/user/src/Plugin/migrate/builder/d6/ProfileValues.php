<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\builder\d6\ProfileValues.
 */

namespace Drupal\user\Plugin\migrate\builder\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\migrate\builder\BuilderBase;

/**
 * @PluginID("d6_profile_values")
 */
class ProfileValues extends BuilderBase {

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migration = Migration::create($template);

    // @TODO The source plugin should accept a database connection.
    // @see https://www.drupal.org/node/2552791
    $source_plugin = $this->getSourcePlugin('d6_profile_field', $template['source']);
    try {
      $source_plugin->checkRequirements();
    }
    catch (RequirementsException $e) {
      return [];
    }

    foreach ($source_plugin as $field) {
      $migration->setProcessOfProperty($field->getSourceProperty('name'), $field->getSourceProperty('name'));
    }

    return [$migration];
  }

}
