<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesListForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides module enable/disable interface.
 *
 * The list of modules gets populated by module.info.yml files, which contain
 * each module's name, description, and information about which modules it
 * requires. See drupal_parse_info_file() for info on module.info.yml
 * descriptors.
 */
class ModulesListForm implements FormInterface, ControllerInterface {

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
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('keyvalue.expirable')->get('module_list'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a ModulesListForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\StringTranslation\TranslationManager
   *   The translation manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, KeyValueStoreExpirableInterface $key_value_expirable, TranslationManager $translation_manager) {
    $this->moduleHandler = $module_handler;
    $this->keyValueExpirable = $key_value_expirable;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_modules';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    $distribution = check_plain(drupal_install_profile_distribution_name());

    // Include system.admin.inc so we can use the sort callbacks.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');

    // Store the request for use in the submit handler.
    $this->request = $request;

    $form['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );

    $form['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->translationManager->translate('Search'),
      '#size' => 30,
      '#placeholder' => $this->translationManager->translate('Enter module name'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '#system-modules',
        'autocomplete' => 'off',
        'title' => $this->translationManager->translate('Enter a part of the module name or description to filter by.'),
      ),
    );

    // Sort all modules by their names.
    $modules = system_rebuild_module_data();
    uasort($modules, 'system_sort_modules_by_info_name');

    // Iterate over each of the modules.
    $form['modules']['#tree'] = TRUE;
    foreach ($modules as $filename => $module) {
      if (empty($module->info['hidden'])) {
        $package = $module->info['package'];
        $form['modules'][$package][$filename] = $this->buildRow($modules, $module, $distribution);
      }
    }

    // Add a wrapper around every package.
    foreach (element_children($form['modules']) as $package) {
      $form['modules'][$package] += array(
        '#type' => 'details',
        '#title' => $this->translationManager->translate($package),
        '#theme' => 'system_modules_details',
        '#header' => array(
          array('data' => '<span class="visually-hidden">' . $this->translationManager->translate('Enabled') . '</span>', 'class' => array('checkbox')),
          array('data' => $this->translationManager->translate('Name'), 'class' => array('name')),
          array('data' => $this->translationManager->translate('Description'), 'class' => array('description', RESPONSIVE_PRIORITY_LOW)),
        ),
        // Ensure that the "Core" package comes first.
        '#weight' => $package == 'Core' ? -10 : NULL,
      );
    }

    // Lastly, sort all packages by title.
    uasort($form['modules'], 'element_sort_by_title');

    $form['#attached']['library'][] = array('system', 'drupal.system.modules');
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->translationManager->translate('Save configuration'),
    );

    return $form;
  }

  /**
   * Builds a table row for the system modules page.
   *
   * @param array $modules
   *   The list existing modules.
   * @param object $module
   *   The module for which to build the form row.
   * @param $distribution
   *
   * @return array
   *   The form row for the given module.
   */
  protected function buildRow(array $modules, $module, $distribution) {
    // Set the basic properties.
    $row['#required'] = array();
    $row['#requires'] = array();
    $row['#required_by'] = array();

    $row['name']['#markup'] = $module->info['name'];
    $row['description']['#markup'] = $this->translationManager->translate($module->info['description']);
    $row['version']['#markup'] = $module->info['version'];

    // Add links for each module.
    // Used when checking if a module implements a help page.
    $help = $this->moduleHandler->moduleExists('help') ? drupal_help_arg() : FALSE;

    // Generate link for module's help page, if there is one.
    $row['links']['help'] = array();
    if ($help && $module->status && in_array($module->name, $this->moduleHandler->getImplementations('help'))) {
      if ($this->moduleHandler->invoke($module->name, 'help', array("admin/help#$module->name", $help))) {
        $row['links']['help'] = array(
          '#type' => 'link',
          '#title' => $this->translationManager->translate('Help'),
          '#href' => "admin/help/$module->name",
          '#options' => array('attributes' => array('class' =>  array('module-link', 'module-link-help'), 'title' => $this->translationManager->translate('Help'))),
        );
      }
    }

    // Generate link for module's permission, if the user has access to it.
    $row['links']['permissions'] = array();
    if ($module->status && user_access('administer permissions') && in_array($module->name, $this->moduleHandler->getImplementations('permission'))) {
      $row['links']['permissions'] = array(
        '#type' => 'link',
        '#title' => $this->translationManager->translate('Permissions'),
        '#href' => 'admin/people/permissions',
        '#options' => array('fragment' => 'module-' . $module->name, 'attributes' => array('class' => array('module-link', 'module-link-permissions'), 'title' => $this->translationManager->translate('Configure permissions'))),
      );
    }

    // Generate link for module's configuration page, if it has one.
    $row['links']['configure'] = array();
    if ($module->status && isset($module->info['configure'])) {
      if (($configure = menu_get_item($module->info['configure'])) && $configure['access']) {
        $row['links']['configure'] = array(
          '#type' => 'link',
          '#title' => $this->translationManager->translate('Configure'),
          '#href' => $configure['href'],
          '#options' => array('attributes' => array('class' => array('module-link', 'module-link-configure'), 'title' => $configure['description'])),
        );
      }
    }

    // Present a checkbox for installing and indicating the status of a module.
    $row['enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->translationManager->translate('Enable'),
      '#default_value' => (bool) $module->status,
    );

    // Disable the checkbox for required modules.
    if (!empty($module->info['required'])) {
      // Used when displaying modules that are required by the installation profile
      $row['enable']['#disabled'] = TRUE;
      $row['#required_by'][] = $distribution . (!empty($module->info['explanation']) ? ' ('. $module->info['explanation'] .')' : '');
    }

    // Check the compatibilities.
    $compatible = TRUE;
    $status = '';

    // Check the core compatibility.
    if ($module->info['core'] != DRUPAL_CORE_COMPATIBILITY) {
      $compatible = FALSE;
      $status .= $this->translationManager->translate('This version is not compatible with Drupal !core_version and should be replaced.', array(
        '!core_version' => DRUPAL_CORE_COMPATIBILITY,
      ));
    }

    // Ensure this module is compatible with the currently installed version of PHP.
    if (version_compare(phpversion(), $module->info['php']) < 0) {
      $compatible = FALSE;
      $required = $module->info['php'] . (substr_count($module->info['php'], '.') < 2 ? '.*' : '');
      $status .= $this->translationManager->translate('This module requires PHP version @php_required and is incompatible with PHP version !php_version.', array(
        '@php_required' => $required,
        '!php_version' => phpversion(),
      ));
    }

    // If this module is not compatible, disable the checkbox.
    if (!$compatible) {
      $row['enable']['#disabled'] = TRUE;
      $row['description'] = array(
        '#theme' => 'system_modules_incompatible',
        '#message' => $status,
      );
    }

    // If this module requires other modules, add them to the array.
    foreach ($module->requires as $dependency => $version) {
      if (!isset($modules[$dependency])) {
        $row['#requires'][$dependency] = $this->translationManager->translate('@module (<span class="admin-missing">missing</span>)', array('@module' => Unicode::ucfirst($dependency)));
        $row['enable']['#disabled'] = TRUE;
      }
      // Only display visible modules.
      elseif (empty($modules[$dependency]->hidden)) {
        $name = $modules[$dependency]->info['name'];
        // Disable the module's checkbox if it is incompatible with the
        // dependency's version.
        if ($incompatible_version = drupal_check_incompatibility($version, str_replace(DRUPAL_CORE_COMPATIBILITY . '-', '', $modules[$dependency]->info['version']))) {
          $row['#requires'][$dependency] = $this->translationManager->translate('@module (<span class="admin-missing">incompatible with</span> version @version)', array(
            '@module' => $name . $incompatible_version,
            '@version' => $modules[$dependency]->info['version'],
          ));
          $row['enable']['#disabled'] = TRUE;
        }
        // Disable the checkbox if the dependency is incompatible with this
        // version of Drupal core.
        elseif ($modules[$dependency]->info['core'] != DRUPAL_CORE_COMPATIBILITY) {
          $row['#requires'][$dependency] = $this->translationManager->translate('@module (<span class="admin-missing">incompatible with</span> this version of Drupal core)', array(
            '@module' => $name,
          ));
          $row['enable']['#disabled'] = TRUE;
        }
        elseif ($modules[$dependency]->status) {
          $row['#requires'][$dependency] = $this->translationManager->translate('@module', array('@module' => $name));
        }
        else {
          $row['#requires'][$dependency] = $this->translationManager->translate('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $name));
        }
      }
    }

    // If this module is required by other modules, list those, and then make it
    // impossible to disable this one.
    foreach ($module->required_by as $dependent => $version) {
      if (isset($modules[$dependent]) && empty($modules[$dependent]->info['hidden'])) {
        if ($modules[$dependent]->status == 1 && $module->status == 1) {
          $row['#required_by'][$dependent] = $this->translationManager->translate('@module', array('@module' => $modules[$dependent]->info['name']));
          $row['enable']['#disabled'] = TRUE;
        }
        else {
          $row['#required_by'][$dependent] = $this->translationManager->translate('@module (<span class="admin-disabled">disabled</span>)', array('@module' => $modules[$dependent]->info['name']));
        }
      }
    }

    return $row;
  }

  /**
   * Helper function for building a list of modules to enable or disable.
   *
   * @param array $form_state
   *   The form state array.
   *
   * @return array
   *   An array of modules to disable/enable and their dependencies.
   */
  protected function buildModuleList(array $form_state) {
    $packages = $form_state['values']['modules'];

    // Build a list of modules to enable or disable.
    $modules = array(
      'enable' => array(),
      'disable' => array(),
      'dependencies' => array(),
      'missing' => array(),
    );

    // Build a list of missing dependencies.
    // @todo This should really not be handled here.
    $data = system_rebuild_module_data();
    foreach ($data as $name => $module) {
      // Modules with missing dependencies have to be disabled.
      if ($this->moduleHandler->moduleExists($name)) {
        foreach (array_keys($module->requires) as $dependency) {
          if (!isset($data[$dependency])) {
            $modules['missing'][$dependency][$name] = $module->info['name'];
            $modules['disable'][$name] = $module->info['name'];
          }
        }
      }
      elseif (!empty($module->required)) {
        $modules['enable'][$name] = $module->info['name'];
      }
    }

    // First, build a list of all modules that were selected.
    foreach ($packages as $items) {
      foreach ($items as $name => $checkbox) {
        // Do not override modules that are forced to be enabled/disabled.
        if (isset($modules['enable'][$name]) || isset($modules['disable'][$name])) {
          continue;
        }

        $enabled = $this->moduleHandler->moduleExists($name);
        if (!$checkbox['enable'] && $enabled) {
          $modules['disable'][$name] = $data[$name]->info['name'];
        }
        elseif ($checkbox['enable'] && !$enabled) {
          $modules['enable'][$name] = $data[$name]->info['name'];
        }
      }
    }

    // Add all dependencies to a list.
    while (list($module) = each($modules['enable'])) {
      foreach (array_keys($data[$module]->requires) as $dependency) {
        if (!isset($modules['enable'][$dependency]) && !$this->moduleHandler->moduleExists($dependency)) {
          $modules['dependencies'][$module][$dependency] = $data[$dependency]->info['name'];
          $modules['enable'][$dependency] = $data[$dependency]->info['name'];
        }
      }
    }

    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Invoke hook_requirements('install'). If failures are detected, make
    // sure the dependent modules aren't installed either.
    foreach (array_keys($modules['enable']) as $module) {
      if (drupal_get_installed_schema_version($module) == SCHEMA_UNINSTALLED && !drupal_check_module($module)) {
        unset($modules['enable'][$module]);
        foreach (array_keys($data[$module]->required_by) as $dependent) {
          unset($modules['enable'][$dependent]);
          unset($modules['dependencies'][$dependent]);
        }
      }
    }

    return $modules;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Retrieve a list of modules to enable/disable and their dependencies.
    $modules = $this->buildModuleList($form_state);

    // Check if we have to enable any dependencies. If there is one or more
    // dependencies that are not enabled yet, redirect to the confirmation form.
    if (!empty($modules['dependencies']) || !empty($modules['missing'])) {
      // Write the list of changed module states into a key value store.
      $account = $this->request->attributes->get('account')->id();
      $this->keyValueExpirable->setWithExpire($account, $modules, 60);

      // Redirect to the confirmation form.
      $form_state['redirect'] = 'admin/modules/list/confirm';

      // We can exit here because at least one modules has dependencies
      // which we have to prompt the user for in a confirmation form.
      return;
    }

    // Gets list of modules prior to install process.
    $before = $this->moduleHandler->getModuleList();

    // There seem to be no dependencies that would need approval.
    if (!empty($modules['enable'])) {
      $this->moduleHandler->enable(array_keys($modules['enable']));
    }
    if (!empty($modules['disable'])) {
      $this->moduleHandler->disable(array_keys($modules['disable']));
    }

    // Gets module list after install process, flushes caches and displays a
    // message if there are changes.
    if ($before != $this->moduleHandler->getModuleList()) {
      drupal_flush_all_caches();
      drupal_set_message(t('The configuration options have been saved.'));
    }
  }

}
