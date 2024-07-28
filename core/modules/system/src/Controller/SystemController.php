<?php

namespace Drupal\system\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Core\Extension\ModuleDependencyMessageTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Theme\ThemeAccessCheck;
use Drupal\Core\Url;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for System routes.
 */
class SystemController extends ControllerBase {

  use ModuleDependencyMessageTrait;

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

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
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected ThemeExtensionList $themeExtensionList;

  /**
   * Constructs a new SystemController.
   *
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   * @param \Drupal\Core\Theme\ThemeAccessCheck $theme_access
   *   The theme access checker service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list
   *   The theme extension list.
   */
  public function __construct(SystemManager $systemManager, ThemeAccessCheck $theme_access, FormBuilderInterface $form_builder, MenuLinkTreeInterface $menu_link_tree, ModuleExtensionList $module_extension_list, ThemeExtensionList $theme_extension_list) {
    $this->systemManager = $systemManager;
    $this->themeAccess = $theme_access;
    $this->formBuilder = $form_builder;
    $this->menuLinkTree = $menu_link_tree;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system.manager'),
      $container->get('access_check.theme'),
      $container->get('form_builder'),
      $container->get('menu.link_tree'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
    );
  }

  /**
   * Provide the administration overview page.
   *
   * This will render child links two levels below the specified link ID,
   * grouped by the child links one level below.
   *
   * @param string $link_id
   *   The ID of the administrative path link for which to display child links.
   *
   * @return array
   *   A renderable array of the administration overview page.
   */
  public function overview($link_id) {
    // Check for status report errors.
    if ($this->currentUser()->hasPermission('administer site configuration') && $this->systemManager->checkRequirements()) {
      $this->messenger()->addError($this->t('One or more problems were detected with your Drupal installation. Check the <a href=":status">status report</a> for more information.', [':status' => Url::fromRoute('system.status')->toString()]));
    }
    // Load all menu links below it.
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($link_id)->excludeRoot()->setTopLevelOnly()->onlyEnabledLinks();
    $tree = $this->menuLinkTree->load(NULL, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $tree_access_cacheability = new CacheableMetadata();
    $blocks = [];
    foreach ($tree as $key => $element) {
      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      $link = $element->link;
      $block['title'] = $link->getTitle();
      $block['description'] = $link->getDescription();
      $block['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $this->systemManager->getAdminBlock($link),
      ];

      if (!empty($block['content']['#content'])) {
        $blocks[$key] = $block;
      }
    }

    if ($blocks) {
      ksort($blocks);
      $build = [
        '#theme' => 'admin_page',
        '#blocks' => $blocks,
      ];
      $tree_access_cacheability->applyTo($build);
      return $build;
    }
    else {
      $build = [
        '#markup' => $this->t('You do not have any administrative items.'),
      ];
      $tree_access_cacheability->applyTo($build);
      return $build;
    }
  }

  /**
   * Sets whether the admin menu is in compact mode or not.
   *
   * @param string $mode
   *   Valid values are 'on' and 'off'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function compactPage($mode) {
    user_cookie_save(['admin_compact_mode' => ($mode == 'on')]);
    return $this->redirect('<front>');
  }

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function systemAdminMenuBlockPage() {
    return $this->systemManager->getBlockContents();
  }

  /**
   * Returns a theme listing which excludes obsolete themes.
   *
   * @return string
   *   An HTML string of the theme listing page.
   *
   * @todo Move into ThemeController.
   */
  public function themesPage() {
    $config = $this->config('system.theme');
    // Get all available themes.
    $themes = $this->themeExtensionList->reset()->getList();

    // Remove obsolete themes.
    $themes = array_filter($themes, function ($theme) {
      return !$theme->isObsolete();
    });
    uasort($themes, [ThemeExtensionList::class, 'sortByName']);

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
          $admin_theme_options[$theme->getName()] = $theme->info['name'] . ($theme->isExperimental() ? ' (' . $this->t('Experimental') . ')' : '');
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

      // Add notes to default theme, administration theme and non-stable themes.
      $theme->notes = [];
      if ($theme->is_default) {
        $theme->notes[] = $this->t('default theme');
      }
      if ($theme->is_admin) {
        $theme->notes[] = $this->t('administration theme');
      }
      $lifecycle = $theme->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER];
      if (!empty($theme->info[ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER])) {
        $theme->notes[] = Link::fromTextAndUrl($this->t('@lifecycle', ['@lifecycle' => ucfirst($lifecycle)]),
          Url::fromUri($theme->info[ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER], [
            'attributes' =>
              [
                'class' => 'theme-link--non-stable',
                'aria-label' => $this->t('View information on the @lifecycle status of the theme @theme', [
                  '@lifecycle' => ucfirst($lifecycle),
                  '@theme' => $theme->info['name'],
                ]),
              ],
          ])
        )->toString();
      }
      if ($theme->isExperimental() && empty($theme->info[ExtensionLifecycle::LIFECYCLE_LINK_IDENTIFIER])) {
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

}
