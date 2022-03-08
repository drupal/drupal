<?php

namespace Drupal\system\Form;

use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a confirmation form for enabling experimental and deprecated modules.
 *
 * @internal
 */
class ModulesListNonStableConfirmForm extends ModulesListConfirmForm {

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * An array of module names to be enabled, keyed by lifecycle.
   *
   * @var array
   */
  protected $groupedModuleInfo;

  /**
   * Boolean indicating a core deprecated module is being enabled.
   *
   * @var bool
   */
  protected $coreDeprecatedModules;

  /**
   * Boolean indicating a contrib deprecated module is being enabled.
   *
   * @var bool
   */
  protected $contribDeprecatedModules;

  /**
   * Constructs a new ModulesListNonStableConfirmForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, KeyValueStoreExpirableInterface $key_value_expirable, ModuleExtensionList $moduleExtensionList) {
    parent::__construct($module_handler, $module_installer, $key_value_expirable);
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('keyvalue.expirable')->get('module_list'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $hasExperimentalModulesToEnable = !empty($this->groupedModuleInfo[ExtensionLifecycle::EXPERIMENTAL]);
    $hasDeprecatedModulesToEnable = !empty($this->groupedModuleInfo[ExtensionLifecycle::DEPRECATED]);

    if ($hasExperimentalModulesToEnable && $hasDeprecatedModulesToEnable) {
      return $this->t('Are you sure you wish to enable experimental and deprecated modules?');
    }

    if ($hasExperimentalModulesToEnable) {
      return $this->formatPlural(
        count($this->groupedModuleInfo[ExtensionLifecycle::EXPERIMENTAL]),
        'Are you sure you wish to enable an experimental module?',
        'Are you sure you wish to enable experimental modules?'
      );
    }

    if ($hasDeprecatedModulesToEnable) {
      return $this->formatPlural(
        count($this->groupedModuleInfo[ExtensionLifecycle::DEPRECATED]),
        'Are you sure you wish to enable a deprecated module?',
        'Are you sure you wish to enable deprecated modules?'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_modules_non_stable_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildMessageList() {
    $this->buildNonStableInfo();

    $items = parent::buildMessageList();
    if (!empty($this->groupedModuleInfo[ExtensionLifecycle::EXPERIMENTAL])) {
      $this->messenger()->addWarning($this->t('<a href=":url">Experimental modules</a> are provided for testing purposes only. Use at your own risk.', [':url' => 'https://www.drupal.org/core/experimental']));
      // Add the list of experimental modules after any other messages.
      $items[] = $this->formatPlural(
        count($this->groupedModuleInfo[ExtensionLifecycle::EXPERIMENTAL]),
        'The following module is experimental: @modules.',
        'The following modules are experimental: @modules.',
        ['@modules' => implode(', ', $this->groupedModuleInfo[ExtensionLifecycle::EXPERIMENTAL])]
      );
    }
    if (!empty($this->groupedModuleInfo[ExtensionLifecycle::DEPRECATED])) {
      $this->messenger()->addWarning($this->buildDeprecatedMessage($this->coreDeprecatedModules, $this->contribDeprecatedModules));
      $items = array_merge($items, $this->groupedModuleInfo[ExtensionLifecycle::DEPRECATED]);
    }

    return $items;
  }

  /**
   * Builds a message to be displayed to the user enabling deprecated modules.
   *
   * @param bool $core_deprecated_modules
   *   TRUE if a core deprecated module is being enabled.
   * @param bool $contrib_deprecated_modules
   *   TRUE if a contrib deprecated module is being enabled.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The relevant message.
   */
  protected function buildDeprecatedMessage(bool $core_deprecated_modules, bool $contrib_deprecated_modules): TranslatableMarkup {
    if ($contrib_deprecated_modules && $core_deprecated_modules) {
      return $this->t('<a href=":url">Deprecated modules</a> are modules that may be removed from the next major release of Drupal core and the relevant contributed module. Use at your own risk.', [':url' => 'https://www.drupal.org/about/core/policies/core-change-policies/deprecated-modules-and-themes']);
    }
    if ($contrib_deprecated_modules) {
      return $this->t('<a href=":url">Deprecated modules</a> are modules that may be removed from the next major release of this project. Use at your own risk.', [':url' => 'https://www.drupal.org/about/core/policies/core-change-policies/deprecated-modules-and-themes']);
    }

    return $this->t('<a href=":url">Deprecated modules</a> are modules that may be removed from the next major release of Drupal core. Use at your own risk.', [':url' => 'https://www.drupal.org/about/core/policies/core-change-policies/deprecated-modules-and-themes']);
  }

  /**
   * Sets properties with information about non-stable modules being enabled.
   */
  protected function buildNonStableInfo(): void {
    $non_stable = $this->modules['non_stable'];
    $data = $this->moduleExtensionList->getList();
    $grouped = [];
    $core_deprecated_modules = FALSE;
    $contrib_deprecated_modules = FALSE;
    foreach ($non_stable as $machine_name => $name) {
      $lifecycle = $data[$machine_name]->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER];
      if ($lifecycle === ExtensionLifecycle::EXPERIMENTAL) {
        // We just show the extension name if it is experimental.
        $grouped[$lifecycle][] = $name;
        continue;
      }
      $core_deprecated_modules = $core_deprecated_modules || $data[$machine_name]->origin === 'core';
      $contrib_deprecated_modules = $contrib_deprecated_modules || $data[$machine_name]->origin !== 'core';
      // If the extension is deprecated we show links to more information.
      $grouped[$lifecycle][] = Link::fromTextAndUrl(
        $this->t('The @name module is deprecated. (more information)', [
          '@name' => $name,
        ]),
        Url::fromUri($data[$machine_name]->info[ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER], [
          'attributes' =>
            [
              'aria-label' => ' ' . $this->t('about the status of the @name module', [
                  '@name' => $name,
                ]),
            ],
        ])
      )->toString();
    }

    $this->groupedModuleInfo = $grouped;
    $this->coreDeprecatedModules = $core_deprecated_modules;
    $this->contribDeprecatedModules = $contrib_deprecated_modules;
  }

}
