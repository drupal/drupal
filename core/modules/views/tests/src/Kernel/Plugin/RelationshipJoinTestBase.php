<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\StringTranslation\TranslatableMarkup;
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
  protected static $modules = ['system', 'user', 'field'];

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $rootUser;

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    $this->installEntitySchema('user');
    $this->installConfig(['user']);
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
   *
   * @internal
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();

    $schema['views_test_data']['fields']['uid'] = [
      'description' => "The {users_field_data}.uid of the author of the beatle entry.",
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ];

    return $schema;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::viewsData().
   *
   * Adds a relationship for the uid column.
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['uid'] = [
      'title' => new TranslatableMarkup('UID'),
      'help' => new TranslatableMarkup('The test data UID'),
      'relationship' => [
        'id' => 'standard',
        'base' => 'users_field_data',
        'base field' => 'uid',
      ],
    ];

    return $data;
  }

}
