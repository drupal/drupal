<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Provides a base class for a testing a relationship.
 *
 * @see \Drupal\views\Tests\Handler\JoinTest
 * @see \Drupal\views\Tests\Plugin\RelationshipTest
 */
abstract class RelationshipJoinTestBase extends PluginKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field');

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $rootUser;

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installConfig(array('user'));
    parent::setUpFixtures();

    // Create a record for uid 1.
    $this->rootUser = User::create(['name' => $this->randomMachineName()]);
    $this->rootUser->save();

    Views::viewsData()->clear();
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::schemaDefinition().
   *
   * Adds a uid column to test the relationships.
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();

    $schema['views_test_data']['fields']['uid'] = array(
      'description' => "The {users_field_data}.uid of the author of the beatle entry.",
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0
    );

    return $schema;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::viewsData().
   *
   * Adds a relationship for the uid column.
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['uid'] = array(
      'title' => t('UID'),
      'help' => t('The test data UID'),
      'relationship' => array(
        'id' => 'standard',
        'base' => 'users_field_data',
        'base field' => 'uid'
      )
    );

    return $data;
  }

}
