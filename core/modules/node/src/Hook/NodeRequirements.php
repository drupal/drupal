<?php

declare(strict_types=1);

namespace Drupal\node\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Requirements for the Node module.
 */
class NodeRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly TranslationInterface $translation,
    protected readonly ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    // Only show rebuild button if there are either 0, or 2 or more, rows
    // in the {node_access} table, or if there are modules that
    // implement hook_node_grants().
    $grant_count = $this->entityTypeManager->getAccessControlHandler('node')->countGrants();
    $has_node_grants_implementations = $this->moduleHandler->hasImplementations('node_grants');
    if ($grant_count != 1 || $has_node_grants_implementations) {
      $value = $this->translation->formatPlural($grant_count, 'One permission in use', '@count permissions in use', ['@count' => $grant_count]);
    }
    else {
      $value = $this->t('Disabled');
    }

    $requirements['node_access'] = [
      'title' => $this->t('Node Access Permissions'),
      'value' => $value,
      'description' => $this->t('If the site is experiencing problems with permissions to content, you may have to rebuild the permissions cache. Rebuilding will remove all privileges to content and replace them with permissions based on the current modules and settings. Rebuilding may take some time if there is a lot of content or complex permission settings. After rebuilding has completed, content will automatically use the new permissions. <a href=":rebuild">Rebuild permissions</a>', [
        ':rebuild' => Url::fromRoute('node.configure_rebuild_confirm')->toString(),
      ]),
    ];

    // Report when the "Published status or admin user" has no impact on the
    // result of dependent views due to active node access modules.
    // @see https://www.drupal.org/node/3472976
    if ($has_node_grants_implementations && $this->moduleHandler->moduleExists('views')) {
      $node_status_filter_problematic_views = [];
      $query = $this->entityTypeManager->getStorage('view')->getQuery();
      $query->condition('status', TRUE);
      $query->accessCheck(FALSE);
      $active_view_ids = $query->execute();

      $views_storage = $this->entityTypeManager->getStorage('view');
      foreach ($views_storage->loadMultiple($active_view_ids) as $view) {
        foreach ($view->get('display') as $display_id => $display) {
          if (array_key_exists('filters', $display['display_options'])) {
            foreach ($display['display_options']['filters'] as $filter) {
              if (array_key_exists('plugin_id', $filter) && $filter['plugin_id'] === 'node_status') {
                $node_status_filter_problematic_views[$view->id()][$display_id] = [
                  'view_label' => $view->label(),
                  'display_name' => $display['display_title'] ?? $display_id,
                ];
                break;
              }
            }
          }
        }
      }

      if ($node_status_filter_problematic_views !== []) {
        $node_access_implementations = [];
        $module_data = $this->moduleExtensionList->getAllInstalledInfo();
        foreach (['node_grants', 'node_grants_alter'] as $hook) {
          $this->moduleHandler->invokeAllWith(
            $hook,
            static function (callable $hook, string $module) use (&$node_access_implementations, $module_data) {
              $node_access_implementations[$module] = $module_data[$module]['name'];
            }
          );
        }
        uasort($node_access_implementations, 'strnatcasecmp');
        $views_ui_enabled = $this->moduleHandler->moduleExists('views_ui');
        $node_status_filter_problematic_views_list = [];
        foreach ($node_status_filter_problematic_views as $view_id => $displays) {
          foreach ($displays as $display_id => $info) {
            $text = "{$info['view_label']} ({$info['display_name']})";
            if ($views_ui_enabled) {
              $url = Url::fromRoute('entity.view.edit_display_form', [
                'view' => $view_id,
                'display_id' => $display_id,
              ]);
              if ($url->access()) {
                $node_status_filter_problematic_views_list[] = Link::fromTextAndUrl($text, $url)->toString();
              }
              else {
                $node_status_filter_problematic_views_list[] = $text;
              }
            }
            else {
              $node_status_filter_problematic_views_list[] = $text;
            }
          }
        }

        $node_status_filter_problematic_views_count = count($node_status_filter_problematic_views_list);
        $node_status_filter_description_arguments = [
          '%modules' => implode(', ', $node_access_implementations),
          '%status_filter' => $this->t('Published status or admin user'),
        ];

        if ($node_status_filter_problematic_views_count > 1) {
          $node_status_filter_problematic_views_list = [
            '#theme' => 'item_list',
            '#items' => $node_status_filter_problematic_views_list,
          ];
          $node_status_filter_description_arguments['@views'] = \Drupal::service('renderer')->renderInIsolation($node_status_filter_problematic_views_list);
        }
        else {
          $node_status_filter_description_arguments['%view'] = reset($node_status_filter_problematic_views_list);
        }

        $node_status_filter_description = new PluralTranslatableMarkup(
          $node_status_filter_problematic_views_count,
          'The %view view uses the %status_filter filter but it has no effect because the following module(s) control access: %modules. Review and consider removing the filter.',
          'The following views use the %status_filter filter but it has no effect because the following module(s) control access: %modules. Review and consider removing the filter from these views: @views',
          $node_status_filter_description_arguments,
        );

        $requirements['node_status_filter'] = [
          'title' => $this->t('Content status filter'),
          'value' => $this->t('Redundant filters detected'),
          'description' => $node_status_filter_description,
          'severity' => RequirementSeverity::Warning,
        ];
      }
    }
    return $requirements;
  }

}
