<?php

namespace Drupal\Core\Extension;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class that manages weights.
 */
class ModuleWeight {

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Sets weight of a particular module.
   *
   * The weight of uninstalled modules cannot be changed.
   *
   * @param string $module
   *   The name of the module (without the .module extension).
   * @param int $weight
   *   An integer representing the weight of the module.
   */
  public function set(string $module, int $weight): void {
    $extensionConfig = $this->configFactory->getEditable('core.extension');
    if ($extensionConfig->get("module.$module") !== NULL) {
      // Pre-cast the $weight to an integer so that we can save this without
      // using schema. This is a performance improvement for module
      // installation.
      $extensionConfig
        ->set("module.$module", (int) $weight)
        ->set('module', $this->sort($extensionConfig->get('module')))
        ->save();

      // Prepare the new module list, sorted by weight, including filenames.
      // @see \Drupal\Core\Extension\ModuleInstaller::install()
      $currentModuleFilenames = $this->moduleHandler->getModuleList();
      $currentModules = array_fill_keys(array_keys($currentModuleFilenames), 0);
      $currentModules = $this->sort(array_merge($currentModules, $extensionConfig->get('module')));
      $moduleFilenames = [];
      foreach ($currentModules as $name => $weight) {
        $moduleFilenames[$name] = $currentModuleFilenames[$name];
      }
      // Update the module list in the extension handler.
      $this->moduleHandler->setModuleList($moduleFilenames);
      return;
    }
  }

  /**
   * Sorts the configured list of enabled modules.
   *
   * The list of enabled modules is expected to be ordered by weight and name.
   * The list is always sorted on write to avoid the overhead on read.
   *
   * @param array $data
   *   An array of module configuration data.
   *
   * @return array
   *   An array of module configuration data sorted by weight and name.
   */
  public function sort(array $data): array {
    // PHP array sorting functions such as uasort() do not work with both keys
    // and values at the same time, so we achieve weight and name sorting by
    // computing strings with both information concatenated (weight first, name
    // second) and use that as a regular string sort reference list via
    // array_multisort(), compound of
    // "[sign-as-integer][padded-integer-weight][name]"; e.g., given two
    // modules and weights (spaces added for clarity):
    // - Block with weight -5: 0 0000000000000000005 block
    // - Node  with weight  0: 1 0000000000000000000 node
    $sort = [];
    foreach ($data as $name => $weight) {
      // Prefix negative weights with 0, positive weights with 1.
      // +/- signs cannot be used, since + (ASCII 43) is before - (ASCII 45).
      $prefix = (int) ($weight >= 0);
      // The maximum weight is PHP_INT_MAX, so pad all weights to 19 digits.
      $sort[] = $prefix . sprintf('%019d', abs($weight)) . $name;
    }
    array_multisort($sort, SORT_STRING, $data);
    return $data;
  }

}
