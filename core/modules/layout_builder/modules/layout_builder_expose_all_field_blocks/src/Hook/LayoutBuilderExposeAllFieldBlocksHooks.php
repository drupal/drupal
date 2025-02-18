<?php

namespace Drupal\layout_builder_expose_all_field_blocks\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for layout_builder_expose_all_field_blocks.
 */
class LayoutBuilderExposeAllFieldBlocksHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.layout_builder_expose_all_field_blocks':
        $output = '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Layout Builder Expose All Field Blocks module is a Feature Flag module which exposes all fields on all bundles as field blocks for use in Layout Builder.') . '</p>';
        $output .= '<p>' . $this->t('Using this feature can significantly reduce the performance of medium to large sites due to the number of Field Block plugins that will be created. It is recommended to uninstall this module, if possible.') . '</p>';
        $output .= '<p>' . $this->t('While it is recommended to uninstall this module, doing so may remove blocks that are being used in your site.') . '</p>';
        $output .= '<p>' . $this->t("For example, if Layout Builder is enabled on a Node bundle (Content type), and that bundle's display is using field blocks from the User entity (e.g. the Author's name), but Layout Builder is not enabled for the User bundle, then that field block would no longer exist after uninstalling this module.") . '</p>';
        $output .= '<p>' . $this->t('For more information, see the <a href=":href">online documentation for the Layout Builder Expose All Field Blocks module</a>.', [
          ':href' => 'https://www.drupal.org/node/3223395#s-layout-builder-expose-all-field-blocks',
        ]) . '</p>';
        return $output;
    }
    return NULL;
  }

}
