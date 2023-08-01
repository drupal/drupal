<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\Plugin\Validation\Constraint\UserNameConstraint;
use Drupal\user\RoleInterface;

/**
 * Tests validation of an entity during migration.
 *
 * @group migrate
 */
class MigrateEntityContentValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'filter',
    'filter_test',
    'migrate',
    'system',
    'text',
    'user',
  ];

  /**
   * Messages accumulated during the migration run.
   *
   * @var string[]
   */
  protected $messages = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('user_role');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['field', 'filter_test', 'system', 'user']);

    $this->container
      ->get('event_dispatcher')
      ->addListener(MigrateEvents::IDMAP_MESSAGE, [$this, 'mapMessageRecorder']);
  }

  /**
   * Tests an import with invalid data and checks error messages.
   */
  public function test1() {
    // Make sure that a user with uid 2 exists.
    $this->container
      ->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 2,
        'name' => $this->randomMachineName(),
        'status' => 1,
      ])
      ->save();

    $this->runImport([
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'id' => '1',
            'name' => $this->randomString(256),
            'user_id' => '1',
          ],
          [
            'id' => '2',
            'name' => $this->randomString(64),
            'user_id' => '1',
          ],
          [
            'id' => '3',
            'name' => $this->randomString(64),
            'user_id' => '2',
          ],
        ],
        'ids' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'process' => [
        'id' => 'id',
        'name' => 'name',
        'user_id' => 'user_id',
      ],
      'destination' => [
        'plugin' => 'entity:entity_test',
        'validate' => TRUE,
      ],
    ]);

    $this->assertSame('1: [entity_test: 1]: name.0.value=<em class="placeholder">Name</em>: may not be longer than 64 characters.||user_id.0.target_id=The referenced entity (<em class="placeholder">user</em>: <em class="placeholder">1</em>) does not exist.', $this->messages[0], 'First message should have 2 validation errors.');
    $this->assertSame('2: [entity_test: 2]: user_id.0.target_id=The referenced entity (<em class="placeholder">user</em>: <em class="placeholder">1</em>) does not exist.', $this->messages[1], 'Second message should have 1 validation error.');
    $this->assertArrayNotHasKey(2, $this->messages, 'Third message should not exist.');
  }

  /**
   * Tests an import with invalid data and checks error messages.
   */
  public function test2() {
    $long_username = $this->randomString(61);
    $username_constraint = new UserNameConstraint();

    $this->runImport([
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'id' => 1,
            'name' => $long_username,
          ],
          [
            'id' => 2,
            'name' => $this->randomString(32),
          ],
          [
            'id' => 3,
            'name' => $this->randomString(32),
          ],
        ],
        'ids' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'process' => [
        'name' => 'name',
      ],
      'destination' => [
        'plugin' => 'entity:user',
        'validate' => TRUE,
      ],
    ]);

    $this->assertSame(sprintf('1: [user]: name=%s||name=%s||mail=Email field is required.', $username_constraint->illegalMessage, t($username_constraint->tooLongMessage, ['%name' => $long_username, '%max' => 60])), $this->messages[0], 'First message should have 3 validation errors.');
    $this->assertSame(sprintf('2: [user]: name=%s||mail=Email field is required.', $username_constraint->illegalMessage), $this->messages[1], 'Second message should have 2 validation errors.');
    $this->assertSame(sprintf('3: [user]: name=%s||mail=Email field is required.', $username_constraint->illegalMessage), $this->messages[2], 'Third message should have 2 validation errors.');
    $this->assertArrayNotHasKey(3, $this->messages, 'Fourth message should not exist.');
  }

  /**
   * Tests validation for entities that are instances of EntityOwnerInterface.
   */
  public function testEntityOwnerValidation() {
    // Text format access is impacted by user permissions.
    $filter_test_format = FilterFormat::load('filter_test');
    assert($filter_test_format instanceof FilterFormatInterface);

    // Create 2 users, an admin user who has permission to use this text format
    // and another who does not have said access.
    $role = Role::create([
      'id' => 'admin',
      'label' => 'admin',
      'is_admin' => TRUE,
    ]);
    assert($role instanceof RoleInterface);
    $role->grantPermission($filter_test_format->getPermissionName());
    $role->save();
    $admin_user = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $admin_user->addRole($role->id());
    $admin_user->save();
    $normal_user = User::create([
      'name' => 'normal user',
      'mail' => 'normal@example.com',
    ]);
    $normal_user->save();

    // Add a "body" field with the text format.
    $field_name = $this->randomMachineName();
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'text',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();

    // Attempt to migrate entities. The first record is owned by an admin user.
    $definition = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'id' => 1,
            'uid' => $admin_user->id(),
            'body' => [
              'value' => 'foo',
              'format' => 'filter_test',
            ],
          ],
          [
            'id' => 2,
            'uid' => $normal_user->id(),
            'body' => [
              'value' => 'bar',
              'format' => 'filter_test',
            ],
          ],
        ],
        'ids' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'process' => [
        'id' => 'id',
        'user_id' => 'uid',
        "$field_name/value" => 'body/value',
        "$field_name/format" => 'body/format',
      ],
      'destination' => [
        'plugin' => 'entity:entity_test',
        'validate' => TRUE,
      ],
    ];
    $this->container->get('current_user')->setAccount($normal_user);
    $this->runImport($definition);

    // The second user import should fail validation because they do not have
    // access to use "filter_test" filter.
    $this->assertSame(sprintf('2: [entity_test: 2]: user_id.0.target_id=This entity (<em class="placeholder">user</em>: <em class="placeholder">%s</em>) cannot be referenced.||%s.0.format=The value you selected is not a valid choice.', $normal_user->id(), $field_name), $this->messages[0]);
    $this->assertArrayNotHasKey(1, $this->messages);
  }

  /**
   * Reacts to map message event.
   *
   * @param \Drupal\migrate\Event\MigrateIdMapMessageEvent $event
   *   The migration event.
   */
  public function mapMessageRecorder(MigrateIdMapMessageEvent $event) {
    $this->messages[] = implode(',', $event->getSourceIdValues()) . ': ' . $event->getMessage();
  }

  /**
   * Runs an import of a migration.
   *
   * @param array $definition
   *   The migration definition.
   *
   * @throws \Exception
   * @throws \Drupal\migrate\MigrateException
   */
  protected function runImport(array $definition) {
    // Reset the list of messages from a previous migration.
    $this->messages = [];

    (new MigrateExecutable($this->container->get('plugin.manager.migration')->createStubMigration($definition)))->import();
  }

}
