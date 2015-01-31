<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Entity\MenuLinkContent.
 */

namespace Drupal\menu_link_content\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Defines the menu link content entity class.
 *
 * @property \Drupal\link\LinkItemInterface link
 *
 * @ContentEntityType(
 *   id = "menu_link_content",
 *   label = @Translation("Custom menu link"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
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
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * Builds up the menu link plugin definition for this entity.
   *
   * @return array
   *   The plugin definition corresponding to this entity.
   *
   * @see \Drupal\Core\Menu\MenuLinkTree::$defaults
   */
  protected function getPluginDefinition() {
    $definition = array();
    $definition['class'] = 'Drupal\menu_link_content\Plugin\Menu\MenuLinkContent';
    $definition['menu_name'] = $this->getMenuName();

    if ($url_object = $this->getUrlObject()) {
      if ($url_object->isExternal()) {
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
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    // The menu link can just be updated if there is already an menu link entry
    // on both entity and menu link plugin level.
    if ($update && $menu_link_manager->getDefinition($this->getPluginId())) {
      // When the entity is saved via a plugin instance, we should not call
      // the menu tree manager to update the definition a second time.
      if (!$this->insidePlugin) {
        $menu_link_manager->updateDefinition($this->getPluginId(), $this->getPluginDefinition(), FALSE);
      }
    }
    else {
      $menu_link_manager->addDefinition($this->getPluginId(), $this->getPluginDefinition());
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
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity ID for this menu link content entity.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The content menu link UUID.'))
      ->setReadOnly(TRUE);

    $fields['bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The content menu link bundle.'))
      ->setSetting('max_length', EntityTypeInterface::BUNDLE_MAX_LENGTH)
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Menu link title'))
      ->setDescription(t('The text to be used for this link in the menu.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'max_length' => 255,
      ))
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
      ->setSettings(array(
        'max_length' => 255,
      ))
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
      ->setSetting('default_value', 'tools');

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setDescription(t('The location this menu link points to.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 560,
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
      ->setSetting('default_value', FALSE);

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

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The menu link language code.'))
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['parent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent plugin ID'))
      ->setDescription(t('The ID of the parent menu link plugin, or empty string when at the top level of the hierarchy.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the menu link was last edited.'));

    return $fields;
  }

}
