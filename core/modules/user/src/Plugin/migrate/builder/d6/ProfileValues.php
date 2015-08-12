<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\builder\d6\ProfileValues.
 */

namespace Drupal\user\Plugin\migrate\builder\d6;

use Drupal\migrate\Entity\Migration;
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

    foreach ($this->getSourcePlugin('d6_profile_field') as $field) {
      $migration->setProcessOfProperty($field->getSourceProperty('name'), $field->getSourceProperty('name'));
    }

    return [$migration];
  }

}
