<?php

namespace Drupal\Core\Extension;

/**
 * Interface for classes that parses Drupal's info.yml files.
 */
interface InfoParserInterface  {

  /**
   * Parses Drupal module, theme and profile .info.yml files.
   *
   * Info files are NOT for placing arbitrary theme and module-specific
   * settings. Use Config::get() and Config::set()->save() for that. Info files
   * are formatted as YAML. If the 'version' key is set to 'VERSION' in any info
   * file, then the value will be substituted with the current version of Drupal
   * core.
   *
   * Information stored in all .info.yml files:
   * - name: The real name of the module for display purposes. (Required)
   * - description: A brief description of the module.
   * - type: whether it is for a module or theme. (Required)
   *
   * Information stored in a module .info.yml file:
   * - dependencies: An array of dependency strings. Each is in the form
   *   'project:module (versions)'; with the following meanings:
   *   - project: (optional) Project shortname, recommended to ensure
   *     uniqueness, if the module is part of a project hosted on drupal.org.
   *     If omitted, also omit the : that follows. The project name is currently
   *     ignored by Drupal core but is used for automated testing.
   *   - module: (required) Module shortname within the project.
   *   - (versions): Version information, consisting of one or more
   *     comma-separated operator/value pairs or simply version numbers, which
   *     can contain "x" as a wildcard. Examples: (>=8.22, <8.28), (8.x-3.x).
   * - package: The name of the package of modules this module belongs to.
   *
   * See forum.info.yml for an example of a module .info.yml file.
   *
   * Information stored in a theme .info.yml file:
   * - screenshot: Path to screenshot relative to the theme's .info.yml file.
   * - engine: Theme engine; typically twig.
   * - base theme: Name of a base theme, if applicable.
   * - regions: Listed regions.
   * - features: Features available.
   * - stylesheets: Theme stylesheets.
   * - scripts: Theme scripts.
   *
   * See bartik.info.yml for an example of a theme .info.yml file.
   *
   * @param string $filename
   *   The file we are parsing. Accepts file with relative or absolute path.
   *
   * @return array
   *   The info array.
   *
   * @throws \Drupal\Core\Extension\InfoParserException
   *   Exception thrown if there is a parsing error or the .info.yml file does
   *   not contain a required key.
   */
  public function parse($filename);

}

