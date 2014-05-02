<?php

/**
 * @file
 * Contains \Drupal\system\Controller\SystemController.
 */

namespace Drupal\system\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Theme\ThemeAccessCheck;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for System routes.
 */
class SystemController extends ControllerBase {

  /**
   * The entity query factory object.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

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
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new SystemController.
   *
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   The entity query object.
   * @param \Drupal\Core\Theme\ThemeAccessCheck $theme_access
   *   The theme access checker service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(SystemManager $systemManager, QueryFactory $queryFactory, ThemeAccessCheck $theme_access, FormBuilderInterface $form_builder, ThemeHandlerInterface $theme_handler) {
    $this->systemManager = $systemManager;
    $this->queryFactory = $queryFactory;
    $this->themeAccess = $theme_access;
    $this->formBuilder = $form_builder;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system.manager'),
      $container->get('entity.query'),
      $container->get('access_check.theme'),
      $container->get('form_builder'),
      $container->get('theme_handler')
    );
  }

  /**
   * Provide the administration overview page.
   *
   * @return array
   *   A renderable array of the administration overview page.
   */
  public function overview() {
    // Check for status report errors.
    if ($this->systemManager->checkRequirements() && $this->currentUser()->hasPermission('administer site configuration')) {
      drupal_set_message($this->t('One or more problems were detected with your Drupal installation. Check the <a href="@status">status report</a> for more information.', array('@status' => url('admin/reports/status'))), 'error');
    }
    $blocks = array();
    // Load all links on admin/config and menu links below it.
    $query = $this->queryFactory->get('menu_link')
      ->condition('link_path', 'admin/config')
      ->condition('module', 'system');
    $result = $query->execute();
    $menu_link_storage = $this->entityManager()->getStorage('menu_link');
    if ($system_link = $menu_link_storage->loadMultiple($result)) {
      $system_link = reset($system_link);
      $query = $this->queryFactory->get('menu_link')
        ->condition('link_path', 'admin/help', '<>')
        ->condition('menu_name', $system_link->menu_name)
        ->condition('plid', $system_link->id())
        ->condition('hidden', 0);
      $result = $query->execute();
      if (!empty($result)) {
        $menu_links = $menu_link_storage->loadMultiple($result);
        foreach ($menu_links as $item) {
          _menu_link_translate($item);
          if (!$item['access']) {
            continue;
          }
          // The link description, either derived from 'description' in hook_menu()
          // or customized via Menu UI module is used as title attribute.
          if (!empty($item['localized_options']['attributes']['title'])) {
            $item['description'] = $item['localized_options']['attributes']['title'];
            unset($item['localized_options']['attributes']['title']);
          }
          $block = $item;
          $block['content'] = array(
            '#theme' => 'admin_block_content',
            '#content' => $this->systemManager->getAdminBlock($item),
          );

          if (!empty($block['content']['#content'])) {
            // Prepare for sorting as in function _menu_tree_check_access().
            // The weight is offset so it is always positive, with a uniform 5-digits.
            $blocks[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $block;
          }
        }
      }
    }
    if ($blocks) {
      ksort($blocks);
      return array(
        '#theme' => 'admin_page',
        '#blocks' => $blocks,
      );
    }
    else {
      return array(
        '#markup' => $this->t('You do not have any administrative items.'),
      );
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
    user_cookie_save(array('admin_compact_mode' => ($mode == 'on')));
    return $this->redirect('<front>');
  }

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function systemAdminMenuBlockPage() {
    return $this->systemManager->getBlockContents();
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
    $theme_groups  = array('enabled' => array(), 'disabled' => array());
    $admin_theme = $config->get('admin');
    $admin_theme_options = array();

    foreach ($themes as &$theme) {
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      $theme->is_default = ($theme->getName() == $theme_default);

      // Identify theme screenshot.
      $theme->screenshot = NULL;
      // Create a list which includes the current theme and all its base themes.
      if (isset($themes[$theme->getName()]->base_themes)) {
        $theme_keys = array_keys($themes[$theme->getName()]->base_themes);
        $theme_keys[] = $theme->getName();
      }
      else {
        $theme_keys = array($theme->getName());
      }
      // Look for a screenshot in the current theme or in its closest ancestor.
      foreach (array_reverse($theme_keys) as $theme_key) {
        if (isset($themes[$theme_key]) && file_exists($themes[$theme_key]->info['screenshot'])) {
          $theme->screenshot = array(
            'uri' => $themes[$theme_key]->info['screenshot'],
            'alt' => $this->t('Screenshot for !theme theme', array('!theme' => $theme->info['name'])),
            'title' => $this->t('Screenshot for !theme theme', array('!theme' => $theme->info['name'])),
            'attributes' => array('class' => array('screenshot')),
          );
          break;
        }
      }

      if (empty($theme->status)) {
        // Ensure this theme is compatible with this version of core.
        // Require the 'content' region to make sure the main page
        // content has a common place in all themes.
        $theme->incompatible_core = !isset($theme->info['core']) || ($theme->info['core'] != \DRUPAL::CORE_COMPATIBILITY) || !isset($theme->info['regions']['content']);
        $theme->incompatible_php = version_compare(phpversion(), $theme->info['php']) < 0;
        // Confirmed that the base theme is available.
        $theme->incompatible_base = isset($theme->info['base theme']) && !isset($themes[$theme->info['base theme']]);
        // Confirm that the theme engine is available.
        $theme->incompatible_engine = isset($theme->info['engine']) && !isset($theme->owner);
      }
      $theme->operations = array();
      if (!empty($theme->status) || !$theme->incompatible_core && !$theme->incompatible_php && !$theme->incompatible_base && !$theme->incompatible_engine) {
        // Create the operations links.
        $query['theme'] = $theme->getName();
        if ($this->themeAccess->checkAccess($theme->getName())) {
          $theme->operations[] = array(
            'title' => $this->t('Settings'),
            'route_name' => 'system.theme_settings_theme',
            'route_parameters' => array('theme' => $theme->getName()),
            'attributes' => array('title' => $this->t('Settings for !theme theme', array('!theme' => $theme->info['name']))),
          );
        }
        if (!empty($theme->status)) {
          if (!$theme->is_default) {
            if ($theme->getName() != $admin_theme) {
              $theme->operations[] = array(
                'title' => $this->t('Disable'),
                'route_name' => 'system.theme_disable',
                'query' => $query,
                'attributes' => array('title' => $this->t('Disable !theme theme', array('!theme' => $theme->info['name']))),
              );
            }
            $theme->operations[] = array(
              'title' => $this->t('Set as default'),
              'route_name' => 'system.theme_set_default',
              'query' => $query,
              'attributes' => array('title' => $this->t('Set !theme as default theme', array('!theme' => $theme->info['name']))),
            );
          }
          $admin_theme_options[$theme->getName()] = $theme->info['name'];
        }
        else {
          $theme->operations[] = array(
            'title' => $this->t('Enable'),
            'route_name' => 'system.theme_enable',
            'query' => $query,
            'attributes' => array('title' => $this->t('Enable !theme theme', array('!theme' => $theme->info['name']))),
          );
          $theme->operations[] = array(
            'title' => $this->t('Enable and set as default'),
            'route_name' => 'system.theme_set_default',
            'query' => $query,
            'attributes' => array('title' => $this->t('Enable !theme as default theme', array('!theme' => $theme->info['name']))),
          );
        }
      }

      // Add notes to default and administration theme.
      $theme->notes = array();
      $theme->classes = array();
      if ($theme->is_default) {
        $theme->classes[] = 'theme-default';
        $theme->notes[] = $this->t('default theme');
      }
      if ($theme->getName() == $admin_theme || ($theme->is_default && $admin_theme == '0')) {
        $theme->classes[] = 'theme-admin';
        $theme->notes[] = $this->t('admin theme');
      }

      // Sort enabled and disabled themes into their own groups.
      $theme_groups[$theme->status ? 'enabled' : 'disabled'][] = $theme;
    }

    // There are two possible theme groups.
    $theme_group_titles = array(
      'enabled' => $this->formatPlural(count($theme_groups['enabled']), 'Enabled theme', 'Enabled themes'),
    );
    if (!empty($theme_groups['disabled'])) {
      $theme_group_titles['disabled'] = $this->formatPlural(count($theme_groups['disabled']), 'Disabled theme', 'Disabled themes');
    }

    uasort($theme_groups['enabled'], 'system_sort_themes');
    $this->moduleHandler()->alter('system_themes_page', $theme_groups);

    $build = array();
    $build[] = array(
      '#theme' => 'system_themes_page',
      '#theme_groups' => $theme_groups,
      '#theme_group_titles' => $theme_group_titles,
    );
    $build[] = $this->formBuilder->getForm('Drupal\system\Form\ThemeAdminForm', $admin_theme_options);

    return $build;
  }

  /**
   * #post_render_cache callback; sets the "active" class on relevant links.
   *
   * This is a PHP implementation of the drupal.active-link JavaScript library.
   *
   * @param array $element
   *  A renderable array with the following keys:
   *    - #markup
   *    - #attached
   * @param array $context
   *   An array with the following keys:
   *   - path: the system path of the currently active page
   *   - front: whether the current page is the front page (which implies the
   *     current path might also be <front>)
   *   - language: the language code of the currently active page
   *   - query: the query string for the currently active page
   *
   * @return array
   *   The updated renderable array.
   *
   * @todo Once a future version of PHP supports parsing HTML5 properly
   *   (i.e. doesn't fail on https://drupal.org/comment/7938201#comment-7938201)
   *   then we can get rid of this manual parsing and use DOMDocument instead.
   */
  public static function setLinkActiveClass(array $element, array $context) {
    $search_key_current_path = 'data-drupal-link-system-path="' . $context['path'] . '"';
    $search_key_front = 'data-drupal-link-system-path="&lt;front&gt;"';

    // An active link's path is equal to the current path, so search the HTML
    // for an attribute with that value.
    $offset = 0;
    while ((strpos($element['#markup'], 'data-drupal-link-system-path="' . $context['path'] . '"', $offset) !== FALSE || ($context['front'] && strpos($element['#markup'], 'data-drupal-link-system-path="&lt;front&gt;"', $offset) !== FALSE))) {
      $pos_current_path = strpos($element['#markup'], $search_key_current_path, $offset);
      $pos_front = strpos($element['#markup'], $search_key_front, $offset);

      // Determine which of the two values matched: the exact path, or the
      // <front> special case.
      $pos_match = NULL;
      $type_match = NULL;
      if ($pos_current_path !== FALSE) {
        $pos_match = $pos_current_path;
        $type_match = 'path';
      }
      elseif ($context['front'] && $pos_front !== FALSE) {
        $pos_match = $pos_front;
        $type_match = 'front';
      }

      // Find beginning and ending of opening tag.
      $pos_tag_start = NULL;
      for ($i = $pos_match; $pos_tag_start === NULL && $i > 0; $i--) {
        if ($element['#markup'][$i] === '<') {
          $pos_tag_start = $i;
        }
      }
      $pos_tag_end = NULL;
      for ($i = $pos_match; $pos_tag_end === NULL && $i < strlen($element['#markup']); $i++) {
        if ($element['#markup'][$i] === '>') {
          $pos_tag_end = $i;
        }
      }

      // Get the HTML: this will be the opening part of a single tag, e.g.:
      //   <a href="/" data-drupal-link-system-path="&lt;front&gt;">
      $tag = substr($element['#markup'], $pos_tag_start, $pos_tag_end - $pos_tag_start + 1);

      // Parse it into a DOMDocument so we can reliably read and modify
      // attributes.
      $dom = new \DOMDocument();
      @$dom->loadHTML('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $tag . '</body></html>');
      $node = $dom->getElementsByTagName('body')->item(0)->firstChild;

      // The language of an active link is equal to the current language.
      $is_active = TRUE;
      if ($context['language']) {
        if ($node->hasAttribute('hreflang') && $node->getAttribute('hreflang') !== $context['language']) {
          $is_active = FALSE;
        }
      }
      // The query parameters of an active link are equal to the current
      // parameters.
      if ($is_active) {
        if ($context['query']) {
          if (!$node->hasAttribute('data-drupal-link-query') || $node->getAttribute('data-drupal-link-query') !== Json::encode($context['query'])) {
            $is_active = FALSE;
          }
        }
        else {
          if ($node->hasAttribute('data-drupal-link-query')) {
            $is_active = FALSE;
          }
        }
      }

      // Only if the the path, the language and the query match, we set the
      // "active" class.
      if ($is_active) {
        $class = $node->getAttribute('class');
        if (strlen($class) > 0) {
          $class .= ' ';
        }
        $class .= 'active';
        $node->setAttribute('class', $class);

        // Get the updated tag.
        $updated_tag = $dom->saveXML($node, LIBXML_NOEMPTYTAG);
        // saveXML() added a closing tag, remove it.
        $updated_tag = substr($updated_tag, 0, strrpos($updated_tag, '<'));

        $element['#markup'] = str_replace($tag, $updated_tag, $element['#markup']);

        // Ensure we only search the remaining HTML.
        $offset = $pos_tag_end - strlen($tag) + strlen($updated_tag);
      }
      else {
        // Ensure we only search the remaining HTML.
        $offset = $pos_tag_end + 1;
      }
    }

    return $element;
  }

}
