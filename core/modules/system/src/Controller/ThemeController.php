<?php

namespace Drupal\system\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Theme\ThemeAccessCheck;
use Drupal\Core\Url;
use Drupal\system\Form\ThemeExperimentalConfirmForm;
use Drupal\system\ModuleDependencyMessageTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for theme handling.
 */
class ThemeController extends ControllerBase {

  use ModuleDependencyMessageTrait;

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeList;

  /**
   * The theme installer service.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * The theme access checker service.
   *
   * @var \Drupal\Core\Theme\ThemeAccessCheck
   */
  protected $themeAccess;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new ThemeController.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeAccessCheck $theme_access
   *   The theme access checker service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   The theme extension list.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer
   *   The theme installer.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, ThemeAccessCheck $theme_access, ThemeExtensionList $theme_list, ConfigFactoryInterface $config_factory, ThemeInstallerInterface $theme_installer, FormBuilderInterface $form_builder, ModuleExtensionList $module_extension_list) {
    $this->themeHandler = $theme_handler;
    $this->themeList = $theme_list;
    $this->themeAccess = $theme_access;
    $this->configFactory = $config_factory;
    $this->themeInstaller = $theme_installer;
    $this->formBuilder = $form_builder;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('theme_handler'),
      $container->get('access_check.theme'),
      $container->get('extension.list.theme'),
      $container->get('config.factory'),
      $container->get('theme_installer'),
      $container->get('form_builder'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Returns a theme listing.
   *
   * @return string
   *   An HTML string of the theme listing page.
   *
   * @todo Move into ThemeController.
   */
  public function themesPage() {
    $config = $this->config('system.theme');
    // Get all available themes.
    $themes = $this->themeHandler->rebuildThemeData();
    uasort($themes, 'system_sort_modules_by_info_name');

    $theme_default = $config->get('default');
    $theme_groups = ['installed' => [], 'uninstalled' => []];
    $admin_theme = $config->get('admin');
    $admin_theme_options = [];
    $incompatible_installed = FALSE;

    foreach ($themes as &$theme) {
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      if (!$incompatible_installed && $theme->info['core_incompatible'] && $theme->status) {
        $incompatible_installed = TRUE;
        $this->messenger()->addWarning($this->t(
          'There are errors with some installed themes. Visit the <a href=":link">status report page</a> for more information.',
          [':link' => Url::fromRoute('system.status')->toString()]
        ));
      }
      $theme->is_default = ($theme->getName() == $theme_default);
      $theme->is_admin = ($theme->getName() == $admin_theme || ($theme->is_default && empty($admin_theme)));
      $theme->is_experimental = isset($theme->info['experimental']) && $theme->info['experimental'];

      // Identify theme screenshot.
      $theme->screenshot = NULL;
      // Create a list which includes the current theme and all its base themes.
      if (isset($themes[$theme->getName()]->base_themes)) {
        $theme_keys = array_keys($themes[$theme->getName()]->base_themes);
        $theme_keys[] = $theme->getName();
      }
      else {
        $theme_keys = [$theme->getName()];
      }
      // Look for a screenshot in the current theme or in its closest ancestor.
      foreach (array_reverse($theme_keys) as $theme_key) {
        if (isset($themes[$theme_key]) && file_exists($themes[$theme_key]->info['screenshot'])) {
          $theme->screenshot = [
            'uri' => $themes[$theme_key]->info['screenshot'],
            'alt' => $this->t('Screenshot for @theme theme', ['@theme' => $theme->info['name']]),
            'title' => $this->t('Screenshot for @theme theme', ['@theme' => $theme->info['name']]),
            'attributes' => ['class' => ['screenshot']],
          ];
          break;
        }
      }

      if (empty($theme->status)) {
        // Require the 'content' region to make sure the main page
        // content has a common place in all themes.
        $theme->incompatible_region = !isset($theme->info['regions']['content']);
        $theme->incompatible_php = version_compare(phpversion(), $theme->info['php']) < 0;
        // Confirm that all base themes are available.
        $theme->incompatible_base = (isset($theme->info['base theme']) && !($theme->base_themes === array_filter($theme->base_themes)));
        // Confirm that the theme engine is available.
        $theme->incompatible_engine = isset($theme->info['engine']) && !isset($theme->owner);
        // Confirm that module dependencies are available.
        $theme->incompatible_module = FALSE;
        // Confirm that the user has permission to enable modules.
        $theme->insufficient_module_permissions = FALSE;
      }

      // Check module dependencies.
      if ($theme->module_dependencies) {
        $modules = $this->moduleExtensionList->getList();
        foreach ($theme->module_dependencies as $dependency => $dependency_object) {
          if ($incompatible = $this->checkDependencyMessage($modules, $dependency, $dependency_object)) {
            $theme->module_dependencies_list[$dependency] = $incompatible;
            $theme->incompatible_module = TRUE;
            continue;
          }

          // @todo Add logic for not displaying hidden modules in
          //   https://drupal.org/node/3117829.
          $module_name = $modules[$dependency]->info['name'];
          $theme->module_dependencies_list[$dependency] = $modules[$dependency]->status ? $this->t('@module_name', ['@module_name' => $module_name]) : $this->t('@module_name (<span class="admin-disabled">disabled</span>)', ['@module_name' => $module_name]);

          // Create an additional property that contains only disabled module
          // dependencies. This will determine if it is possible to install the
          // theme, or if modules must first be enabled.
          if (!$modules[$dependency]->status) {
            $theme->module_dependencies_disabled[$dependency] = $module_name;
            if (!$this->currentUser()->hasPermission('administer modules')) {
              $theme->insufficient_module_permissions = TRUE;
            }
          }
        }
      }

      $theme->operations = [];
      if (!empty($theme->status) || !$theme->info['core_incompatible'] && !$theme->incompatible_php && !$theme->incompatible_base && !$theme->incompatible_engine && !$theme->incompatible_module && empty($theme->module_dependencies_disabled)) {
        // Create the operations links.
        $query['theme'] = $theme->getName();
        if ($this->themeAccess->checkAccess($theme->getName())) {
          $theme->operations[] = [
            'title' => $this->t('Settings'),
            'url' => Url::fromRoute('system.theme_settings_theme', ['theme' => $theme->getName()]),
            'attributes' => ['title' => $this->t('Settings for @theme theme', ['@theme' => $theme->info['name']])],
          ];
        }
        if (!empty($theme->status)) {
          if (!$theme->is_default) {
            $theme_uninstallable = TRUE;
            if ($theme->getName() == $admin_theme) {
              $theme_uninstallable = FALSE;
            }
            // Check it isn't the base of theme of an installed theme.
            foreach ($theme->required_by as $themename => $dependency) {
              if (!empty($themes[$themename]->status)) {
                $theme_uninstallable = FALSE;
              }
            }
            if ($theme_uninstallable) {
              $theme->operations[] = [
                'title' => $this->t('Uninstall'),
                'url' => Url::fromRoute('system.theme_uninstall'),
                'query' => $query,
                'attributes' => ['title' => $this->t('Uninstall @theme theme', ['@theme' => $theme->info['name']])],
              ];
            }
            $theme->operations[] = [
              'title' => $this->t('Set as default'),
              'url' => Url::fromRoute('system.theme_set_default'),
              'query' => $query,
              'attributes' => ['title' => $this->t('Set @theme as default theme', ['@theme' => $theme->info['name']])],
            ];
          }
          $admin_theme_options[$theme->getName()] = $theme->info['name'] . ($theme->is_experimental ? ' (' . t('Experimental') . ')' : '');
        }
        else {
          $theme->operations[] = [
            'title' => $this->t('Install'),
            'url' => Url::fromRoute('system.theme_install'),
            'query' => $query,
            'attributes' => ['title' => $this->t('Install @theme theme', ['@theme' => $theme->info['name']])],
          ];
          $theme->operations[] = [
            'title' => $this->t('Install and set as default'),
            'url' => Url::fromRoute('system.theme_set_default'),
            'query' => $query,
            'attributes' => ['title' => $this->t('Install @theme as default theme', ['@theme' => $theme->info['name']])],
          ];
        }
      }

      // Add notes to default theme, administration theme and experimental
      // themes.
      $theme->notes = [];
      if ($theme->is_default) {
        $theme->notes[] = $this->t('default theme');
      }
      if ($theme->is_admin) {
        $theme->notes[] = $this->t('administration theme');
      }
      if ($theme->is_experimental) {
        $theme->notes[] = $this->t('experimental theme');
      }

      // Sort installed and uninstalled themes into their own groups.
      $theme_groups[$theme->status ? 'installed' : 'uninstalled'][] = $theme;
    }

    // There are two possible theme groups.
    $theme_group_titles = [
      'installed' => $this->formatPlural(count($theme_groups['installed']), 'Installed theme', 'Installed themes'),
    ];
    if (!empty($theme_groups['uninstalled'])) {
      $theme_group_titles['uninstalled'] = $this->formatPlural(count($theme_groups['uninstalled']), 'Uninstalled theme', 'Uninstalled themes');
    }

    uasort($theme_groups['installed'], 'system_sort_themes');
    $this->moduleHandler()->alter('system_themes_page', $theme_groups);

    $build = [];
    $build[] = [
      '#theme' => 'system_themes_page',
      '#theme_groups' => $theme_groups,
      '#theme_group_titles' => $theme_group_titles,
    ];
    $build[] = $this->formBuilder->getForm('Drupal\system\Form\ThemeAdminForm', $admin_theme_options);

    return $build;
  }

  /**
   * Uninstalls a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the appearance admin page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme or token is set in the request or when
   *   the token is invalid.
   */
  public function uninstall(Request $request) {
    $theme = $request->query->get('theme');
    $config = $this->config('system.theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();

      // Check if the specified theme is one recognized by the system.
      if (!empty($themes[$theme])) {
        // Do not uninstall the default or admin theme.
        if ($theme === $config->get('default') || $theme === $config->get('admin')) {
          $this->messenger()->addError($this->t('%theme is the default theme and cannot be uninstalled.', ['%theme' => $themes[$theme]->info['name']]));
        }
        else {
          $this->themeInstaller->uninstall([$theme]);
          $this->messenger()->addStatus($this->t('The %theme theme has been uninstalled.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Installs a theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name and a valid token.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Redirects back to the appearance admin page or the confirmation form
   *   if an experimental theme will be installed.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme or token is set in the request or when
   *   the token is invalid.
   */
  public function install(Request $request) {
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      // Display confirmation form in case of experimental theme.
      if ($this->willInstallExperimentalTheme($theme)) {
        return $this->formBuilder()->getForm(ThemeExperimentalConfirmForm::class, $theme);
      }

      try {
        if ($this->themeInstaller->install([$theme])) {
          $themes = $this->themeHandler->listInfo();
          $this->messenger()->addStatus($this->t('The %theme theme has been installed.', ['%theme' => $themes[$theme]->info['name']]));
        }
        else {
          $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
        }
      }
      catch (PreExistingConfigException $e) {
        $config_objects = $e->flattenConfigObjects($e->getConfigObjects());
        $this->messenger()->addError(
          $this->formatPlural(
            count($config_objects),
            'Unable to install @extension, %config_names already exists in active configuration.',
            'Unable to install @extension, %config_names already exist in active configuration.',
            [
              '%config_names' => implode(', ', $config_objects),
              '@extension' => $theme,
            ])
        );
      }
      catch (UnmetDependenciesException $e) {
        $this->messenger()->addError($e->getTranslatedMessage($this->getStringTranslation(), $theme));
      }
      catch (MissingDependencyException $e) {
        $this->messenger()->addError($this->t('Unable to install @theme due to missing module dependencies.', ['@theme' => $theme]));
      }

      return $this->redirect('system.themes_page');
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Checks if the given theme requires the installation of experimental themes.
   *
   * @param string $theme
   *   The name of the theme to check.
   *
   * @return bool
   *   Whether experimental themes will be installed.
   */
  protected function willInstallExperimentalTheme($theme) {
    $all_themes = $this->themeList->getList();
    $dependencies = array_keys($all_themes[$theme]->requires);
    $themes_to_enable = array_merge([$theme], $dependencies);

    foreach ($themes_to_enable as $name) {
      if (!empty($all_themes[$name]->info['experimental']) && $all_themes[$name]->status === 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Set the default theme.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object containing a theme name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   Redirects back to the appearance admin page or the confirmation form
   *   if an experimental theme will be installed.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Throws access denied when no theme is set in the request.
   */
  public function setDefaultTheme(Request $request) {
    $config = $this->configFactory->getEditable('system.theme');
    $theme = $request->query->get('theme');

    if (isset($theme)) {
      // Get current list of themes.
      $themes = $this->themeHandler->listInfo();
      // Display confirmation form if an experimental theme is being installed.
      if ($this->willInstallExperimentalTheme($theme)) {
        return $this->formBuilder()->getForm(ThemeExperimentalConfirmForm::class, $theme, TRUE);
      }

      // Check if the specified theme is one recognized by the system.
      // Or try to install the theme.
      if (isset($themes[$theme]) || $this->themeInstaller->install([$theme])) {
        $themes = $this->themeHandler->listInfo();

        // Set the default theme.
        $config->set('default', $theme)->save();

        // The status message depends on whether an admin theme is currently in
        // use: a value of 0 means the admin theme is set to be the default
        // theme.
        $admin_theme = $config->get('admin');
        if (!empty($admin_theme) && $admin_theme != $theme) {
          $this->messenger()
            ->addStatus($this->t('Please note that the administration theme is still set to the %admin_theme theme; consequently, the theme on this page remains unchanged. All non-administrative sections of the site, however, will show the selected %selected_theme theme by default.', [
              '%admin_theme' => $themes[$admin_theme]->info['name'],
              '%selected_theme' => $themes[$theme]->info['name'],
            ]));
        }
        else {
          $this->messenger()->addStatus($this->t('%theme is now the default theme.', ['%theme' => $themes[$theme]->info['name']]));
        }
      }
      else {
        $this->messenger()->addError($this->t('The %theme theme was not found.', ['%theme' => $theme]));
      }

      return $this->redirect('system.themes_page');

    }
    throw new AccessDeniedHttpException();
  }

}
