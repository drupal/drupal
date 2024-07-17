<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\Exception\UnknownExtensionTypeException;

/**
 * Factory for getting extension lists by type.
 */
class ExtensionPathResolver {

  /**
   * A associative array of ExtensionList objects keyed by type.
   *
   * @var \Drupal\Core\Extension\ExtensionList[]
   */
  protected $extensionLists;

  /**
   * ExtensionPathResolver constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profile_extension_list
   *   The profile extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list
   *   The theme extension list.
   * @param \Drupal\Core\Extension\ThemeEngineExtensionList $theme_engine_extension_list
   *   The theme engine extension list.
   */
  public function __construct(ModuleExtensionList $module_extension_list, ProfileExtensionList $profile_extension_list, ThemeExtensionList $theme_extension_list, ThemeEngineExtensionList $theme_engine_extension_list) {
    $this->extensionLists['module'] = $module_extension_list;
    $this->extensionLists['profile'] = $profile_extension_list;
    $this->extensionLists['theme'] = $theme_extension_list;
    $this->extensionLists['theme_engine'] = $theme_engine_extension_list;
  }

  /**
   * Gets the info file path for the extension.
   *
   * @param string $type
   *   The extension type.
   * @param string $name
   *   The extension name.
   *
   * @return string|null
   *   The extension path, or NULL if unknown.
   */
  public function getPathname(string $type, string $name): ?string {
    if ($type === 'core') {
      return 'core/core.info.yml';
    }
    if (!isset($this->extensionLists[$type])) {
      throw new UnknownExtensionTypeException(sprintf('Extension type %s is unknown.', $type));
    }
    try {
      return $this->extensionLists[$type]->getPathname($name);
    }
    catch (UnknownExtensionException) {
      // Catch the exception. This will result in triggering an error.
      // If the filename is still unknown, create a user-level error message.
      trigger_error(sprintf('The following %s is missing from the file system: %s', $type, $name), E_USER_WARNING);
      return NULL;
    }
  }

  /**
   * Gets the extension directory path.
   *
   * @param string $type
   *   The extension type.
   * @param string $name
   *   The extension name.
   *
   * @return string
   *   The extension info file path.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionTypeException
   *   If the extension type is unknown.
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If the extension is unknown.
   */
  public function getPath(string $type, string $name): string {
    return dirname($this->getPathname($type, $name));
  }

}
