<?php

namespace Drupal\demo_umami_content;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cSpell:ignore María García Gregorio Sánchez

/**
 * Defines a helper class for importing default content.
 *
 * @internal
 *   This code is only for use by the Umami demo: Content module.
 */
class InstallHelper implements ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Enabled languages.
   *
   * List of all enabled languages.
   *
   * @var array
   */
  protected $enabledLanguages;

  /**
   * Term ID map.
   *
   * Used to store term IDs created in the import process against
   * vocabulary and row in the source CSV files. This allows the created terms
   * to be cross referenced when creating articles and recipes.
   *
   * @var array
   */
  protected $termIdMap;

  /**
   * Media Image CSV ID map.
   *
   * Used to store media image CSV IDs created in the import process.
   * This allows the created media images to be cross referenced when creating
   * article, recipes and blocks.
   *
   * @var array
   */
  protected $mediaImageIdMap;

  /**
   * Node CSV ID map.
   *
   * Used to store node CSV IDs created in the import process. This allows the
   * created nodes to be cross referenced when creating blocks.
   *
   * @var array
   */
  protected $nodeIdMap;

  /**
   * The module's path.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  protected string $module_path;

  /**
   * Constructs a new InstallHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler, StateInterface $state, FileSystemInterface $fileSystem) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->state = $state;
    $this->fileSystem = $fileSystem;
    $this->termIdMap = [];
    $this->mediaImageIdMap = [];
    $this->nodeIdMap = [];
    $this->enabledLanguages = array_keys(\Drupal::languageManager()->getLanguages());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('state'),
      $container->get('file_system')
    );
  }

  /**
   * Imports default contents.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importContent() {
    $this->getModulePath()
      ->importUsers()
      ->importContentFromFile('taxonomy_term', 'tags')
      ->importContentFromFile('taxonomy_term', 'recipe_category')
      ->importContentFromFile('media', 'image')
      ->importContentFromFile('node', 'recipe')
      ->importContentFromFile('node', 'article')
      ->importContentFromFile('node', 'page')
      ->importContentFromFile('block_content', 'disclaimer_block')
      ->importContentFromFile('block_content', 'footer_promo_block')
      ->importContentFromFile('block_content', 'banner_block');
  }

  /**
   * Set module_path variable.
   *
   * @return $this
   */
  protected function getModulePath() {
    $this->module_path = $this->moduleHandler->getModule('demo_umami_content')->getPath();
    return $this;
  }

  /**
   * Read multilingual content.
   *
   * @param string $filename
   *   Filename to import.
   *
   * @return array
   *   An array of two items:
   *     1. All multilingual content that was read from the files.
   *     2. List of language codes that need to be imported.
   */
  protected function readMultilingualContent($filename) {
    $default_content_path = $this->module_path . "/default_content/languages/";

    // Get all enabled languages.
    $translated_languages = $this->enabledLanguages;

    // Load all the content from any CSV files that exist for enabled languages.
    foreach ($translated_languages as $language) {
      if (file_exists($default_content_path . "$language/$filename") &&
      ($handle = fopen($default_content_path . "$language/$filename", 'r')) !== FALSE) {
        $header = fgetcsv($handle, escape: '');
        $line_counter = 0;
        while (($content = fgetcsv($handle, escape: '')) !== FALSE) {
          $keyed_content[$language][$line_counter] = array_combine($header, $content);
          $line_counter++;
        }
        fclose($handle);
      }
      else {
        // Language directory exists, but the file in this language was not found,
        // remove that language from list of languages to be translated.
        $key = array_search($language, $translated_languages);
        unset($translated_languages[$key]);
      }
    }
    return [$keyed_content, $translated_languages];
  }

  /**
   * Retrieves the Term ID of a term saved during the import process.
   *
   * @param string $vocabulary
   *   Machine name of vocabulary to which it was saved.
   * @param int $term_csv_id
   *   The term's ID from the CSV file.
   *
   * @return int
   *   Term ID, or 0 if Term ID could not be found.
   */
  protected function getTermId($vocabulary, $term_csv_id) {
    if (array_key_exists($vocabulary, $this->termIdMap) && array_key_exists($term_csv_id, $this->termIdMap[$vocabulary])) {
      return $this->termIdMap[$vocabulary][$term_csv_id];
    }
    return 0;
  }

  /**
   * Saves a Term ID generated when saving a taxonomy term.
   *
   * @param string $vocabulary
   *   Machine name of vocabulary to which it was saved.
   * @param int $term_csv_id
   *   The term's ID from the CSV file.
   * @param int $tid
   *   Term ID generated when saved in the Drupal database.
   */
  protected function saveTermId($vocabulary, $term_csv_id, $tid) {
    $this->termIdMap[$vocabulary][$term_csv_id] = $tid;
  }

  /**
   * Retrieves the Media Image ID of a media image saved during the import process.
   *
   * @param int $media_image_csv_id
   *   The media image's ID from the CSV file.
   *
   * @return int
   *   Media Image ID, or 0 if Media Image ID could not be found.
   */
  protected function getMediaImageId($media_image_csv_id) {
    if (array_key_exists($media_image_csv_id, $this->mediaImageIdMap)) {
      return $this->mediaImageIdMap[$media_image_csv_id];
    }
    return 0;
  }

  /**
   * Saves a Media Image ID generated when saving a media image.
   *
   * @param int $media_image_csv_id
   *   The media image's ID from the CSV file.
   * @param int $media_image_id
   *   Media Image ID generated when saved in the Drupal database.
   */
  protected function saveMediaImageId($media_image_csv_id, $media_image_id) {
    $this->mediaImageIdMap[$media_image_csv_id] = $media_image_id;
  }

  /**
   * Retrieves the node path of node CSV ID saved during the import process.
   *
   * @param string $langcode
   *   Current language code.
   * @param string $content_type
   *   Current content type.
   * @param string $node_csv_id
   *   The node's ID from the CSV file.
   *
   * @return string
   *   Node path, or 0 if node CSV ID could not be found.
   */
  protected function getNodePath($langcode, $content_type, $node_csv_id) {
    if (array_key_exists($langcode, $this->nodeIdMap) &&
        array_key_exists($content_type, $this->nodeIdMap[$langcode]) &&
        array_key_exists($node_csv_id, $this->nodeIdMap[$langcode][$content_type])) {
      return $this->nodeIdMap[$langcode][$content_type][$node_csv_id];
    }
    return 0;
  }

  /**
   * Saves a node CSV ID generated when saving content.
   *
   * @param string $langcode
   *   Current language code.
   * @param string $content_type
   *   Current content type.
   * @param string $node_csv_id
   *   The node's ID from the CSV file.
   * @param string $node_url
   *   Node's URL alias when saved in the Drupal database.
   */
  protected function saveNodePath($langcode, $content_type, $node_csv_id, $node_url) {
    $this->nodeIdMap[$langcode][$content_type][$node_csv_id] = $node_url;
  }

  /**
   * Imports users.
   *
   * Users are created as their content is imported. However, some users might
   * have non-default values (as preferred language), or editors don't have
   * their own content so are created here instead.
   *
   * @return $this
   */
  protected function importUsers() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $users = [
      'Gregorio Sánchez' => [
        'preferred_language' => 'es',
        'roles' => ['author'],
      ],
      'Margaret Hopper' => [
        'preferred_language' => 'en',
        'roles' => ['editor'],
      ],
      'Grace Hamilton' => [
        'preferred_language' => 'en',
        'roles' => ['editor'],
      ],
      'María García' => [
        'preferred_language' => 'es',
        'roles' => ['editor'],
      ],
    ];
    foreach ($users as $name => $user_data) {
      $user = $user_storage->create([
        'name' => $name,
        'status' => 1,
        'roles' => $user_data['roles'],
        'preferred_langcode' => $user_data['preferred_language'],
        'preferred_admin_langcode' => $user_data['preferred_language'],
        'mail' => \Drupal::transliteration()->transliterate(mb_strtolower(str_replace(' ', '.', $name))) . '@example.com',
      ]);
      $user->enforceIsNew();
      $user->save();
      $this->storeCreatedContentUuids([$user->uuid() => 'user']);
    }
    return $this;
  }

  /**
   * Process terms for a given vocabulary and filename.
   *
   * @param array $data
   *   Data of line that was read from the file.
   * @param string $vocabulary
   *   Machine name of vocabulary to which we should save terms.
   *
   * @return array
   *   Data structured as a term.
   */
  protected function processTerm(array $data, $vocabulary) {
    $term_name = trim($data['term']);

    // Prepare content.
    $values = [
      'name' => $term_name,
      'vid' => $vocabulary,
      'path' => ['alias' => '/' . Html::getClass($vocabulary) . '/' . Html::getClass($term_name)],
      'langcode' => 'en',
    ];
    return $values;
  }

  /**
   * Process images into media entities.
   *
   * @param array $data
   *   Data of line that was read from the file.
   *
   * @return array
   *   Data structured as a image.
   */
  protected function processImage(array $data) {
    // Set article author.
    if (!empty($data['author'])) {
      $values['uid'] = $this->getUser($data['author']);
    }

    $image_path = $this->module_path . '/default_content/images/' . $data['image'];
    // Prepare content.
    $values = [
      'name' => $data['title'],
      'bundle' => 'image',
      'langcode' => 'en',
      'field_media_image' => [
        'target_id' => $this->createFileEntity($image_path),
        'alt' => $data['alt'],
      ],
    ];
    return $values;
  }

  /**
   * Process pages data into page node structure.
   *
   * @param array $data
   *   Data of line that was read from the file.
   * @param string $langcode
   *   Current language code.
   *
   * @return array
   *   Data structured as a page node.
   */
  protected function processPage(array $data, $langcode) {
    // Prepare content.
    $values = [
      'type' => 'page',
      'title' => $data['title'],
      'moderation_state' => 'published',
      'langcode' => 'en',
    ];
    // Fields mapping starts.
    // Set field_body field.
    if (!empty($data['field_body'])) {
      $values['field_body'] = [['value' => $data['field_body'], 'format' => 'basic_html']];
    }
    // Set node alias if exists.
    if (!empty($data['slug'])) {
      $values['path'] = [['alias' => '/' . $data['slug']]];
    }
    // Save node alias
    $this->saveNodePath($langcode, 'page', $data['id'], $data['slug']);

    // Set article author.
    if (!empty($data['author'])) {
      $values['uid'] = $this->getUser($data['author']);
    }
    return $values;
  }

  /**
   * Process recipe data into recipe node structure.
   *
   * @param array $data
   *   Data of line that was read from the file.
   * @param string $langcode
   *   Current language code.
   *
   * @return array
   *   Data structured as a recipe node.
   */
  protected function processRecipe(array $data, $langcode) {
    $values = [
      'type' => 'recipe',
      // Title field.
      'title' => $data['title'],
      'moderation_state' => 'published',
      'langcode' => 'en',
    ];
    // Set article author.
    if (!empty($data['author'])) {
      $values['uid'] = $this->getUser($data['author']);
    }
    // Set node alias if exists.
    if (!empty($data['slug'])) {
      $values['path'] = [['alias' => '/' . $data['slug']]];
    }
    // Save node alias
    $this->saveNodePath($langcode, 'recipe', $data['id'], $data['slug']);
    // Set field_media_image field.
    if (!empty($data['image_reference'])) {
      $values['field_media_image'] = [
        'target_id' => $this->getMediaImageId($data['image_reference']),
      ];
    }
    // Set field_summary field.
    if (!empty($data['summary'])) {
      $values['field_summary'] = [['value' => $data['summary'], 'format' => 'basic_html']];
    }
    // Set field_recipe_category if exists.
    if (!empty($data['recipe_category'])) {
      $values['field_recipe_category'] = [];
      $tags = array_filter(explode(',', $data['recipe_category']));
      foreach ($tags as $tag_id) {
        if ($tid = $this->getTermId('recipe_category', $tag_id)) {
          $values['field_recipe_category'][] = ['target_id' => $tid];
        }
      }
    }
    // Set field_preparation_time field.
    if (!empty($data['preparation_time'])) {
      $values['field_preparation_time'] = [['value' => $data['preparation_time']]];
    }
    // Set field_cooking_time field.
    if (!empty($data['cooking_time'])) {
      $values['field_cooking_time'] = [['value' => $data['cooking_time']]];
    }
    // Set field_difficulty field.
    if (!empty($data['difficulty'])) {
      $values['field_difficulty'] = $data['difficulty'];
    }
    // Set field_number_of_servings field.
    if (!empty($data['number_of_servings'])) {
      $values['field_number_of_servings'] = [['value' => $data['number_of_servings']]];
    }
    // Set field_ingredients field.
    if (!empty($data['ingredients'])) {
      $ingredients = explode(',', $data['ingredients']);
      $values['field_ingredients'] = [];
      foreach ($ingredients as $ingredient) {
        $values['field_ingredients'][] = ['value' => $ingredient];
      }
    }
    // Set field_recipe_instruction field.
    if (!empty($data['recipe_instruction'])) {
      $recipe_instruction_path = $this->module_path . '/default_content/languages/' . $langcode . '/recipe_instructions/' . $data['recipe_instruction'];
      $recipe_instructions = file_get_contents($recipe_instruction_path);
      if ($recipe_instructions !== FALSE) {
        $values['field_recipe_instruction'] = [['value' => $recipe_instructions, 'format' => 'basic_html']];
      }
    }
    // Set field_tags if exists.
    if (!empty($data['tags'])) {
      $values['field_tags'] = [];
      $tags = array_filter(explode(',', $data['tags']));
      foreach ($tags as $tag_id) {
        if ($tid = $this->getTermId('tags', $tag_id)) {
          $values['field_tags'][] = ['target_id' => $tid];
        }
      }
    }
    return $values;
  }

  /**
   * Process article data into article node structure.
   *
   * @param array $data
   *   Data of line that was read from the file.
   * @param string $langcode
   *   Current language code.
   *
   * @return array
   *   Data structured as an article node.
   */
  protected function processArticle(array $data, $langcode) {
    // Prepare content.
    $values = [
      'type' => 'article',
      'title' => $data['title'],
      'moderation_state' => 'published',
      'langcode' => 'en',
    ];
    // Fields mapping starts.
    // Set field_body field.
    if (!empty($data['field_body'])) {
      $body_path = $this->module_path . '/default_content/languages/' . $langcode . '/article_body/' . $data['field_body'];
      $body = file_get_contents($body_path);
      if ($body !== FALSE) {
        $values['field_body'] = [['value' => $body, 'format' => 'basic_html']];
      }
    }

    // Set node alias if exists.
    if (!empty($data['slug'])) {
      $values['path'] = [['alias' => '/' . $data['slug']]];
    }
    // Save node alias
    $this->saveNodePath($langcode, 'article', $data['id'], $data['slug']);
    // Set article author.
    if (!empty($data['author'])) {
      $values['uid'] = $this->getUser($data['author']);
    }
    // Set field_media_image field.
    if (!empty($data['image_reference'])) {
      $values['field_media_image'] = [
        'target_id' => $this->getMediaImageId($data['image_reference']),
      ];
    }
    // Set field_tags if exists.
    if (!empty($data['tags'])) {
      $values['field_tags'] = [];
      $tags = explode(',', $data['tags']);
      foreach ($tags as $tag_id) {
        if ($tid = $this->getTermId('tags', $tag_id)) {
          $values['field_tags'][] = ['target_id' => $tid];
        }
      }
    }
    return $values;
  }

  /**
   * Process block_banner data into block_banner block structure.
   *
   * @param array $data
   *   Data of line that was read from the file.
   * @param string $langcode
   *   Current language code.
   *
   * @return array
   *   Data structured as a block.
   */
  protected function processBannerBlock(array $data, $langcode) {
    $node_url = $this->getNodePath($langcode, $data['content_type'], $data['node_id']);
    $values = [
      'uuid' => $data['uuid'],
      'info' => $data['info'],
      'type' => $data['type'],
      'langcode' => 'en',
      'field_title' => [
        'value' => $data['field_title'],
      ],
      'field_content_link' => [
        'uri' => 'internal:/' . $langcode . '/' . $node_url,
        'title' => $data['field_content_link_title'],
      ],
      'field_summary' => [
        'value' => $data['field_summary'],
      ],
      'field_media_image' => [
        'target_id' => $this->getMediaImageId($data['image_reference']),
      ],
    ];
    return $values;
  }

  /**
   * Process disclaimer_block data into disclaimer_block block structure.
   *
   * @param array $data
   *   Data of line that was read from the file.
   *
   * @return array
   *   Data structured as a block.
   */
  protected function processDisclaimerBlock(array $data) {
    $values = [
      'uuid' => $data['uuid'],
      'info' => $data['info'],
      'type' => $data['type'],
      'langcode' => 'en',
      'field_disclaimer' => [
        'value' => $data['field_disclaimer'],
        'format' => 'basic_html',
      ],
      'field_copyright' => [
        'value' => '&copy; ' . date("Y") . ' ' . $data['field_copyright'],
        'format' => 'basic_html',
      ],
    ];
    return $values;
  }

  /**
   * Process footer_block data into footer_block block structure.
   *
   * @param array $data
   *   Data of line that was read from the file.
   * @param string $langcode
   *   Current language code.
   *
   * @return array
   *   Data structured as a block.
   */
  protected function processFooterPromoBlock(array $data, $langcode) {
    $node_url = $this->getNodePath($langcode, $data['content_type'], $data['node_id']);
    $values = [
      'uuid' => $data['uuid'],
      'info' => $data['info'],
      'type' => $data['type'],
      'langcode' => 'en',
      'field_title' => [
        'value' => $data['field_title'],
      ],
      'field_content_link' => [
        'uri' => 'internal:/' . $node_url,
        'title' => $data['field_content_link_title'],
      ],
      'field_summary' => [
        'value' => $data['field_summary'],
      ],
      'field_media_image' => [
        'target_id' => $this->getMediaImageId($data['image_reference']),
      ],
    ];
    return $values;
  }

  /**
   * Process content into a structure that can be saved into Drupal.
   *
   * @param string $bundle_machine_name
   *   Current bundle's machine name.
   * @param array $content
   *   Current content array that needs to be structured.
   * @param string $langcode
   *   Current language code.
   *
   * @return array
   *   Structured content.
   */
  protected function processContent($bundle_machine_name, array $content, $langcode) {
    switch ($bundle_machine_name) {
      case 'recipe':
        $structured_content = $this->processRecipe($content, $langcode);
        break;

      case 'article':
        $structured_content = $this->processArticle($content, $langcode);
        break;

      case 'page':
        $structured_content = $this->processPage($content, $langcode);
        break;

      case 'banner_block':
        $structured_content = $this->processBannerBlock($content, $langcode);
        break;

      case 'disclaimer_block':
        $structured_content = $this->processDisclaimerBlock($content);
        break;

      case 'footer_promo_block':
        $structured_content = $this->processFooterPromoBlock($content, $langcode);
        break;

      case 'image':
        $structured_content = $this->processImage($content);
        break;

      case 'recipe_category':
      case 'tags':
        $structured_content = $this->processTerm($content, $bundle_machine_name);
        break;

      default:
        break;
    }
    return $structured_content;
  }

  /**
   * Imports content.
   *
   * @param string $entity_type
   *   Entity type to be imported
   * @param string $bundle_machine_name
   *   Bundle machine name to be imported.
   *
   * @return $this
   */
  protected function importContentFromFile($entity_type, $bundle_machine_name) {
    $filename = $entity_type . '/' . $bundle_machine_name . '.csv';

    // Read all multilingual content from the file.
    [$all_content, $translated_languages] = $this->readMultilingualContent($filename);

    // English is no longer needed in the list of languages to translate.
    $key = array_search('en', $translated_languages);
    unset($translated_languages[$key]);

    // Start the loop with English (default) recipes.
    foreach ($all_content['en'] as $current_content) {
      // Process data into its relevant structure.
      $structured_content = $this->processContent($bundle_machine_name, $current_content, 'en');

      // Save Entity.
      $entity = $this->entityTypeManager->getStorage($entity_type)->create($structured_content);
      $entity->save();
      $this->storeCreatedContentUuids([$entity->uuid() => $entity_type]);

      // Save taxonomy entity Drupal ID, so we can reference it in nodes.
      if ($entity_type == 'taxonomy_term') {
        $this->saveTermId($bundle_machine_name, $current_content['id'], $entity->id());
      }

      // Save media entity Drupal ID, so we can reference it in nodes & blocks.
      if ($entity_type == 'media') {
        $this->saveMediaImageId($current_content['id'], $entity->id());
      }

      // Go through all the languages that have translations.
      foreach ($translated_languages as $translated_language) {

        // Find the translated content ID that corresponds to original content.
        $translation_id = array_search($current_content['id'], array_column($all_content[$translated_language], 'id'));

        // Check if translation was found.
        if ($translation_id !== FALSE) {

          // Process that translation.
          $translated_entity = $all_content[$translated_language][$translation_id];
          $structured_content = $this->processContent($bundle_machine_name, $translated_entity, $translated_language);

          // Save entity's translation.
          $entity->addTranslation(
            $translated_language,
            $structured_content
          );
          $entity->save();
        }
      }
    }
    return $this;
  }

  /**
   * Deletes any content imported by this module.
   *
   * @return $this
   */
  public function deleteImportedContent() {
    $uuids = $this->state->get('demo_umami_content_uuids', []);
    $by_entity_type = array_reduce(array_keys($uuids), function ($carry, $uuid) use ($uuids) {
      $entity_type_id = $uuids[$uuid];
      $carry[$entity_type_id][] = $uuid;
      return $carry;
    }, []);
    foreach ($by_entity_type as $entity_type_id => $entity_uuids) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entities = $storage->loadByProperties(['uuid' => $entity_uuids]);
      $storage->delete($entities);
    }
    return $this;
  }

  /**
   * Looks up a user by name, if it is missing the user is created.
   *
   * @param string $name
   *   Username.
   *
   * @return int
   *   User ID.
   */
  protected function getUser($name) {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $users = $user_storage->loadByProperties(['name' => $name]);
    if (empty($users)) {
      // Creating user without any password.
      $user = $user_storage->create([
        'name' => $name,
        'status' => 1,
        'roles' => ['author'],
        'mail' => mb_strtolower(str_replace(' ', '.', $name)) . '@example.com',
      ]);
      $user->enforceIsNew();
      $user->save();
      $this->storeCreatedContentUuids([$user->uuid() => 'user']);
      return $user->id();
    }
    $user = reset($users);
    return $user->id();
  }

  /**
   * Creates a file entity based on an image path.
   *
   * @param string $path
   *   Image path.
   *
   * @return int
   *   File ID.
   */
  protected function createFileEntity($path) {
    $filename = basename($path);
    try {
      $uri = $this->fileSystem->copy($path, 'public://' . $filename, FileExists::Replace);
    }
    catch (FileException) {
      $uri = FALSE;
    }
    $file = $this->entityTypeManager->getStorage('file')->create([
      'uri' => $uri,
      'status' => 1,
    ]);
    $file->save();
    $this->storeCreatedContentUuids([$file->uuid() => 'file']);
    return $file->id();
  }

  /**
   * Stores record of content entities created by this import.
   *
   * @param array $uuids
   *   Array of UUIDs where the key is the UUID and the value is the entity
   *   type.
   */
  protected function storeCreatedContentUuids(array $uuids) {
    $uuids = $this->state->get('demo_umami_content_uuids', []) + $uuids;
    $this->state->set('demo_umami_content_uuids', $uuids);
  }

}
