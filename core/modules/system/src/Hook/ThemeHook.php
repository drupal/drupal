<?php

namespace Drupal\system\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Theme\ThemeCommonElements;

/**
 * Hook implementations for system.
 */
class ThemeHook {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    $themeCommonElements = ThemeCommonElements::commonElements();

    $systemTheme = [
      // Normally theme suggestion templates are only picked up when they are in
      // themes. We explicitly define theme suggestions here so that the block
      // templates in core/modules/system/templates are picked up.
      'block__system_branding_block' => [
        'render element' => 'elements',
        'base hook' => 'block',
      ],
      'block__system_messages_block' => [
        'base hook' => 'block',
      ],
      'block__system_menu_block' => [
        'render element' => 'elements',
        'base hook' => 'block',
      ],
      'system_themes_page' => [
        'variables' => [
          'theme_groups' => [],
          'theme_group_titles' => [],
        ],
        'file' => 'system.admin.inc',
      ],
      'system_config_form' => [
        'render element' => 'form',
      ],
      'confirm_form' => [
        'render element' => 'form',
      ],
      'system_modules_details' => [
        'render element' => 'form',
        'file' => 'system.admin.inc',
      ],
      'system_modules_uninstall' => [
        'render element' => 'form',
        'file' => 'system.admin.inc',
      ],
      'status_report_page' => [
        'variables' => [
          'counters' => [],
          'general_info' => [],
          'requirements' => NULL,
        ],
      ],
      'status_report' => [
        'variables' => [
          'grouped_requirements' => NULL,
          'requirements' => NULL,
        ],
      ],
      'status_report_counter' => [
        'variables' => [
          'amount' => NULL,
          'text' => NULL,
          'severity' => NULL,
        ],
      ],
      'status_report_general_info' => [
        'variables' => [
          'drupal' => [],
          'cron' => [],
          'database_system' => [],
          'database_system_version' => [],
          'php' => [],
          'php_memory_limit' => [],
          'webserver' => [],
        ],
      ],
      'admin_page' => [
        'variables' => [
          'blocks' => NULL,
        ],
        'file' => 'system.admin.inc',
      ],
      'admin_block' => [
        'variables' => [
          'block' => NULL,
          'attributes' => [],
        ],
        'file' => 'system.admin.inc',
      ],
      'admin_block_content' => [
        'variables' => [
          'content' => NULL,
        ],
        'file' => 'system.admin.inc',
      ],
      'system_admin_index' => [
        'variables' => [
          'menu_items' => NULL,
        ],
        'file' => 'system.admin.inc',
      ],
      'entity_add_list' => [
        'variables' => [
          'bundles' => [],
          'add_bundle_message' => NULL,
        ],
        'template' => 'entity-add-list',
      ],
      'system_security_advisories_fetch_error_message' => [
        'file' => 'system.theme.inc',
        'variables' => [
          'error_message' => [],
        ],
      ],
      'entity_page_title' => [
        'variables' => [
          'attributes' => [],
          'title' => NULL,
          'entity' => NULL,
          'view_mode' => NULL,
        ],
      ],
    ];

    return array_merge($themeCommonElements, $systemTheme);
  }

}
