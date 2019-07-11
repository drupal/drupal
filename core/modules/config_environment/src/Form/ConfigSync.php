<?php

namespace Drupal\config_environment\Form;

use Drupal\config\Form\ConfigSync as OriginalConfigSync;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the ConfigSync form.
 */
class ConfigSync extends OriginalConfigSync {

  /**
   * The import transformer service.
   *
   * @var \Drupal\Core\Config\ImportStorageTransformer
   */
  protected $importTransformer;

  /**
   * The sync storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $originalSyncStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->importTransformer = $container->get('config.import_transformer');
    $form->originalSyncStorage = $form->syncStorage;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->syncStorage = $this->importTransformer->transform($this->originalSyncStorage);

    return parent::buildForm($form, $form_state);
  }

}
