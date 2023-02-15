<?php

namespace Drupal\Core\Entity\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an entities deletion confirmation form.
 */
class DeleteMultipleForm extends ConfirmFormBase implements BaseFormIdInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempStore;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The selection, in the entity_id => langcodes format.
   *
   * @var array
   */
  protected $selection = [];

  /**
   * The entity type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Constructs a new DeleteMultiple object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStore = $temp_store_factory->get('entity_delete_multiple_confirm');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'entity_delete_multiple_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // Get entity type ID from the route because ::buildForm has not yet been
    // called.
    $entity_type_id = $this->getRouteMatch()->getParameter('entity_type_id');
    return $entity_type_id . '_delete_multiple_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->selection), 'Are you sure you want to delete this @item?', 'Are you sure you want to delete these @items?', [
      '@item' => $this->entityType->getSingularLabel(),
      '@items' => $this->entityType->getPluralLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->entityType->hasLinkTemplate('collection')) {
      return new Url('entity.' . $this->entityTypeId . '.collection');
    }
    else {
      return new Url('<front>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->entityTypeId = $entity_type_id;
    $this->entityType = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $this->selection = $this->tempStore->get($this->currentUser->id() . ':' . $entity_type_id);
    if (empty($this->entityTypeId) || empty($this->selection)) {
      return new RedirectResponse($this->getCancelUrl()
        ->setAbsolute()
        ->toString());
    }

    $items = [];
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple(array_keys($this->selection));
    foreach ($this->selection as $id => $selected_langcodes) {
      $entity = $entities[$id];
      foreach ($selected_langcodes as $langcode) {
        $key = $id . ':' . $langcode;
        if ($entity instanceof TranslatableInterface) {
          $entity = $entity->getTranslation($langcode);
          $default_key = $id . ':' . $entity->getUntranslated()->language()->getId();

          // Build a nested list of translations that will be deleted if the
          // entity has multiple translations.
          $entity_languages = $entity->getTranslationLanguages();
          if (count($entity_languages) > 1 && $entity->isDefaultTranslation()) {
            $names = [];
            foreach ($entity_languages as $translation_langcode => $language) {
              $names[] = $language->getName();
              unset($items[$id . ':' . $translation_langcode]);
            }
            $items[$default_key] = [
              'label' => [
                '#markup' => $this->t('@label (Original translation) - <em>The following @entity_type translations will be deleted:</em>',
                  [
                    '@label' => $entity->label(),
                    '@entity_type' => $this->entityType->getSingularLabel(),
                  ]),
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
        elseif (!isset($items[$key])) {
          $items[$key] = $entity->label();
        }
      }
    }

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $total_count = 0;
    $delete_entities = [];
    $delete_translations = [];
    $inaccessible_entities = [];
    $storage = $this->entityTypeManager->getStorage($this->entityTypeId);

    $entities = $storage->loadMultiple(array_keys($this->selection));
    foreach ($this->selection as $id => $selected_langcodes) {
      $entity = $entities[$id];
      if (!$entity->access('delete', $this->currentUser)) {
        $inaccessible_entities[] = $entity;
        continue;
      }
      foreach ($selected_langcodes as $langcode) {
        if ($entity instanceof TranslatableInterface) {
          $entity = $entity->getTranslation($langcode);
          // If the entity is the default translation then deleting it will
          // delete all the translations.
          if ($entity->isDefaultTranslation()) {
            $delete_entities[$id] = $entity;
            // If there are translations already marked for deletion then remove
            // them as they will be deleted anyway.
            unset($delete_translations[$id]);
            // Update the total count. Since a single delete will delete all
            // translations, we need to add the number of translations to the
            // count.
            $total_count += count($entity->getTranslationLanguages());
          }
          // Add the translation to the list of translations to be deleted
          // unless the default translation is being deleted.
          elseif (!isset($delete_entities[$id])) {
            $delete_translations[$id][] = $entity;
          }
        }
        elseif (!isset($delete_entities[$id])) {
          $delete_entities[$id] = $entity;
          $total_count++;
        }
      }
    }

    if ($delete_entities) {
      $storage->delete($delete_entities);
      foreach ($delete_entities as $entity) {
        $this->logger($entity->getEntityType()->getProvider())->info('The @entity-type %label has been deleted.', [
          '@entity-type' => $entity->getEntityType()->getSingularLabel(),
          '%label' => $entity->label(),
        ]);
      }
    }

    if ($delete_translations) {
      /** @var \Drupal\Core\Entity\TranslatableInterface[][] $delete_translations */
      foreach ($delete_translations as $id => $translations) {
        $entity = $entities[$id]->getUntranslated();
        foreach ($translations as $translation) {
          $entity->removeTranslation($translation->language()->getId());
        }
        $entity->save();
        foreach ($translations as $translation) {
          $this->logger($entity->getEntityType()->getProvider())->info('The @entity-type %label @language translation has been deleted.', [
            '@entity-type' => $entity->getEntityType()->getSingularLabel(),
            '%label'       => $entity->label(),
            '@language'    => $translation->language()->getName(),
          ]);
        }
        $total_count += count($translations);
      }
    }

    if ($total_count) {
      $this->messenger->addStatus($this->getDeletedMessage($total_count));
    }
    if ($inaccessible_entities) {
      $this->messenger->addWarning($this->getInaccessibleMessage(count($inaccessible_entities)));
    }
    $this->tempStore->delete($this->currentUser->id());
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Returns the message to show the user after an item was deleted.
   *
   * @param int $count
   *   Count of deleted translations.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The item deleted message.
   */
  protected function getDeletedMessage($count) {
    return $this->formatPlural($count, 'Deleted @count item.', 'Deleted @count items.');
  }

  /**
   * Returns the message to show the user when an item has not been deleted.
   *
   * @param int $count
   *   Count of deleted translations.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The item inaccessible message.
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count item has not been deleted because you do not have the necessary permissions.", "@count items have not been deleted because you do not have the necessary permissions.");
  }

}
