<?php

namespace Drupal\menu_link_content\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Defines the menu link content entity class.
 *
 * @property \Drupal\link\LinkItemInterface link
 * @property \Drupal\Core\Field\FieldItemList rediscover
 *
 * @ContentEntityType(
 *   id = "menu_link_content",
 *   label = @Translation("Custom menu link"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "storage_schema" = "Drupal\menu_link_content\MenuLinkContentStorageSchema",
 *     "access" = "Drupal\menu_link_content\MenuLinkContentAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\menu_link_content\Form\MenuLinkContentForm",
 *       "delete" = "Drupal\menu_link_content\Form\MenuLinkContentDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer menu",
 *   base_table = "menu_link_content",
 *   data_table = "menu_link_content_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "bundle" = "bundle"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/menu/item/{menu_link_content}/edit",
 *     "edit-form" = "/admin/structure/menu/item/{menu_link_content}/edit",
 *     "delete-form" = "/admin/structure/menu/item/{menu_link_content}/delete",
 *   }
 * )
 */
class MenuLinkContent extends ContentEntityBase implements MenuLinkContentInterface {

  use EntityChangedTrait;

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
    return $this->get('parent')->value;
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
    $definition = array();
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
    $definition['metadata'] = array('entity_id' => $this->id());
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

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    // The menu link can just be updated if there is already an menu link entry
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
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID for this menu link content entity.'));

    $fields['uuid']->setDescription(t('The content menu link UUID.'));

    $fields['langcode']->setDescription(t('The menu link language code.'));

    $fields['bundle']
      ->setDescription(t('The content menu link bundle.'))
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH)
      ->setSetting('is_ascii', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Menu link title'))
      ->setDescription(t('The text to be used for this link in the menu.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Shown when hovering over the menu link.'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 0,
      ));

    $fields['menu_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Menu name'))
      ->setDescription(t('The menu name. All links with the same menu name (such as "tools") are part of the same menu.'))
      ->setDefaultValue('tools')
      ->setSetting('is_ascii', TRUE);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setDescription(t('The location this menu link points to.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'link_default',
        'weight' => -2,
      ));

    $fields['external'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('External'))
      ->setDescription(t('A flag to indicate if the link points to a full URL starting with a protocol, like http:// (1 = external, 0 = internal).'))
      ->setDefaultValue(FALSE);

    $fields['rediscover'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Indicates whether the menu link should be rediscovered'))
      ->setDefaultValue(FALSE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'integer',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 20,
      ));

    $fields['expanded'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Show as expanded'))
      ->setDescription(t('If selected and this menu link has children, the menu will always appear expanded.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'boolean',
        'weight' => 0,
      ))
    ->setDisplayOptions('form', array(
        'settings' => array('display_label' => TRUE),
        'weight' => 0,
      ));

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('A flag for whether the link should be enabled in menus or hidden.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'boolean',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'settings' => array('display_label' => TRUE),
        'weight' => -1,
      ));

    $fields['parent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent plugin ID'))
      ->setDescription(t('The ID of the parent menu link plugin, or empty string when at the top level of the hierarchy.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the menu link was last edited.'))
      ->setTranslatable(TRUE);

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
