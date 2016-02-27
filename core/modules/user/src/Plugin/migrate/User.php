<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\migrate\builder\d7\User.
 */

namespace Drupal\user\Plugin\migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Migration;

/**
 * Plugin class for Drupal 7 user migrations dealing with fields and profiles.
 */
class User extends Migration {

  /**
   * Flag indicating whether the CCK data has been filled already.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $definition['source'] = [
        'entity_type' => 'user',
        'ignore_map' => TRUE,
      ] + $this->source;
      $definition['destination']['plugin'] = 'null';
      if (\Drupal::moduleHandler()->moduleExists('field')) {
        $definition['source']['plugin'] = 'd7_field_instance';
        $field_migration = new Migration([], uniqid(), $definition);
        foreach ($field_migration->getSourcePlugin() as $row) {
          $field_name = $row->getSourceProperty('field_name');
          $this->process[$field_name] = $field_name;
        }
      }
      try {
        $definition['source']['plugin'] = 'profile_field';
        $profile_migration = new Migration([], uniqid(), $definition);
        // Ensure that Profile is enabled in the source DB.
        $profile_migration->checkRequirements();
        foreach ($profile_migration->getSourcePlugin() as $row) {
          $name = $row->getSourceProperty('name');
          $this->process[$name] = $name;
        }
      }
      catch (RequirementsException $e) {
        // @TODO https://www.drupal.org/node/2666640
      }
    }
    return parent::getProcess();
  }

}
