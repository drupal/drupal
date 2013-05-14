<?php

/**
 * @file
 * Contains \Drupal\config\Controller\ConfigController
 */

namespace Drupal\config\Controller;

use Drupal\Core\ControllerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for config module routes.
 */
class ConfigController implements ControllerInterface {

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface;
   */
  protected $targetStorage;

  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface;
   */
  protected $sourceStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.storage'), $container->get('config.storage.staging'));
  }

  /**
   * Constructs a ConfigController object.
   *
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source storage
   */
  public function __construct(StorageInterface $target_storage, StorageInterface $source_storage) {
    $this->targetStorage = $target_storage;
    $this->sourceStorage = $source_storage;
  }

  /**
   * Shows diff of specificed configuration file.
   *
   * @param string $config_file
   *   The name of the configuration file.
   *
   * @return string
   *   Table showing a two-way diff between the active and staged configuration.
   */
  public function diff($config_file) {
    // Add the CSS for the inline diff.
    $output['#attached']['css'][] = drupal_get_path('module', 'system') . '/system.diff.css';

    $diff = config_diff($this->targetStorage, $this->sourceStorage, $config_file);
    $formatter = new \DrupalDiffFormatter();
    $formatter->show_header = FALSE;

    $variables = array(
      'header' => array(
        array('data' => t('Old'), 'colspan' => '2'),
        array('data' => t('New'), 'colspan' => '2'),
      ),
      'rows' => $formatter->format($diff),
    );

    $output['diff'] = array(
      '#markup' => theme('table', $variables),
    );

    $output['back'] = array(
      '#type' => 'link',
      '#attributes' => array(
        'class' => array(
          'dialog-cancel',
        ),
      ),
      '#title' => "Back to 'Synchronize configuration' page.",
      '#href' => 'admin/config/development/sync',
    );

    // @todo Remove use of drupal_set_title() when
    //   http://drupal.org/node/1871596 is in.
    drupal_set_title(t('View changes of @config_file', array('@config_file' => $config_file)), PASS_THROUGH);

    return $output;
  }
}
