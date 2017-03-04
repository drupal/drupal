<?php

namespace Drupal\system\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\PermissionHandlerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides module installation interface.
 *
 * The list of modules gets populated by module.info.yml files, which contain
 * each module's name, description, and information about which modules it
 * requires. See \Drupal\Core\Extension\InfoParser for info on module.info.yml
 * descriptors.
 */
class ModulesListForm extends FormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The expirable key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('keyvalue.expirable')->get('module_list'),
      $container->get('access_manager'),
      $container->get('current_user'),
      $container->get('user.permissions')
    );
  }

  /**
   * Constructs a ModulesListForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   Access manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, KeyValueStoreExpirableInterface $key_value_expirable, AccessManagerInterface $access_manager, AccountInterface $current_user, PermissionHandlerInterface $permission_handler) {
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->keyValueExpirable = $key_value_expirable;
    $this->accessManager = $access_manager;
    $this->currentUser = $current_user;
    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_modules';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    $distribution = drupal_install_profile_distribution_name();

    // Include system.admin.inc so we can use the sort callbacks.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $form['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter modules'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by name or description'),
      '#description' => $this->t('Enter a part of the module name or description'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#system-modules',
        'autocomplete' => 'off',
      ],
    ];

    // Sort all modules by their names.
    $modules = system_rebuild_module_data();
    uasort($modules, 'system_sort_modules_by_info_name');

    // Iterate over each of the modules.
    $form['modules']['#tree'] = TRUE;
    foreach ($modules as $filename => $module) {
      if (empty($module->info['hidden'])) {
        $package = $module->info['package'];
        $form['modules'][$package][$filename] = $this->buildRow($modules, $module, $distribution);
        $form['modules'][$package][$filename]['#parents'] = ['modules', $filename];
      }
    }

    // Add a wrapper around every package.
    foreach (Element::children($form['modules']) as $package) {
      $form['modules'][$package] += [
        '#type' => 'details',
        '#title' => $this->t($package),
        '#open' => TRUE,
        '#theme' => 'system_modules_details',
        '#attributes' => ['class' => ['package-listing']],
        // Ensure that the "Core" package comes first.
        '#weight' => $package == 'Core' ? -10 : NULL,
      ];
    }

    // If testing modules are shown, collapse the corresponding package by
    // default.
    if (isset($form['modules']['Testing'])) {
      $form['modules']['Testing']['#open'] = FALSE;
    }

    // Lastly, sort all packages by title.
    uasort($form['modules'], ['\Drupal\Component\Utility\SortArray', 'sortByTitleProperty']);

    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Install'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Builds a table row for the system modules page.
   *
   * @param array $modules
   *   The list existing modules.
   * @param \Drupal\Core\Extension\Extension $module
   *   The module for which to build the form row.
   * @param $distribution
   *
   * @return array
   *   The form row for the given module.
   */
  protected function buildRow(array $modules, Extension $module, $distribution) {
    // Set the basic properties.
    $row['#required'] = [];
    $row['#requires'] = [];
    $row['#required_by'] = [];

    $row['name']['#markup'] = $module->info['name'];
    $row['description']['#markup'] = $this->t($module->info['description']);
    $row['version']['#markup'] = $module->info['version'];

    // Generate link for module's help page. Assume that if a hook_help()
    // implementation exists then the module provides an overview page, rather
    // than checking to see if the page exists, which is costly.
    if ($this->moduleHandler->moduleExists('help') && $module->status && in_array($module->getName(), $this->moduleHandler->getImplementations('help'))) {
      $row['links']['help'] = [
        '#type' => 'link',
        '#title' => $this->t('Help'),
        '#url' => Url::fromRoute('help.page', ['name' => $module->getName()]),
        '#options' => ['attributes' => ['class' => ['module-link', 'module-link-help'], 'title' => $this->t('Help')]],
      ];
    }

    // Generate link for module's permission, if the user has access to it.
    if ($module->status && $this->currentUser->hasPermission('administer permissions') && $this->permissionHandler->moduleProvidesPermissions($module->getName())) {
      $row['links']['permissions'] = [
        '#type' => 'link',
        '#title' => $this->t('Permissions'),
        '#url' => Url::fromRoute('user.admin_permissions'),
        '#options' => ['fragment' => 'module-' . $module->getName(), 'attributes' => ['class' => ['module-link', 'module-link-permissions'], 'title' => $this->t('Configure permissions')]],
      ];
    }

    // Generate link for module's configuration page, if it has one.
    if ($module->status && isset($module->info['configure'])) {
      $route_parameters = isset($module->info['configure_parameters']) ? $module->info['configure_parameters'] : [];
      if ($this->accessManager->checkNamedRoute($module->info['configure'], $route_parameters, $this->currentUser)) {
        $row['links']['configure'] = [
          '#type' => 'link',
          '#title' => $this->t('Configure <span class="visually-hidden">the @module module</span>', ['@module' => $module->info['name']]),
          '#url' => Url::fromRoute($module->info['configure'], $route_parameters),
          '#options' => [
            'attributes' => [
              'class' => ['module-link', 'module-link-configure'],
            ],
          ],
        ];
      }
    }

    // Present a checkbox for installing and indicating the status of a module.
    $row['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Install'),
      '#default_value' => (bool) $module->status,
      '#disabled' => (bool) $module->status,
    ];

    // Disable the checkbox for required modules.
    if (!empty($module->info['required'])) {
      // Used when displaying modules that are required by the installation profile
      $row['enable']['#disabled'] = TRUE;
      $row['#required_by'][] = $distribution . (!empty($module->info['explanation']) ? ' (' . $module->info['explanation'] . ')' : '');
    }

    // Check the compatibilities.
    $compatible = TRUE;

    // Initialize an empty array of reasons why the module is incompatible. Add
    // each reason as a separate element of the array.
    $reasons = [];

    // Check the core compatibility.
    if ($module->info['core'] != \Drupal::CORE_COMPATIBILITY) {
      $compatible = FALSE;
      $reasons[] = $this->t('This version is not compatible with Drupal @core_version and should be replaced.', [
        '@core_version' => \Drupal::CORE_COMPATIBILITY,
      ]);
    }

    // Ensure this module is compatible with the currently installed version of PHP.
    if (version_compare(phpversion(), $module->info['php']) < 0) {
      $compatible = FALSE;
      $required = $module->info['php'] . (substr_count($module->info['php'], '.') < 2 ? '.*' : '');
      $reasons[] = $this->t('This module requires PHP version @php_required and is incompatible with PHP version @php_version.', [
        '@php_required' => $required,
        '@php_version' => phpversion(),
      ]);
    }

    // If this module is not compatible, disable the checkbox.
    if (!$compatible) {
      $status = implode(' ', $reasons);
      $row['enable']['#disabled'] = TRUE;
      $row['description']['#markup'] = $status;
      $row['#attributes']['class'][] = 'incompatible';
    }

    // If this module requires other modules, add them to the array.
    foreach ($module->requires as $dependency => $version) {
      if (!isset($modules[$dependency])) {
        $row['#requires'][$dependency] = $this->t('@module (<span class="admin-missing">missing</span>)', ['@module' => Unicode::ucfirst($dependency)]);
        $row['enable']['#disabled'] = TRUE;
      }
      // Only display visible modules.
      elseif (empty($modules[$dependency]->hidden)) {
        $name = $modules[$dependency]->info['name'];
        // Disable the module's checkbox if it is incompatible with the
        // dependency's version.
        if ($incompatible_version = drupal_check_incompatibility($version, str_replace(\Drupal::CORE_COMPATIBILITY . '-', '', $modules[$dependency]->info['version']))) {
          $row['#requires'][$dependency] = $this->t('@module (<span class="admin-missing">incompatible with</span> version @version)', [
            '@module' => $name . $incompatible_version,
            '@version' => $modules[$dependency]->info['version'],
          ]);
          $row['enable']['#disabled'] = TRUE;
        }
        // Disable the checkbox if the dependency is incompatible with this
        // version of Drupal core.
        elseif ($modules[$dependency]->info['core'] != \Drupal::CORE_COMPATIBILITY) {
          $row['#requires'][$dependency] = $this->t('@module (<span class="admin-missing">incompatible with</span> this version of Drupal core)', [
            '@module' => $name,
          ]);
          $row['enable']['#disabled'] = TRUE;
        }
        elseif ($modules[$dependency]->status) {
          $row['#requires'][$dependency] = $this->t('@module', ['@module' => $name]);
        }
        else {
          $row['#requires'][$dependency] = $this->t('@module (<span class="admin-disabled">disabled</span>)', ['@module' => $name]);
        }
      }
    }

    // If this module is required by other modules, list those, and then make it
    // impossible to disable this one.
    foreach ($module->required_by as $dependent => $version) {
      if (isset($modules[$dependent]) && empty($modules[$dependent]->info['hidden'])) {
        if ($modules[$dependent]->status == 1 && $module->status == 1) {
          $row['#required_by'][$dependent] = $this->t('@module', ['@module' => $modules[$dependent]->info['name']]);
          $row['enable']['#disabled'] = TRUE;
        }
        else {
          $row['#required_by'][$dependent] = $this->t('@module (<span class="admin-disabled">disabled</span>)', ['@module' => $modules[$dependent]->info['name']]);
        }
      }
    }

    return $row;
  }

  /**
   * Helper function for building a list of modules to install.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array of modules to install and their dependencies.
   */
  protected function buildModuleList(FormStateInterface $form_state) {
    // Build a list of modules to install.
    $modules = [
      'install' => [],
      'dependencies' => [],
      'experimental' => [],
    ];

    $data = system_rebuild_module_data();
    foreach ($data as $name => $module) {
      // If the module is installed there is nothing to do.
      if ($this->moduleHandler->moduleExists($name)) {
        continue;
      }
      // Required modules have to be installed.
      if (!empty($module->required)) {
        $modules['install'][$name] = $module->info['name'];
      }
      // Selected modules should be installed.
      elseif (($checkbox = $form_state->getValue(['modules', $name], FALSE)) && $checkbox['enable']) {
        $modules['install'][$name] = $data[$name]->info['name'];
        // Identify experimental modules.
        if ($data[$name]->info['package'] == 'Core (Experimental)') {
          $modules['experimental'][$name] = $data[$name]->info['name'];
        }
      }
    }

    // Add all dependencies to a list.
    while (list($module) = each($modules['install'])) {
      foreach (array_keys($data[$module]->requires) as $dependency) {
        if (!isset($modules['install'][$dependency]) && !$this->moduleHandler->moduleExists($dependency)) {
          $modules['dependencies'][$module][$dependency] = $data[$dependency]->info['name'];
          $modules['install'][$dependency] = $data[$dependency]->info['name'];

          // Identify experimental modules.
          if ($data[$dependency]->info['package'] == 'Core (Experimental)') {
            $modules['experimental'][$dependency] = $data[$dependency]->info['name'];
          }
        }
      }
    }

    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Invoke hook_requirements('install'). If failures are detected, make
    // sure the dependent modules aren't installed either.
    foreach (array_keys($modules['install']) as $module) {
      if (!drupal_check_module($module)) {
        unset($modules['install'][$module]);
        unset($modules['experimental'][$module]);
        foreach (array_keys($data[$module]->required_by) as $dependent) {
          unset($modules['install'][$dependent]);
          unset($modules['dependencies'][$dependent]);
        }
      }
    }

    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve a list of modules to install and their dependencies.
    $modules = $this->buildModuleList($form_state);

    // Redirect to a confirmation form if needed.
    if (!empty($modules['experimental']) || !empty($modules['dependencies'])) {

      $route_name = !empty($modules['experimental']) ? 'system.modules_list_experimental_confirm' : 'system.modules_list_confirm';
      // Write the list of changed module states into a key value store.
      $account = $this->currentUser()->id();
      $this->keyValueExpirable->setWithExpire($account, $modules, 60);

      // Redirect to the confirmation form.
      $form_state->setRedirect($route_name);

      // We can exit here because at least one modules has dependencies
      // which we have to prompt the user for in a confirmation form.
      return;
    }

    // Install the given modules.
    if (!empty($modules['install'])) {
      try {
        $this->moduleInstaller->install(array_keys($modules['install']));
        $module_names = array_values($modules['install']);
        drupal_set_message($this->formatPlural(count($module_names), 'Module %name has been enabled.', '@count modules have been enabled: %names.', [
          '%name' => $module_names[0],
          '%names' => implode(', ', $module_names),
        ]));
      }
      catch (PreExistingConfigException $e) {
        $config_objects = $e->flattenConfigObjects($e->getConfigObjects());
        drupal_set_message(
          $this->formatPlural(
            count($config_objects),
            'Unable to install @extension, %config_names already exists in active configuration.',
            'Unable to install @extension, %config_names already exist in active configuration.',
            [
              '%config_names' => implode(', ', $config_objects),
              '@extension' => $modules['install'][$e->getExtension()]
            ]),
          'error'
        );
        return;
      }
      catch (UnmetDependenciesException $e) {
        drupal_set_message(
          $e->getTranslatedMessage($this->getStringTranslation(), $modules['install'][$e->getExtension()]),
          'error'
        );
        return;
      }
    }
  }

}
