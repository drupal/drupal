<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d6\ProfileFields.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d6;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the profile_fields table.
 */
class ProfileFields extends DrupalDumpBase {

  public function load() {
    $this->createTable("profile_fields", array(
      'primary key' => array(
        'fid',
      ),
      'fields' => array(
        'fid' => array(
          'type' => 'serial',
          'not null' => TRUE,
          'length' => '11',
        ),
        'title' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'explanation' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
        'category' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'page' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '255',
        ),
        'type' => array(
          'type' => 'varchar',
          'not null' => FALSE,
          'length' => '128',
        ),
        'weight' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'required' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'register' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'visibility' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'autocomplete' => array(
          'type' => 'int',
          'not null' => TRUE,
          'length' => '11',
          'default' => '0',
        ),
        'options' => array(
          'type' => 'text',
          'not null' => FALSE,
          'length' => 100,
        ),
      ),
    ));
    $this->database->insert("profile_fields")->fields(array(
      'fid',
      'title',
      'name',
      'explanation',
      'category',
      'page',
      'type',
      'weight',
      'required',
      'register',
      'visibility',
      'autocomplete',
      'options',
    ))
    ->values(array(
      'fid' => '8',
      'title' => 'Favorite color',
      'name' => 'profile_color',
      'explanation' => 'List your favorite color',
      'category' => 'Personal information',
      'page' => 'Peole whose favorite color is %value',
      'type' => 'textfield',
      'weight' => '-10',
      'required' => '0',
      'register' => '1',
      'visibility' => '2',
      'autocomplete' => '1',
      'options' => '',
    ))->values(array(
      'fid' => '9',
      'title' => 'Biography',
      'name' => 'profile_biography',
      'explanation' => 'Tell people a little bit about yourself',
      'category' => 'Personal information',
      'page' => '',
      'type' => 'textarea',
      'weight' => '-8',
      'required' => '0',
      'register' => '0',
      'visibility' => '2',
      'autocomplete' => '0',
      'options' => '',
    ))->values(array(
      'fid' => '10',
      'title' => 'Sell your email address?',
      'name' => 'profile_sell_address',
      'explanation' => "If you check this box, we'll sell your address to spammers to help line the pockets of our shareholders. Thanks!",
      'category' => 'Communication preferences',
      'page' => 'People who want us to sell their address',
      'type' => 'checkbox',
      'weight' => '-10',
      'required' => '0',
      'register' => '1',
      'visibility' => '1',
      'autocomplete' => '0',
      'options' => '',
    ))->values(array(
      'fid' => '11',
      'title' => 'Sales Category',
      'name' => 'profile_sold_to',
      'explanation' => "Select the sales categories to which this user's address was sold.",
      'category' => 'Administrative data',
      'page' => 'People whose address was sold to %value',
      'type' => 'selection',
      'weight' => '-10',
      'required' => '0',
      'register' => '0',
      'visibility' => '4',
      'autocomplete' => '0',
      'options' => "Pill spammers\r\nFitness spammers\r\nBack\\slash\r\nForward/slash\r\nDot.in.the.middle",
    ))->values(array(
      'fid' => '12',
      'title' => 'Favorite bands',
      'name' => 'profile_bands',
      'explanation' => "Enter your favorite bands. When you've saved your profile, you'll be able to find other people with the same favorites.",
      'category' => 'Personal information',
      'page' => '',
      'type' => 'list',
      'weight' => '-6',
      'required' => '0',
      'register' => '1',
      'visibility' => '3',
      'autocomplete' => '1',
      'options' => '',
    ))->values(array(
      'fid' => '14',
      'title' => 'Birthdate',
      'name' => 'profile_birthdate',
      'explanation' => "Enter your birth date and we'll send you a coupon.",
      'category' => 'Personal information',
      'page' => '',
      'type' => 'date',
      'weight' => '4',
      'required' => '0',
      'register' => '0',
      'visibility' => '2',
      'autocomplete' => '0',
      'options' => '',
    ))->values(array(
      'fid' => '15',
      'title' => 'I love migrations',
      'name' => 'profile_love_migrations',
      'explanation' => 'If you check this box, you love migrations.',
      'category' => 'Personal information',
      'page' => 'People who love migrations',
      'type' => 'checkbox',
      'weight' => '-15',
      'required' => '0',
      'register' => '0',
      'visibility' => '2',
      'autocomplete' => '0',
      'options' => '',
    ))->execute();
  }

}
#5b52398061f1ff1fd90ffb8fb91059a2
