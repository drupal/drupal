<?php

namespace Drupal\user\Plugin\migrate\destination;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EmailItem;
use Drupal\Core\Password\PasswordInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\user\MigratePassword;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateDestination(
 *   id = "entity:user"
 * )
 */
class EntityUser extends EntityContentBase {

  /**
   * The password service class.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * Builds an user entity destination.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $plugin_manager
   *   The migrate plugin manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Password\PasswordInterface $password
   *   The password service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager, PasswordInterface $password) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $entity_manager, $field_type_manager);
    if (isset($configuration['md5_passwords'])) {
      $this->password = $password;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager')->getStorage($entity_type),
      array_keys($container->get('entity.manager')->getBundleInfo($entity_type)),
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('password')
    );
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\migrate\MigrateException
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    if ($this->password) {
      if ($this->password instanceof MigratePassword) {
        $this->password->enableMd5Prefixing();
      }
      else {
        throw new MigrateException('Password service has been altered by another module, aborting.');
      }
    }
    // Do not overwrite the root account password.
    if ($row->getDestinationProperty('uid') == 1) {
      $row->removeDestinationProperty('pass');
    }
    $ids = parent::import($row, $old_destination_id_values);
    if ($this->password) {
      $this->password->disableMd5Prefixing();
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function processStubRow(Row $row) {
    parent::processStubRow($row);
    // Email address is not defined as required in the base field definition but
    // is effectively required by the UserMailRequired constraint. This means
    // that Entity::processStubRow() did not populate it - we do it here.
    $field_definitions = $this->entityManager
      ->getFieldDefinitions($this->storage->getEntityTypeId(),
        $this->getKey('bundle'));
    $mail = EmailItem::generateSampleValue($field_definitions['mail']);
    $row->setDestinationProperty('mail', reset($mail));

    // @todo Work-around for https://www.drupal.org/node/2602066.
    $name = $row->getDestinationProperty('name');
    if (is_array($name)) {
      $name = reset($name);
    }
    if (Unicode::strlen($name) > USERNAME_MAX_LENGTH) {
      $row->setDestinationProperty('name', Unicode::substr($name, 0, USERNAME_MAX_LENGTH));
    }
  }

}
