<?php

namespace Drupal\shortcut\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\shortcut\Form\SetCustomize;
use Drupal\shortcut\Form\ShortcutSetDeleteForm;
use Drupal\shortcut\ShortcutSetAccessControlHandler;
use Drupal\shortcut\ShortcutSetForm;
use Drupal\shortcut\ShortcutSetInterface;
use Drupal\shortcut\ShortcutSetListBuilder;
use Drupal\shortcut\ShortcutSetStorage;

/**
 * Defines the Shortcut set configuration entity.
 */
#[ConfigEntityType(
  id: 'shortcut_set',
  label: new TranslatableMarkup('Shortcut set'),
  label_collection: new TranslatableMarkup('Shortcut sets'),
  label_singular: new TranslatableMarkup('shortcut set'),
  label_plural: new TranslatableMarkup('shortcut sets'),
  config_prefix: 'set',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ], handlers: [
    'storage' => ShortcutSetStorage::class,
    'access' => ShortcutSetAccessControlHandler::class,
    'list_builder' => ShortcutSetListBuilder::class,
    'form' => [
      'default' => ShortcutSetForm::class,
      'add' => ShortcutSetForm::class,
      'edit' => ShortcutSetForm::class,
      'customize' => SetCustomize::class,
      'delete' => ShortcutSetDeleteForm::class,
    ],
  ],
  links: [
    'customize-form' => '/admin/config/user-interface/shortcut/manage/{shortcut_set}/customize',
    'delete-form' => '/admin/config/user-interface/shortcut/manage/{shortcut_set}/delete',
    'edit-form' => '/admin/config/user-interface/shortcut/manage/{shortcut_set}',
    'collection' => '/admin/config/user-interface/shortcut',
  ],
  bundle_of: 'shortcut',
  label_count: [
    'singular' => '@count shortcut set',
    'plural' => '@count shortcut sets',
  ],
  config_export: [
    'id',
    'label',
  ],
)]
class ShortcutSet extends ConfigEntityBundleBase implements ShortcutSetInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update && !$this->isSyncing()) {
      // Save a new shortcut set with links copied from the user's default set.
      $default_set = $storage->getDefaultSet(\Drupal::currentUser());
      // This is the default set, do not copy shortcuts.
      if ($default_set->id() != $this->id()) {
        foreach ($default_set->getShortcuts() as $shortcut) {
          $shortcut = $shortcut->createDuplicate();
          $shortcut->enforceIsNew();
          $shortcut->shortcut_set->target_id = $this->id();
          $shortcut->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      $storage->deleteAssignedShortcutSets($entity);

      // Next, delete the shortcuts for this set.
      $shortcut_ids = \Drupal::entityQuery('shortcut')
        ->accessCheck(FALSE)
        ->condition('shortcut_set', $entity->id(), '=')
        ->execute();

      $controller = \Drupal::entityTypeManager()->getStorage('shortcut');
      $entities = $controller->loadMultiple($shortcut_ids);
      $controller->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetLinkWeights() {
    $weight = -50;
    foreach ($this->getShortcuts() as $shortcut) {
      $shortcut->setWeight(++$weight);
      $shortcut->save();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortcuts() {
    $shortcuts = \Drupal::entityTypeManager()->getStorage('shortcut')->loadByProperties(['shortcut_set' => $this->id()]);
    uasort($shortcuts, ['\Drupal\shortcut\Entity\Shortcut', 'sort']);
    return $shortcuts;
  }

}
