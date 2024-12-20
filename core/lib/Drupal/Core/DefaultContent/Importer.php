<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\file\FileInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\user\EntityOwnerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * A service for handling import of content.
 *
 * @internal
 *   This API is experimental.
 */
final class Importer implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * The dependencies of the currently importing entity, if any.
   *
   * The keys are the UUIDs of the dependencies, and the values are arrays with
   * two members: the entity type ID of the dependency, and the UUID to load.
   *
   * @var array<string, string[]>|null
   */
  private ?array $dependencies = NULL;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AdminAccountSwitcher $accountSwitcher,
    private readonly FileSystemInterface $fileSystem,
    private readonly LanguageManagerInterface $languageManager,
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * Imports content entities from disk.
   *
   * @param \Drupal\Core\DefaultContent\Finder $content
   *   The content finder, which has information on the entities to create
   *   in the necessary dependency order.
   * @param \Drupal\Core\DefaultContent\Existing $existing
   *   (optional) What to do if one of the entities being imported already
   *   exists, by UUID:
   *   - \Drupal\Core\DefaultContent\Existing::Error: Throw an exception.
   *   - \Drupal\Core\DefaultContent\Existing::Skip: Leave the existing entity
   *     as-is.
   *
   * @throws \Drupal\Core\DefaultContent\ImportException
   *   - If any of the entities being imported are not content entities.
   *   - If any of the entities being imported already exists, by UUID, and
   *     $existing is \Drupal\Core\DefaultContent\Existing::Error.
   */
  public function importContent(Finder $content, Existing $existing = Existing::Error): void {
    if (count($content->data) === 0) {
      return;
    }

    $event = new PreImportEvent($content, $existing);
    $skip = $this->eventDispatcher->dispatch($event)->getSkipList();

    $account = $this->accountSwitcher->switchToAdministrator();

    try {
      /** @var array{_meta: array<mixed>} $decoded */
      foreach ($content->data as $decoded) {
        ['uuid' => $uuid, 'entity_type' => $entity_type_id, 'path' => $path] = $decoded['_meta'];
        assert(is_string($uuid));
        assert(is_string($entity_type_id));
        assert(is_string($path));

        // The event subscribers asked to skip importing this entity. If they
        // explained why, log that.
        if (array_key_exists($uuid, $skip)) {
          if ($skip[$uuid]) {
            $this->logger?->info('Skipped importing @entity_type @uuid because: %reason', [
              '@entity_type' => $entity_type_id,
              '@uuid' => $uuid,
              '%reason' => $skip[$uuid],
            ]);
          }
          continue;
        }

        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
        if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
          throw new ImportException("Content entity $uuid is a '$entity_type_id', which is not a content entity type.");
        }

        $entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
        if ($entity) {
          if ($existing === Existing::Skip) {
            continue;
          }
          else {
            throw new ImportException("$entity_type_id $uuid already exists.");
          }
        }

        $entity = $this->toEntity($decoded)->enforceIsNew();

        // Ensure that the entity is not owned by the anonymous user.
        if ($entity instanceof EntityOwnerInterface && empty($entity->getOwnerId())) {
          $entity->setOwnerId($account->id());
        }

        // If a file exists in the same folder, copy it to the designated
        // target URI.
        if ($entity instanceof FileInterface) {
          $this->copyFileAssociatedWithEntity(dirname($path), $entity);
        }
        $violations = $entity->validate();
        if (count($violations) > 0) {
          throw new InvalidEntityException($violations, $path);
        }
        $entity->save();
      }
    }
    finally {
      $this->accountSwitcher->switchBack();
    }
  }

  /**
   * Copies a file from default content directory to the site's file system.
   *
   * @param string $path
   *   The path to the file to copy.
   * @param \Drupal\file\FileInterface $entity
   *   The file entity.
   */
  private function copyFileAssociatedWithEntity(string $path, FileInterface &$entity): void {
    $destination = $entity->getFileUri();
    assert(is_string($destination));

    // If the source file doesn't exist, there's nothing we can do.
    $source = $path . '/' . basename($destination);
    if (!file_exists($source)) {
      $this->logger?->warning("File entity %name was imported, but the associated file (@path) was not found.", [
        '%name' => $entity->label(),
        '@path' => $source,
      ]);
      return;
    }

    $copy_file = TRUE;
    if (file_exists($destination)) {
      $source_hash = hash_file('sha256', $source);
      assert(is_string($source_hash));
      $destination_hash = hash_file('sha256', $destination);
      assert(is_string($destination_hash));

      if (hash_equals($source_hash, $destination_hash) && $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $destination]) === []) {
        // If the file hashes match and the file is not already a managed file
        // then do not copy a new version to the file system. This prevents
        // re-installs during development from creating unnecessary duplicates.
        $copy_file = FALSE;
      }
    }

    $target_directory = dirname($destination);
    $this->fileSystem->prepareDirectory($target_directory, FileSystemInterface::CREATE_DIRECTORY);
    if ($copy_file) {
      $uri = $this->fileSystem->copy($source, $destination);
      $entity->setFileUri($uri);
    }
  }

  /**
   * Converts an array of content entity data to a content entity object.
   *
   * @param array<string, array<mixed>> $data
   *   The entity data.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The unsaved entity.
   *
   * @throws \Drupal\Core\DefaultContent\ImportException
   *   If the `entity_type` or `uuid` meta keys are not set.
   */
  private function toEntity(array $data): ContentEntityInterface {
    if (empty($data['_meta']['entity_type'])) {
      throw new ImportException('The entity type metadata must be specified.');
    }
    if (empty($data['_meta']['uuid'])) {
      throw new ImportException('The uuid metadata must be specified.');
    }

    $is_root = FALSE;
    // @see ::loadEntityDependency()
    if ($this->dependencies === NULL && !empty($data['_meta']['depends'])) {
      $is_root = TRUE;
      foreach ($data['_meta']['depends'] as $uuid => $entity_type) {
        assert(is_string($uuid));
        assert(is_string($entity_type));
        $this->dependencies[$uuid] = [$entity_type, $uuid];
      }
    }

    ['entity_type' => $entity_type] = $data['_meta'];
    assert(is_string($entity_type));
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition($entity_type);

    $values = [
      'uuid' => $data['_meta']['uuid'],
    ];
    if (!empty($data['_meta']['bundle'])) {
      $values[$entity_type->getKey('bundle')] = $data['_meta']['bundle'];
    }

    if (!empty($data['_meta']['default_langcode'])) {
      $data = $this->verifyNormalizedLanguage($data);
      $values[$entity_type->getKey('langcode')] = $data['_meta']['default_langcode'];
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type->id())->create($values);
    foreach ($data['default'] as $field_name => $values) {
      $this->setFieldValues($entity, $field_name, $values);
    }

    foreach ($data['translations'] ?? [] as $langcode => $translation_data) {
      if ($this->languageManager->getLanguage($langcode)) {
        $translation = $entity->addTranslation($langcode, $entity->toArray());
        foreach ($translation_data as $field_name => $values) {
          $this->setFieldValues($translation, $field_name, $values);
        }
      }
    }

    if ($is_root) {
      $this->dependencies = NULL;
    }
    return $entity;
  }

  /**
   * Sets field values based on the normalized data.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param string $field_name
   *   The name of the field.
   * @param array $values
   *   The normalized data for the field.
   */
  private function setFieldValues(ContentEntityInterface $entity, string $field_name, array $values): void {
    foreach ($values as $delta => $item_value) {
      if (!$entity->get($field_name)->get($delta)) {
        $entity->get($field_name)->appendItem();
      }
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $item = $entity->get($field_name)->get($delta);

      // Update the URI based on the target UUID for link fields.
      if (isset($item_value['target_uuid']) && $item instanceof LinkItem) {
        $target_entity = $this->loadEntityDependency($item_value['target_uuid']);
        if ($target_entity) {
          $item_value['uri'] = 'entity:' . $target_entity->getEntityTypeId() . '/' . $target_entity->id();
        }
        unset($item_value['target_uuid']);
      }

      $serialized_property_names = $this->getCustomSerializedPropertyNames($item);
      foreach ($item_value as $property_name => $value) {
        if (\in_array($property_name, $serialized_property_names)) {
          if (\is_string($value)) {
            throw new ImportException("Received string for serialized property $field_name.$delta.$property_name");
          }
          $value = serialize($value);
        }

        $property = $item->get($property_name);

        if ($property instanceof EntityReference) {
          if (is_array($value)) {
            $value = $this->toEntity($value);
          }
          else {
            $value = $this->loadEntityDependency($value);
          }
        }
        $property->setValue($value);
      }
    }
  }

  /**
   * Gets the names of all properties the plugin treats as serialized data.
   *
   * This allows the field storage definition or entity type to provide a
   * setting for serialized properties. This can be used for fields that
   * handle serialized data themselves and do not rely on the serialized schema
   * flag.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   *
   * @return string[]
   *   The property names for serialized properties.
   *
   * @see \Drupal\serialization\Normalizer\SerializedColumnNormalizerTrait::getCustomSerializedPropertyNames
   */
  private function getCustomSerializedPropertyNames(FieldItemInterface $field_item): array {
    if ($field_item instanceof PluginInspectionInterface) {
      $definition = $field_item->getPluginDefinition();
      $serialized_fields = $field_item->getEntity()->getEntityType()->get('serialized_field_property_names');
      $field_name = $field_item->getFieldDefinition()->getName();
      if (is_array($serialized_fields) && isset($serialized_fields[$field_name]) && is_array($serialized_fields[$field_name])) {
        return $serialized_fields[$field_name];
      }
      if (isset($definition['serialized_property_names']) && is_array($definition['serialized_property_names'])) {
        return $definition['serialized_property_names'];
      }
    }
    return [];
  }

  /**
   * Loads the entity dependency by its UUID.
   *
   * @param string $target_uuid
   *   The entity UUID.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The loaded entity.
   */
  private function loadEntityDependency(string $target_uuid): ?ContentEntityInterface {
    if ($this->dependencies && array_key_exists($target_uuid, $this->dependencies)) {
      $entity = $this->entityRepository->loadEntityByUuid(...$this->dependencies[$target_uuid]);
      assert($entity instanceof ContentEntityInterface || $entity === NULL);
      return $entity;
    }
    return NULL;
  }

  /**
   * Verifies that the site knows the default language of the normalized entity.
   *
   * Will attempt to switch to an alternative translation or just import it
   * with the site default language.
   *
   * @param array $data
   *   The normalized entity data.
   *
   * @return array
   *   The normalized entity data, possibly with altered default language
   *   and translations.
   */
  private function verifyNormalizedLanguage(array $data): array {
    $default_langcode = $data['_meta']['default_langcode'];
    $default_language = $this->languageManager->getDefaultLanguage();
    // Check the language. If the default language isn't known, import as one
    // of the available translations if one exists with those values. If none
    // exists, create the entity in the default language.
    // During the installer, when installing with an alternative language,
    // `en` is still the default when modules are installed so check the default language
    // instead.
    if (!$this->languageManager->getLanguage($default_langcode) || (InstallerKernel::installationAttempted() && $default_language->getId() !== $default_langcode)) {
      $use_default = TRUE;
      foreach ($data['translations'] ?? [] as $langcode => $translation_data) {
        if ($this->languageManager->getLanguage($langcode)) {
          $data['_meta']['default_langcode'] = $langcode;
          $data['default'] = \array_merge($data['default'], $translation_data);
          unset($data['translations'][$langcode]);
          $use_default = FALSE;
          break;
        }
      }

      if ($use_default) {
        $data['_meta']['default_langcode'] = $default_language->getId();
      }
    }
    return $data;
  }

}
