<?php

/**
 * @file
 * Contains \Drupal\system\Controller\SystemController.
 */

namespace Drupal\system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for System routes.
 */
class SystemController extends ControllerBase implements ContainerInjectionInterface {

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
   * Constructs a new SystemController.
   *
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   The entity query object.
   */
  public function __construct(SystemManager $systemManager, QueryFactory $queryFactory) {
    $this->systemManager = $systemManager;
    $this->queryFactory = $queryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system.manager'),
      $container->get('entity.query')
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
    $menu_link_storage = $this->entityManager()->getStorageController('menu_link');
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
          // or customized via menu module is used as title attribute.
          if (!empty($item['localized_options']['attributes']['title'])) {
            $item['description'] = $item['localized_options']['attributes']['title'];
            unset($item['localized_options']['attributes']['title']);
          }
          $block = $item;
          $block['content'] = array(
            '#theme' => 'admin_block_content',
            '#content' => $this->systemManager->getAdminBlock($item),
          );

          if (!empty($block['content'])) {
            $block['show'] = TRUE;
          }

          // Prepare for sorting as in function _menu_tree_check_access().
          // The weight is offset so it is always positive, with a uniform 5-digits.
          $blocks[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $block;
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
   * @todo Remove system_themes_page().
   */
  public function themesPage() {
    module_load_include('admin.inc', 'system');
    return system_themes_page();
  }

  /**
   * @todo Remove system_theme_default().
   */
  public function themeSetDefault() {
    module_load_include('admin.inc', 'system');
    return system_theme_default();
  }

}
