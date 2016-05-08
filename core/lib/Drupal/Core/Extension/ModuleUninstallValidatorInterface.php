<?php

namespace Drupal\Core\Extension;

/**
 * Common interface for module uninstall validators.
 *
 * A module uninstall validator must implement this interface and be defined in
 * a Drupal @link container service @endlink that is tagged
 * module_install.uninstall_validator.
 */
interface ModuleUninstallValidatorInterface {

  /**
   * Determines the reasons a module can not be uninstalled.
   *
   * Example implementation:
   * @code
   * public function validate($module) {
   *   $entity_types = $this->entityManager->getDefinitions();
   *   $reasons = array();
   *   foreach ($entity_types as $entity_type) {
   *     if ($module == $entity_type->getProvider() && $entity_type instanceof ContentEntityTypeInterface && $this->entityManager->getStorage($entity_type->id())->hasData()) {
   *       $reasons[] = $this->t('There is content for the entity type: @entity_type', array('@entity_type' => $entity_type->getLabel()));
   *     }
   *   }
   *   return $reasons;
   * }
   * @endcode
   *
   * @param string $module
   *   A module name.
   *
   * @return string[]
   *   An array of reasons the module can not be uninstalled, empty if it can.
   *   Each reason should not end with any punctuation since multiple reasons
   *   can be displayed together.
   *
   * @see template_preprocess_system_modules_uninstall()
   */
  public function validate($module);

}
