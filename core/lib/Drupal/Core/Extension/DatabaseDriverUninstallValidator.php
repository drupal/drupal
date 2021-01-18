<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Ensures installed modules providing a database driver are not uninstalled.
 */
class DatabaseDriverUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new DatabaseDriverUninstallValidator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(TranslationInterface $string_translation, ModuleExtensionList $extension_list_module, Connection $connection) {
    $this->stringTranslation = $string_translation;
    $this->moduleExtensionList = $extension_list_module;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];

    // @todo Remove the next line of code in
    // https://www.drupal.org/project/drupal/issues/3129043.
    $this->connection = Database::getConnection();

    // When the database driver is provided by a module, then that module
    // cannot be uninstalled.
    if ($module === $this->connection->getProvider()) {
      $module_name = $this->moduleExtensionList->get($module)->info['name'];
      $reasons[] = $this->t("The module '@module_name' is providing the database driver '@driver_name'.",
        ['@module_name' => $module_name, '@driver_name' => $this->connection->driver()]);
    }

    return $reasons;
  }

}
