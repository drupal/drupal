<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\TriggerAssignments.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the trigger_assignments table.
 */
class TriggerAssignments extends DrupalDumpBase {

  public function load() {
    $this->createTable("trigger_assignments", array(
      'primary key' => array(
        'hook',
        'aid',
      ),
      'fields' => array(
        'hook' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '78',
          'default' => '',
        ),
        'aid' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
    ));
    $this->database->insert("trigger_assignments")->fields(array(
      'hook',
      'aid',
      'weight',
    ))
    ->values(array(
      'hook' => 'comment_presave',
      'aid' => 'comment_publish_action',
      'weight' => '1',
    ))->execute();
  }

}
#5d757148ffae33413bb7681a38cccb04
