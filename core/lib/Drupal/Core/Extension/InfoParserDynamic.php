<?php

namespace Drupal\Core\Extension;

use Composer\Semver\Semver;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Serialization\Yaml;

/**
 * Parses dynamic .info.yml files that might change during the page request.
 */
class InfoParserDynamic implements InfoParserInterface {

  /**
   * InfoParserDynamic constructor.
   *
   * @param string $root
   *   The root directory of the Drupal installation.
   */
  public function __construct(protected string $root) {
  }

  /**
   * {@inheritdoc}
   */
  public function parse($filename) {
    if (!file_exists($filename)) {
      throw new InfoParserException("Unable to parse $filename as it does not exist");
    }

    try {
      $parsed_info = Yaml::decode(file_get_contents($filename));
    }
    catch (InvalidDataTypeException $e) {
      throw new InfoParserException("Unable to parse $filename " . $e->getMessage());
    }
    $missing_keys = array_diff($this->getRequiredKeys(), array_keys($parsed_info));
    if (!empty($missing_keys)) {
      throw new InfoParserException('Missing required keys (' . implode(', ', $missing_keys) . ') in ' . $filename);
    }
    if (!isset($parsed_info['core_version_requirement'])) {
      if (str_starts_with($filename, 'core/') || str_starts_with($filename, $this->root . '/core/')) {
        // Core extensions do not need to specify core compatibility: they are
        // by definition compatible so a sensible default is used. Core
        // modules are allowed to provide these for testing purposes.
        $parsed_info['core_version_requirement'] = \Drupal::VERSION;
      }
      elseif (isset($parsed_info['package']) && $parsed_info['package'] === 'Testing') {
        // Modules in the testing package are exempt as well. This makes it
        // easier for contrib to use test modules.
        $parsed_info['core_version_requirement'] = \Drupal::VERSION;
      }
      else {
        // Non-core extensions must specify core compatibility.
        throw new InfoParserException("The 'core_version_requirement' key must be present in " . $filename);
      }
    }

    // Determine if the extension is compatible with the current version of
    // Drupal core.
    try {
      $parsed_info['core_incompatible'] = !Semver::satisfies(\Drupal::VERSION, $parsed_info['core_version_requirement']);
    }
    catch (\UnexpectedValueException) {
      throw new InfoParserException("The 'core_version_requirement' constraint ({$parsed_info['core_version_requirement']}) is not a valid value in $filename");
    }
    if (isset($parsed_info['version'])) {
      if ($parsed_info['version'] === 'VERSION') {
        $parsed_info['version'] = \Drupal::VERSION;
      }
      elseif (!is_scalar($parsed_info['version'])) {
        throw new InfoParserException("The 'version' value must be a scalar in $filename");
      }
      elseif (!is_string($parsed_info['version'])) {
        // @todo Replace this with an InfoParserException in Drupal 13.
        // @see https://www.drupal.org/project/drupal/issues/3576313
        @trigger_error("Using a non-string as the 'version' value in $filename is deprecated in drupal:11.4.0 and will be a fatal error in drupal:13.0.0. Instead, wrap the version value in single quotes. See https://www.drupal.org/node/3576311", E_USER_DEPRECATED);
        $parsed_info['version'] = (string) $parsed_info['version'];
      }
    }
    $parsed_info += [ExtensionLifecycle::LIFECYCLE_IDENTIFIER => ExtensionLifecycle::STABLE];
    $lifecycle = $parsed_info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER];
    if (!ExtensionLifecycle::isValid($lifecycle)) {
      $valid_values = [
        ExtensionLifecycle::EXPERIMENTAL,
        ExtensionLifecycle::STABLE,
        ExtensionLifecycle::DEPRECATED,
        ExtensionLifecycle::OBSOLETE,
      ];
      throw new InfoParserException("'lifecycle: {$lifecycle}' is not valid in $filename. Valid values are: '" . implode("', '", $valid_values) . "'.");
    }
    if (in_array($lifecycle, [ExtensionLifecycle::DEPRECATED, ExtensionLifecycle::OBSOLETE], TRUE)) {
      if (empty($parsed_info[ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER])) {
        throw new InfoParserException(sprintf("Extension %s (%s) has 'lifecycle: %s' but is missing a '%s' entry.", $parsed_info['name'], $filename, $lifecycle, ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER));
      }
      if (!filter_var($parsed_info[ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER], FILTER_VALIDATE_URL)) {
        throw new InfoParserException(sprintf("Extension %s (%s) has a '%s' entry that is not a valid URL.", $parsed_info['name'], $filename, ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER));
      }
    }

    return $parsed_info;
  }

  /**
   * Returns an array of keys required to exist in .info.yml file.
   *
   * @return array
   *   An array of required keys.
   */
  protected function getRequiredKeys() {
    return ['type', 'name'];
  }

}
