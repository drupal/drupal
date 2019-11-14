<?php

namespace Drupal\media\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a confirmation form to delete multiple media items at once.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0.
 *   This route is not used in Drupal core. As an internal API, it may also be
 *   removed in a minor release. If you are using it, copy the class
 *   and the related "entity.media.multiple_delete_confirm" route to your
 *   module.
 *
 * @internal
 */
class MediaDeleteMultipleConfirmForm extends ConfirmFormBase {

  /**
   * The array of media items to delete, indexed by ID and language.
   *
   * @var string[][]
   */
  protected $mediaItems = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a MediaDeleteMultipleConfirmForm form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager) {
    @trigger_error(__CLASS__ . ' is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. It is not used in Drupal core. As an internal API, it may also be removed in a minor release. If you are using it, copy the class and the related "entity.media.multiple_delete_confirm" route to your module.', E_USER_DEPRECATED);
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->mediaItems), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // @todo Change to media library when #2834729 is done.
    // https://www.drupal.org/node/2834729.
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Change to trait or base class when #2843395 is done.
   * @see https://www.drupal.org/node/2843395
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->mediaItems = $this->tempStoreFactory->get('media_multiple_delete_confirm')->get($this->currentUser()->id());
    if (empty($this->mediaItems)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\media\MediaInterface[] $entities */
    $entities = $this->storage->loadMultiple(array_keys($this->mediaItems));

    $items = [];
    foreach ($this->mediaItems as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $entity = $entities[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $entity->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $entity->getTranslationLanguages();
        if (count($languages) > 1 && $entity->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following translations will be deleted:</em>', ['@label' => $entity->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $entity->label();
        }
      }
    }

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Change to trait or base class when #2843395 is done.
   * @see https://www.drupal.org/node/2843395
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->mediaItems)) {
      $total_count = 0;
      $delete_entities = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\media\MediaInterface[] $entities */
      $entities = $this->storage->loadMultiple(array_keys($this->mediaItems));

      foreach ($this->mediaItems as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $entity = $entities[$id]->getTranslation($langcode);
          if ($entity->isDefaultTranslation()) {
            $delete_entities[$id] = $entity;
            unset($delete_translations[$id]);
            $total_count += count($entity->getTranslationLanguages());
          }
          elseif (!isset($delete_entities[$id])) {
            $delete_translations[$id][] = $entity;
          }
        }
      }

      if ($delete_entities) {
        $this->storage->delete($delete_entities);
        $this->logger('media')->notice('Deleted @count media items.', ['@count' => count($delete_entities)]);
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $entity = $entities[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $entity->removeTranslation($translation->language()->getId());
          }
          $entity->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('media')->notice('Deleted @count media translations.', ['@count' => $count]);
        }
      }

      if ($total_count) {
        $this->messenger()->addStatus($this->formatPlural($total_count, 'Deleted 1 media item.', 'Deleted @count media items.'));
      }

      $this->tempStoreFactory->get('media_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    // @todo Change to media library when #2834729 is done.
    // https://www.drupal.org/node/2834729.
    $form_state->setRedirect('system.admin_content');
  }

}
