<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\DateFormats.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the date_formats table.
 */
class DateFormats extends DrupalDumpBase {

  public function load() {
    $this->createTable("date_formats", array(
      'primary key' => array(
        'dfid',
      ),
      'fields' => array(
        'dfid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '10',
          'unsigned' => TRUE,
        ),
        'format' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '100',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '200',
        ),
        'locked' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("date_formats")->fields(array(
      'dfid',
      'format',
      'type',
      'locked',
    ))
    ->values(array(
      'dfid' => '1',
      'format' => 'Y-m-d H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '2',
      'format' => 'm/d/Y - H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '3',
      'format' => 'd/m/Y - H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '4',
      'format' => 'Y/m/d - H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '5',
      'format' => 'd.m.Y - H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '6',
      'format' => 'm/d/Y - g:ia',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '7',
      'format' => 'd/m/Y - g:ia',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '8',
      'format' => 'Y/m/d - g:ia',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '9',
      'format' => 'M j Y - H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '10',
      'format' => 'j M Y - H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '11',
      'format' => 'Y M j - H:i',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '12',
      'format' => 'M j Y - g:ia',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '13',
      'format' => 'j M Y - g:ia',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '14',
      'format' => 'Y M j - g:ia',
      'type' => 'short',
      'locked' => '1',
    ))->values(array(
      'dfid' => '15',
      'format' => 'D, Y-m-d H:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '16',
      'format' => 'D, m/d/Y - H:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '17',
      'format' => 'D, d/m/Y - H:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '18',
      'format' => 'D, Y/m/d - H:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '19',
      'format' => 'F j, Y - H:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '20',
      'format' => 'j F, Y - H:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '21',
      'format' => 'Y, F j - H:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '22',
      'format' => 'D, m/d/Y - g:ia',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '23',
      'format' => 'D, d/m/Y - g:ia',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '24',
      'format' => 'D, Y/m/d - g:ia',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '25',
      'format' => 'F j, Y - g:ia',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '26',
      'format' => 'j F Y - g:ia',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '27',
      'format' => 'Y, F j - g:ia',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '28',
      'format' => 'j. F Y - G:i',
      'type' => 'medium',
      'locked' => '1',
    ))->values(array(
      'dfid' => '29',
      'format' => 'l, F j, Y - H:i',
      'type' => 'long',
      'locked' => '1',
    ))->values(array(
      'dfid' => '30',
      'format' => 'l, j F, Y - H:i',
      'type' => 'long',
      'locked' => '1',
    ))->values(array(
      'dfid' => '31',
      'format' => 'l, Y,  F j - H:i',
      'type' => 'long',
      'locked' => '1',
    ))->values(array(
      'dfid' => '32',
      'format' => 'l, F j, Y - g:ia',
      'type' => 'long',
      'locked' => '1',
    ))->values(array(
      'dfid' => '33',
      'format' => 'l, j F Y - g:ia',
      'type' => 'long',
      'locked' => '1',
    ))->values(array(
      'dfid' => '34',
      'format' => 'l, Y,  F j - g:ia',
      'type' => 'long',
      'locked' => '1',
    ))->values(array(
      'dfid' => '35',
      'format' => 'l, j. F Y - G:i',
      'type' => 'long',
      'locked' => '1',
    ))->execute();
  }

}
#7b7e1b59dbb8771c5f9dacbfb31bc771
