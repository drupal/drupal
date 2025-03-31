<?php

namespace Drupal\shortcut\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;
use Drupal\shortcut\Form\ShortcutDeleteForm;
use Drupal\shortcut\ShortcutAccessControlHandler;
use Drupal\shortcut\ShortcutForm;
use Drupal\shortcut\ShortcutInterface;

/**
 * Defines the shortcut entity class.
 *
 * @property \Drupal\link\LinkItemInterface $link
 */
#[ContentEntityType(
  id: 'shortcut',
  label: new TranslatableMarkup('Shortcut link'),
  label_collection: new TranslatableMarkup('Shortcut links'),
  label_singular: new TranslatableMarkup('shortcut link'),
  label_plural: new TranslatableMarkup('shortcut links'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'shortcut_set',
    'label' => 'title',
    'langcode' => 'langcode',
  ],
  handlers: [
    'access' => ShortcutAccessControlHandler::class,
    'form' => [
      'default' => ShortcutForm::class,
      'add' => ShortcutForm::class,
      'edit' => ShortcutForm::class,
      'delete' => ShortcutDeleteForm::class,
    ],
  ],
  links: [
    'canonical' => '/admin/config/user-interface/shortcut/link/{shortcut}',
    'delete-form' => '/admin/config/user-interface/shortcut/link/{shortcut}/delete',
    'edit-form' => '/admin/config/user-interface/shortcut/link/{shortcut}',
  ],
  bundle_entity_type: 'shortcut_set',
  bundle_label: new TranslatableMarkup('Shortcut set'),
  base_table: 'shortcut',
  data_table: 'shortcut_field_data',
  translatable: TRUE,
  label_count: [
    'singular' => '@count shortcut link',
    'plural' => '@count shortcut links',
  ],
  list_cache_tags: [
    'config:shortcut_set_list',
  ],
)]
class Shortcut extends ContentEntityBase implements ShortcutInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($link_title) {
    $this->set('title', $link_title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->link->first()->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // EntityBase::postSave() calls EntityBase::invalidateTagsOnSave(), which
    // only handles the regular cases. The Shortcut entity has one special case:
    // a newly created shortcut is *also* added to a shortcut set, so we must
    // invalidate the associated shortcut set's cache tag.
    if (!$update) {
      Cache::invalidateTags($this->getCacheTagsToInvalidate());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id']->setDescription(t('The ID of the shortcut.'));

    $fields['uuid']->setDescription(t('The UUID of the shortcut.'));

    $fields['shortcut_set']->setLabel(t('Shortcut set'))
      ->setDescription(t('The bundle of the shortcut.'));

    $fields['langcode']->setDescription(t('The language code of the shortcut.'));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the shortcut.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
        'settings' => [
          'size' => 40,
        ],
      ]);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Weight among shortcuts in the same shortcut set.'));

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Path'))
      ->setDescription(t('The location this shortcut points to.'))
      ->setRequired(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_INTERNAL,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return $this->shortcut_set->entity->getCacheTags();
  }

  /**
   * Sort shortcut objects.
   *
   * Callback for uasort().
   *
   * @param \Drupal\shortcut\ShortcutInterface $a
   *   First item for comparison.
   * @param \Drupal\shortcut\ShortcutInterface $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public static function sort(ShortcutInterface $a, ShortcutInterface $b) {
    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();
    if ($a_weight == $b_weight) {
      return strnatcasecmp($a->getTitle(), $b->getTitle());
    }
    return $a_weight <=> $b_weight;
  }

}
