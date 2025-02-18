<?php

namespace Drupal\config\Hook;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config.
 */
class ConfigHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.config':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Configuration Manager module provides a user interface for importing and exporting configuration changes between installations of your website in different environments. Configuration is stored in YAML format. For more information, see the <a href=":url">online documentation for the Configuration Manager module</a>.', [':url' => 'https://www.drupal.org/documentation/administer/config']) . '</p>';
        $output .= '<h2>' . $this->t('Uses') . '</h2>';
        $output .= '<dl>';
        $output .= '<dt>' . $this->t('Exporting the full configuration') . '</dt>';
        $output .= '<dd>' . $this->t('You can create and download an archive consisting of all your site\'s configuration exported as <em>*.yml</em> files on the <a href=":url">Export</a> page.', [':url' => Url::fromRoute('config.export_full')->toString()]) . '</dd>';
        $output .= '<dt>' . $this->t('Importing a full configuration') . '</dt>';
        $output .= '<dd>' . $this->t('You can upload a full site configuration from an archive file on the <a href=":url">Import</a> page. When importing data from a different environment, the site and import files must have matching configuration values for UUID in the <em>system.site</em> configuration item. That means that your other environments should initially be set up as clones of the target site. Migrations are not supported.', [':url' => Url::fromRoute('config.import_full')->toString()]) . '</dd>';
        $output .= '<dt>' . $this->t('Synchronizing configuration') . '</dt>';
        $output .= '<dd>' . $this->t('You can review differences between the active configuration and an imported configuration archive on the <a href=":synchronize">Synchronize</a> page to ensure that the changes are as expected, before finalizing the import. The Synchronize page also shows configuration items that would be added or removed.', [':synchronize' => Url::fromRoute('config.sync')->toString()]) . '</dd>';
        $output .= '<dt>' . $this->t('Exporting a single configuration item') . '</dt>';
        $output .= '<dd>' . $this->t('You can export a single configuration item by selecting a <em>Configuration type</em> and <em>Configuration name</em> on the <a href=":single-export">Single export</a> page. The configuration and its corresponding <em>*.yml file name</em> are then displayed on the page for you to copy.', [
          ':single-export' => Url::fromRoute('config.export_single')->toString(),
        ]) . '</dd>';
        $output .= '<dt>' . $this->t('Importing a single configuration item') . '</dt>';
        $output .= '<dd>' . $this->t('You can import a single configuration item by pasting it in YAML format into the form on the <a href=":single-import">Single import</a> page.', [
          ':single-import' => Url::fromRoute('config.import_single')->toString(),
        ]) . '</dd>';
        $output .= '</dl>';
        return $output;

      case 'config.sync':
        $output = '';
        $output .= '<p>' . $this->t('Compare the configuration uploaded to your sync directory with the active configuration before completing the import.') . '</p>';
        return $output;

      case 'config.export_full':
        $output = '';
        $output .= '<p>' . $this->t('Export and download the full configuration of this site as a gzipped tar file.') . '</p>';
        return $output;

      case 'config.import_full':
        $output = '';
        $output .= '<p>' . $this->t('Upload a full site configuration archive to the sync directory. It can then be compared and imported on the Synchronize page.') . '</p>';
        return $output;

      case 'config.export_single':
        $output = '';
        $output .= '<p>' . $this->t('Choose a configuration item to display its YAML structure.') . '</p>';
        return $output;

      case 'config.import_single':
        $output = '';
        $output .= '<p>' . $this->t('Import a single configuration item by pasting its YAML structure into the text field.') . '</p>';
        return $output;
    }
    return NULL;
  }

  /**
   * Implements hook_file_download().
   */
  #[Hook('file_download')]
  public function fileDownload($uri): array|int|null {
    $scheme = StreamWrapperManager::getScheme($uri);
    $target = StreamWrapperManager::getTarget($uri);
    if ($scheme == 'temporary' && $target == 'config.tar.gz') {
      if (\Drupal::currentUser()->hasPermission('export configuration')) {
        $request = \Drupal::request();
        $date = \DateTime::createFromFormat('U', $request->server->get('REQUEST_TIME'));
        $date_string = $date->format('Y-m-d-H-i');
        $hostname = str_replace('.', '-', $request->getHttpHost());
        $filename = 'config-' . $hostname . '-' . $date_string . '.tar.gz';
        $disposition = 'attachment; filename="' . $filename . '"';
        return ['Content-disposition' => $disposition];
      }
      return -1;
    }
    return NULL;
  }

}
