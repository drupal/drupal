<?php

namespace Drupal\Core\Extension;

/**
 * Messages for missing or incompatible dependencies on modules.
 *
 * @internal The trait simply helps core classes that display user messages
 *   regarding missing or incompatible module dependencies share exact same
 *   wording and markup.
 */
trait ModuleDependencyMessageTrait {

  /**
   * Provides messages for missing modules or incompatible dependencies.
   *
   * @param array $modules
   *   The list of existing modules.
   * @param string $dependency
   *   The module dependency to check.
   * @param \Drupal\Core\Extension\Dependency $dependency_object
   *   Dependency object used for comparing version requirement data.
   *
   * @return string|null
   *   NULL if compatible, otherwise a string describing the incompatibility.
   */
  public function checkDependencyMessage(array $modules, $dependency, Dependency $dependency_object) {
    if (!isset($modules[$dependency])) {
      return $this->t('@module_name (<span class="admin-missing">missing</span>)', ['@module_name' => $dependency]);
    }
    else {
      $module_name = $modules[$dependency]->info['name'];

      // Check if the module is compatible with the installed version of core.
      if ($modules[$dependency]->info['core_incompatible']) {
        return $this->t('@module_name (<span class="admin-missing">incompatible with</span> this version of Drupal core)', [
          '@module_name' => $module_name,
        ]);
      }

      // Check if the module is incompatible with the dependency constraints.
      $version = str_replace(\Drupal::CORE_COMPATIBILITY . '-', '', $modules[$dependency]->info['version'] ?? '');
      if (!$dependency_object->isCompatible($version)) {
        $constraint_string = $dependency_object->getConstraintString();
        return $this->t('@module_name (<span class="admin-missing">incompatible with</span> version @version)', [
          '@module_name' => "$module_name ($constraint_string)",
          '@version' => $modules[$dependency]->info['version'],
        ]);
      }
    }
  }

}
