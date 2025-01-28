<?php

namespace Drupal\menu_link_content\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;
use Drupal\menu_link_content\Form\MenuLinkContentDeleteForm;
use Drupal\menu_link_content\Form\MenuLinkContentForm;
use Drupal\menu_link_content\MenuLinkContentAccessControlHandler;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\menu_link_content\MenuLinkContentStorage;
use Drupal\menu_link_content\MenuLinkContentStorageSchema;
use Drupal\menu_link_content\MenuLinkListBuilder;

/**
 * Defines the menu link content entity class.
 *
 * @property \Drupal\Core\Field\FieldItemList $link
 * @property \Drupal\Core\Field\FieldItemList $rediscover
 */
#[ContentEntityType(
  id: 'menu_link_content',
  label: new TranslatableMarkup('Custom menu link'),
  label_collection: new TranslatableMarkup('Custom menu links'),
  label_singular: new TranslatableMarkup('custom menu link'),
  label_plural: new TranslatableMarkup('custom menu links'),
  entity_keys: [
    'id' => 'id',
    'revision' => 'revision_id',
    'label' => 'title',
    'langcode' => 'langcode',
    'uuid' => 'uuid',
    'bundle' => 'bundle',
    'published' => 'enabled',
  ],
  handlers: [
    'storage' => MenuLinkContentStorage::class,
    'storage_schema' => MenuLinkContentStorageSchema::class,
    'access' => MenuLinkContentAccessControlHandler::class,
    'form' => [
      'default' => MenuLinkContentForm::class,
      'delete' => MenuLinkContentDeleteForm::class,
    ],
    'list_builder' => MenuLinkListBuilder::class,
  ],
  links: [
    'canonical' => '/admin/structure/menu/item/{menu_link_content}/edit',
    'edit-form' => '/admin/structure/menu/item/{menu_link_content}/edit',
    'delete-form' => '/admin/structure/menu/item/{menu_link_content}/delete',
  ],
  admin_permission: 'administer menu',
  base_table: 'menu_link_content',
  data_table: 'menu_link_content_data',
  revision_table: 'menu_link_content_revision',
  revision_data_table: 'menu_link_content_field_revision',
  translatable: TRUE,
  label_count: [
    'singular' => '@count custom menu link',
    'plural' => '@count custom menu links',
  ],
  constraints: [
    'MenuTreeHierarchy' => [],
  ],
  revision_metadata_keys: [
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ],
  )]
class MenuLinkContent extends EditorialContentEntityBase implements MenuLinkContentInterface {

  /**
   * A flag for whether this entity is wrapped in a plugin instance.
   *
   * @var bool
   */
  protected $insidePlugin = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setInsidePlugin() {
    $this->insidePlugin = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlObject() {
    return $this->link->first()->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName() {
    return $this->get('menu_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return 'menu_link_content:' . $this->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded() {
    return (bool) $this->get('expanded')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    // Cast the parent ID to a string, only an empty string means no parent,
    // NULL keeps the existing parent.
    return (string) $this->get('parent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = [];
    $definition['class'] = 'Drupal\menu_link_content\Plugin\Menu\MenuLinkContent';
    $definition['menu_name'] = $this->getMenuName();

    if ($url_object = $this->getUrlObject()) {
      $definition['url'] = NULL;
      $definition['route_name'] = NULL;
      $definition['route_parameters'] = [];
      if (!$url_object->isRouted()) {
        $definition['url'] = $url_object->getUri();
      }
      else {
        $definition['route_name'] = $url_object->getRouteName();
        $definition['route_parameters'] = $url_object->getRouteParameters();
      }
      $definition['options'] = $url_object->getOptions();
    }

    $definition['title'] = $this->getTitle();
    $definition['description'] = $this->getDescription();
    $definition['weight'] = $this->getWeight();
    $definition['id'] = $this->getPluginId();
    $definition['metadata'] = ['entity_id' => $this->id()];
    $definition['form_class'] = '\Drupal\menu_link_content\Form\MenuLinkContentForm';
    $definition['enabled'] = $this->isEnabled() ? 1 : 0;
    $definition['expanded'] = $this->isExpanded() ? 1 : 0;
    $definition['provider'] = 'menu_link_content';
    $definition['discovered'] = 0;
    $definition['parent'] = $this->getParentId();

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += ['bundle' => 'menu_link_content'];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (parse_url($this->link->uri, PHP_URL_SCHEME) === 'internal') {
      $this->setRequiresRediscovery(TRUE);
    }
    else {
      $this->setRequiresRediscovery(FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Don't update the menu tree if a pending revision was saved.
    if (!$this->isDefaultRevision()) {
      return;
    }

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    // The menu link can just be updated if there is already a menu link entry
    // on both entity and menu link plugin level.
    $definition = $this->getPluginDefinition();
    // Even when $update is FALSE, for top level links it is possible the link
    // already is in the storage because of the getPluginDefinition() call
    // above, see https://www.drupal.org/node/2605684#comment-10515450 for the
    // call chain. Because of this the $update flag is ignored and only the
    // existence of the definition (equals to being in the tree storage) is
    // checked.
    if ($menu_link_manager->getDefinition($this->getPluginId(), FALSE)) {
      // When the entity is saved via a plugin instance, we should not call
      // the menu tree manager to update the definition a second time.
      if (!$this->insidePlugin) {
        $menu_link_manager->updateDefinition($this->getPluginId(), $definition, FALSE);
      }
    }
    else {
      $menu_link_manager->addDefinition($this->getPluginId(), $definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    foreach ($entities as $menu_link) {
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
      $menu_link_manager->removeDefinition($menu_link->getPluginId(), FALSE);

      // Children get re-attached to the menu link's parent.
      $parent_plugin_id = $menu_link->getParentId();
      $children = $storage->loadByProperties(['parent' => $menu_link->getPluginId()]);
      foreach ($children as $child) {
        /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $child */
        $child->set('parent', $parent_plugin_id)->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the publishing status field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['id']->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID for this menu link content entity.'));

    $fields['uuid']->setDescription(t('The content menu link UUID.'));

    $fields['langcode']->setDescription(t('The menu link language code.'));

    $fields['bundle']
      ->setDescription(t('The content menu link bundle.'))
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH)
      ->setSetting('is_ascii', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Menu link title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Shown when hovering over the menu link.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['menu_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Menu name'))
      ->setDescription(t('The menu name. All links with the same menu name (such as "tools") are part of the same menu.'))
      ->setDefaultValue('tools')
      ->setSetting('is_ascii', TRUE);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -2,
      ]);

    $fields['external'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('External'))
      ->setDescription(t('A flag to indicate if the link points to a full URL starting with a protocol, like http:// (1 = external, 0 = internal).'))
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE);

    $fields['rediscover'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Indicates whether the menu link should be rediscovered'))
      ->setDefaultValue(FALSE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 20,
      ]);

    $fields['expanded'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show as expanded'))
      ->setDescription(t('If selected and this menu link has children, the menu will always appear expanded. This option may be overridden for the entire menu tree when placing a menu block.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'settings' => ['display_label' => TRUE],
        'weight' => 0,
      ]);

    // Override some properties of the published field added by
    // \Drupal\Core\Entity\EntityPublishedTrait::publishedBaseFieldDefinitions().
    $fields['enabled']->setLabel(t('Enabled'));
    $fields['enabled']->setDescription(t('A flag for whether the link should be enabled in menus or hidden.'));
    $fields['enabled']->setTranslatable(FALSE);
    $fields['enabled']->setDisplayOptions('view', [
      'label' => 'hidden',
      'type' => 'boolean',
      'weight' => 0,
    ]);
    $fields['enabled']->setDisplayOptions('form', [
      'settings' => ['display_label' => TRUE],
      'weight' => -1,
    ]);

    $fields['parent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent plugin ID'))
      ->setDescription(t('The ID of the parent menu link plugin, or empty string when at the top level of the hierarchy.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the menu link was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    // @todo Keep this field hidden until we have a revision UI for menu links.
    //   @see https://www.drupal.org/project/drupal/issues/2350939
    $fields['revision_log_message']->setDisplayOptions('form', [
      'region' => 'hidden',
    ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresRediscovery() {
    return $this->get('rediscover')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequiresRediscovery($rediscovery) {
    $this->set('rediscover', $rediscovery);
    return $this;
  }

}
