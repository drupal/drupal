<?php

namespace Drupal\Core\Extension;

/**
 * Provides method for checking requirements during install time.
 */
interface InstallRequirementsInterface {

  /**
   * Check installation requirements.
   *
   * Classes implementing this must be in the Install/Requirements namespace.
   * For example src/Install/Requirements/ModuleNameRequirements.php.
   *
   * During the 'install' phase, modules can for example assert that
   * library or server versions are available or sufficient.
   * Note that the installation of a module can happen during installation of
   * Drupal itself (by install.php) with an installation profile or later by
   * hand. As a consequence, install-time requirements must be checked without
   * access to the full Drupal API, because it is not available during
   * install.php.
   * If a requirement has a severity of RequirementSeverity::Error, install.php
   * will abort or at least the module will not install.
   * Other severity levels have no effect on the installation.
   * Module dependencies do not belong to these installation requirements,
   * but should be defined in the module's .info.yml file.
   *
   * During installation, if you need to load a class from your module,
   * you'll need to include the class file directly.
   *
   * @return array
   *   An associative array where the keys are arbitrary but must be unique (it
   *   is suggested to use the module short name as a prefix) and the values are
   *   themselves associative arrays with the following elements:
   *   - title: The name of the requirement.
   *   - value: This should only be used for version numbers, do not set it if
   *     not applicable.
   *   - description: The description of the requirement/status.
   *   - severity: (optional) An instance of
   *     \Drupal\Core\Extension\Requirement\RequirementSeverity enum. Defaults
   *     to RequirementSeverity::OK when installing.
   */
  public static function getRequirements(): array;

}
