<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ProfileValues.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the profile_values table.
 */
class ProfileValues extends DrupalDumpBase {

  public function load() {
    $this->createTable("profile_values", array(
      'primary key' => array(
        'fid',
        'uid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'uid' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '10',
          'default' => '0',
          'unsigned' => TRUE,
        ),
        'value' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("profile_values")->fields(array(
      'fid',
      'uid',
      'value',
    ))
    ->values(array(
      'fid' => '8',
      'uid' => '2',
      'value' => 'red',
    ))->values(array(
      'fid' => '8',
      'uid' => '8',
      'value' => 'brown',
    ))->values(array(
      'fid' => '8',
      'uid' => '15',
      'value' => 'orange',
    ))->values(array(
      'fid' => '8',
      'uid' => '16',
      'value' => 'blue',
    ))->values(array(
      'fid' => '8',
      'uid' => '17',
      'value' => 'yellow',
    ))->values(array(
      'fid' => '9',
      'uid' => '2',
      'value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam nulla sapien, congue nec risus ut, adipiscing aliquet felis. Maecenas quis justo vel nulla varius euismod. Quisque metus metus, cursus sit amet sem non, bibendum vehicula elit. Cras dui nisl, eleifend at iaculis vitae, lacinia ut felis. Nullam aliquam ligula volutpat nulla consectetur accumsan. Maecenas tincidunt molestie diam, a accumsan enim fringilla sit amet. Morbi a tincidunt tellus. Donec imperdiet scelerisque porta. Sed quis sem bibendum eros congue sodales. Vivamus vel fermentum est, at rutrum orci. Nunc consectetur purus ut dolor pulvinar, ut volutpat felis congue. Cras tincidunt odio sed neque sollicitudin, vehicula tempor metus scelerisque.',
    ))->values(array(
      'fid' => '9',
      'uid' => '8',
      'value' => 'Nunc condimentum ligula felis, eget lacinia purus accumsan at. Pellentesque eu lobortis felis. Duis at accumsan nisl, vel pulvinar risus. Nullam venenatis, tellus non eleifend hendrerit, augue nulla rhoncus leo, eget convallis enim sem ut velit. Mauris tincidunt enim ut eros volutpat dapibus. Curabitur augue libero, imperdiet eget orci sed, malesuada dapibus tellus. Nam lacus sapien, convallis vitae quam vel, bibendum commodo odio.',
    ))->values(array(
      'fid' => '9',
      'uid' => '15',
      'value' => 'Donec a diam volutpat augue fringilla fringilla. Mauris ultricies turpis ut lacus tempus, vitae pharetra lacus mattis. Nulla semper dui euismod sem bibendum, in eleifend nisi malesuada. Vivamus orci mauris, volutpat vitae enim ac, aliquam tempus lectus.',
    ))->values(array(
      'fid' => '9',
      'uid' => '16',
      'value' => 'Pellentesque sit amet sem et purus pretium consectetuer.',
    ))->values(array(
      'fid' => '9',
      'uid' => '17',
      'value' => 'The quick brown fox jumped over the lazy dog.',
    ))->values(array(
      'fid' => '10',
      'uid' => '2',
      'value' => '1',
    ))->values(array(
      'fid' => '10',
      'uid' => '8',
      'value' => '0',
    ))->values(array(
      'fid' => '10',
      'uid' => '15',
      'value' => '1',
    ))->values(array(
      'fid' => '10',
      'uid' => '16',
      'value' => '0',
    ))->values(array(
      'fid' => '10',
      'uid' => '17',
      'value' => '0',
    ))->values(array(
      'fid' => '11',
      'uid' => '2',
      'value' => 'Back\slash',
    ))->values(array(
      'fid' => '11',
      'uid' => '8',
      'value' => 'Forward/slash',
    ))->values(array(
      'fid' => '11',
      'uid' => '15',
      'value' => 'Dot.in.the.middle',
    ))->values(array(
      'fid' => '11',
      'uid' => '16',
      'value' => 'Faithful servant',
    ))->values(array(
      'fid' => '11',
      'uid' => '17',
      'value' => 'Anonymous donor',
    ))->values(array(
      'fid' => '12',
      'uid' => '2',
      'value' => "AC/DC\n,,Eagles\r\nElton John,Lemonheads\r\n\r\nRolling Stones\rQueen\nThe White Stripes",
    ))->values(array(
      'fid' => '12',
      'uid' => '8',
      'value' => "Deep Purple\nWho\nThe Beatles",
    ))->values(array(
      'fid' => '12',
      'uid' => '15',
      'value' => "ABBA\nBoney M",
    ))->values(array(
      'fid' => '12',
      'uid' => '16',
      'value' => "Van Halen\nDave M",
    ))->values(array(
      'fid' => '12',
      'uid' => '17',
      'value' => "Toto\nJohn Denver",
    ))->values(array(
      'fid' => '13',
      'uid' => '2',
      'value' => 'http://example.com/blog',
    ))->values(array(
      'fid' => '13',
      'uid' => '8',
      'value' => 'http://blog.example.com',
    ))->values(array(
      'fid' => '13',
      'uid' => '15',
      'value' => 'http://example.com/journal',
    ))->values(array(
      'fid' => '13',
      'uid' => '16',
      'value' => 'http://example.com/monkeys',
    ))->values(array(
      'fid' => '13',
      'uid' => '17',
      'value' => 'http://example.com/penguins',
    ))->values(array(
      'fid' => '14',
      'uid' => '2',
      'value' => 'a:3:{s:5:"month";s:1:"6";s:3:"day";s:1:"2";s:4:"year";s:4:"1974";}',
    ))->values(array(
      'fid' => '14',
      'uid' => '8',
      'value' => 'a:3:{s:5:"month";s:1:"9";s:3:"day";s:1:"9";s:4:"year";s:4:"1980";}',
    ))->values(array(
      'fid' => '14',
      'uid' => '15',
      'value' => 'a:3:{s:5:"month";s:2:"11";s:3:"day";s:2:"25";s:4:"year";s:4:"1982";}',
    ))->values(array(
      'fid' => '14',
      'uid' => '16',
      'value' => 'a:3:{s:5:"month";s:1:"9";s:3:"day";s:2:"23";s:4:"year";s:4:"1939";}',
    ))->values(array(
      'fid' => '14',
      'uid' => '17',
      'value' => 'a:3:{s:5:"month";s:2:"12";s:3:"day";s:2:"18";s:4:"year";s:4:"1942";}',
    ))->execute();
  }

}
#9d394555277ddc78f6f904b8df99b3c5
