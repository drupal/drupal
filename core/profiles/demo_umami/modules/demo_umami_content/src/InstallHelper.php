<?php

namespace Drupal\demo_umami_content;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Defines a helper class for importing default content.
 *
 * @internal
 *   This code is only for use by the Umami demo: Content module.
 */
class InstallHelper implements ContainerInjectionInterface {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

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
   * Constructs a new InstallHelper object.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(AliasManagerInterface $aliasManager, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler, StateInterface $state) {
    $this->aliasManager = $aliasManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * Imports default contents.
   */
  public function importContent() {
    $this->importEditors()
      ->importArticles()
      ->importRecipes()
      ->importPages()
      ->importBlockContent();
  }

  /**
   * Imports editors.
   *
   * Other users are created as their content is imported. However, editors
   * don't have their own content so are created here instead.
   *
   * @return $this
   */
  protected function importEditors() {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $editors = [
      'Margaret Hopper',
      'Grace Hamilton',
    ];
    foreach ($editors as $name) {
      $user = $user_storage->create([
        'name' => $name,
        'status' => 1,
        'roles' => ['editor'],
        'mail' => mb_strtolower(str_replace(' ', '.', $name)) . '@example.com',
      ]);
      $user->enforceIsNew();
      $user->save();
      $this->storeCreatedContentUuids([$user->uuid() => 'user']);
    }
    return $this;
  }

  /**
   * Imports articles.
   *
   * @return $this
   */
  protected function importArticles() {
    $module_path = $this->moduleHandler->getModule('demo_umami_content')
      ->getPath();
    if (($handle = fopen($module_path . '/default_content/articles.csv', "r")) !== FALSE) {
      $uuids = [];
      $header = fgetcsv($handle);
      while (($data = fgetcsv($handle)) !== FALSE) {
        $data = array_combine($header, $data);
        // Prepare content.
        $values = [
          'type' => 'article',
          'title' => $data['title'],
          'moderation_state' => 'published',
        ];
        // Fields mapping starts.
        // Set Body Field.
        if (!empty($data['body'])) {
          $body_path = $module_path . '/default_content/article_body/' . $data['body'];
          $body = file_get_contents($body_path);
          if ($body !== FALSE) {
            $values['body'] = [['value' => $body, 'format' => 'basic_html']];
          }
        }
        // Set node alias if exists.
        if (!empty($data['slug'])) {
          $values['path'] = [['alias' => '/' . $data['slug']]];
        }
        // Set field_tags if exists.
        if (!empty($data['tags'])) {
          $values['field_tags'] = [];
          $tags = explode(',', $data['tags']);
          foreach ($tags as $term) {
            $values['field_tags'][] = ['target_id' => $this->getTerm($term)];
          }
        }
        // Set article author.
        if (!empty($data['author'])) {
          $values['uid'] = $this->getUser($data['author']);
        }
        // Set Image field.
        if (!empty($data['image'])) {
          $path = $module_path . '/default_content/images/' . $data['image'];
          $values['field_image'] = [
            'target_id' => $this->createFileEntity($path),
            'alt' => $data['alt'],
          ];
        }

        // Create Node.
        $node = $this->entityTypeManager->getStorage('node')->create($values);
        $node->save();
        $uuids[$node->uuid()] = 'node';
      }
      $this->storeCreatedContentUuids($uuids);
      fclose($handle);
    }
    return $this;
  }

  /**
   * Imports recipes.
   *
   * @return $this
   */
  protected function importRecipes() {
    $module_path = $this->moduleHandler->getModule('demo_umami_content')->getPath();

    if (($handle = fopen($module_path . '/default_content/recipes.csv', "r")) !== FALSE) {
      $header = fgetcsv($handle);
      $uuids = [];
      while (($data = fgetcsv($handle)) !== FALSE) {
        $data = array_combine($header, $data);
        $values = [
          'type' => 'recipe',
          // Title field.
          'title' => $data['title'],
          'moderation_state' => 'published',
        ];
        // Set article author.
        if (!empty($data['author'])) {
          $values['uid'] = $this->getUser($data['author']);
          $values['field_author'] = $values['uid'];
        }
        // Set node alias if exists.
        if (!empty($data['slug'])) {
          $values['path'] = [['alias' => '/' . $data['slug']]];
        }
        // Set field_image field.
        if (!empty($data['image'])) {
          $image_path = $module_path . '/default_content/images/' . $data['image'];
          $values['field_image'] = [
            'target_id' => $this->createFileEntity($image_path),
            'alt' => $data['alt'],
          ];
        }
        // Set field_summary Field.
        if (!empty($data['summary'])) {
          $values['field_summary'] = [['value' => $data['summary'], 'format' => 'basic_html']];
        }
        // Set field_recipe_category if exists.
        if (!empty($data['recipe_category'])) {
          $values['field_recipe_category'] = [];
          $tags = array_filter(explode(',', $data['recipe_category']));
          foreach ($tags as $term) {
            $values['field_recipe_category'][] = ['target_id' => $this->getTerm($term, 'recipe_category')];
          }
        }
        // Set field_preparation_time Field.
        if (!empty($data['preparation_time'])) {
          $values['field_preparation_time'] = [['value' => $data['preparation_time']]];
        }
        // Set field_cooking_time Field.
        if (!empty($data['cooking_time'])) {
          $values['field_cooking_time'] = [['value' => $data['cooking_time']]];
        }
        // Set field_difficulty Field.
        if (!empty($data['difficulty'])) {
          $values['field_difficulty'] = $data['difficulty'];
        }
        // Set field_number_of_servings Field.
        if (!empty($data['number_of_servings'])) {
          $values['field_number_of_servings'] = [['value' => $data['number_of_servings']]];
        }
        // Set field_ingredients Field.
        if (!empty($data['ingredients'])) {
          $ingredients = explode(',', $data['ingredients']);
          $values['field_ingredients'] = [];
          foreach ($ingredients as $ingredient) {
            $values['field_ingredients'][] = ['value' => $ingredient];
          }
        }
        // Set field_recipe_instruction Field.
        if (!empty($data['recipe_instruction'])) {
          $recipe_instruction_path = $module_path . '/default_content/recipe_instructions/' . $data['recipe_instruction'];
          $recipe_instructions = file_get_contents($recipe_instruction_path);
          if ($recipe_instructions !== FALSE) {
            $values['field_recipe_instruction'] = [['value' => $recipe_instructions, 'format' => 'basic_html']];
          }
        }
        // Set field_tags if exists.
        if (!empty($data['tags'])) {
          $values['field_tags'] = [];
          $tags = array_filter(explode(',', $data['tags']));
          foreach ($tags as $term) {
            $values['field_tags'][] = ['target_id' => $this->getTerm($term)];
          }
        }

        $node = $this->entityTypeManager->getStorage('node')->create($values);
        $node->save();
        $uuids[$node->uuid()] = 'node';
      }
      $this->storeCreatedContentUuids($uuids);
      fclose($handle);
    }
    return $this;
  }

  /**
   * Imports pages.
   *
   * @return $this
   */
  protected function importPages() {
    if (($handle = fopen($this->moduleHandler->getModule('demo_umami_content')->getPath() . '/default_content/pages.csv', "r")) !== FALSE) {
      $headers = fgetcsv($handle);
      $uuids = [];
      while (($data = fgetcsv($handle)) !== FALSE) {
        $data = array_combine($headers, $data);

        // Prepare content.
        $values = [
          'type' => 'page',
          'title' => $data['title'],
          'moderation_state' => 'published',
        ];
        // Fields mapping starts.
        // Set Body Field.
        if (!empty($data['body'])) {
          $values['body'] = [['value' => $data['body'], 'format' => 'basic_html']];
        }
        // Set node alias if exists.
        if (!empty($data['slug'])) {
          $values['path'] = [['alias' => '/' . $data['slug']]];
        }
        // Set article author.
        if (!empty($data['author'])) {
          $values['uid'] = $this->getUser($data['author']);
        }

        // Create Node.
        $node = $this->entityTypeManager->getStorage('node')->create($values);
        $node->save();
        $uuids[$node->uuid()] = 'node';
      }
      $this->storeCreatedContentUuids($uuids);
      fclose($handle);
    }
    return $this;
  }

  /**
   * Imports block content entities.
   *
   * @return $this
   */
  protected function importBlockContent() {
    $module_path = $this->moduleHandler->getModule('demo_umami_content')->getPath();
    $block_content_entities = [
      'umami_recipes_banner' => [
        'uuid' => '4c7d58a3-a45d-412d-9068-259c57e40541',
        'info' => 'Umami Recipes Banner',
        'type' => 'banner_block',
        'field_title' => [
          'value' => 'Super easy vegetarian pasta bake',
        ],
        'field_content_link' => [
          'uri' => 'internal:' . call_user_func(function () {
            $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['title' => 'Super easy vegetarian pasta bake']);
            $node = reset($nodes);
            return $this->aliasManager->getAliasByPath('/node/' . $node->id());
          }),
          'title' => 'Super easy vegetarian pasta bake',
        ],
        'field_summary' => [
          'value' => 'A wholesome pasta bake is the ultimate comfort food. This delicious bake is super quick to prepare and an ideal midweek meal for all the family.',
        ],
        'field_banner_image' => [
          'target_id' => $this->createFileEntity($module_path . '/default_content/images/veggie-pasta-bake-hero-umami.jpg'),
          'alt' => 'Mouth watering vegetarian pasta bake with rich tomato sauce and cheese toppings',
        ],
      ],
      'umami_disclaimer' => [
        'uuid' => '9b4dcd67-99f3-48d0-93c9-2c46648b29de',
        'info' => 'Umami disclaimer',
        'type' => 'disclaimer_block',
        'field_disclaimer' => [
          'value' => '<strong>Umami Magazine & Umami Publications</strong> is a fictional magazine and publisher for illustrative purposes only.',
          'format' => 'basic_html',
        ],
        'field_copyright' => [
          'value' => '&copy; 2018 Terms & Conditions',
          'format' => 'basic_html',
        ],
      ],
      'umami_footer_promo' => [
        'uuid' => '924ab293-8f5f-45a1-9c7f-2423ae61a241',
        'info' => 'Umami footer promo',
        'type' => 'footer_promo_block',
        'field_title' => [
          'value' => 'Umami Food Magazine',
        ],
        'field_summary' => [
          'value' => 'Skills and know-how. Magazine exclusive articles, recipes and plenty of reasons to get your copy today.',
        ],
        'field_content_link' => [
          'uri' => 'internal:' . call_user_func(function () {
            $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['title' => 'About Umami']);
            $node = reset($nodes);
            return $this->aliasManager->getAliasByPath('/node/' . $node->id());
          }),
          'title' => 'Find out more',
        ],
        'field_promo_image' => [
          'target_id' => $this->createFileEntity($module_path . '/default_content/images/umami-bundle.png'),
          'alt' => '3 issue bundle of the Umami food magazine',
        ],
      ],
    ];

    // Create block content.
    foreach ($block_content_entities as $values) {
      $block_content = $this->entityTypeManager->getStorage('block_content')->create($values);
      $block_content->save();
      $this->storeCreatedContentUuids([$block_content->uuid() => 'block_content']);
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
    $users = $user_storage->loadByProperties(['name' => $name]);;
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
   * Looks up a term by name, if it is missing the term is created.
   *
   * @param string $term_name
   *   Term name.
   * @param string $vocabulary_id
   *   Vocabulary ID.
   *
   * @return int
   *   Term ID.
   */
  protected function getTerm($term_name, $vocabulary_id = 'tags') {
    $term_name = trim($term_name);
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties([
      'name' => $term_name,
      'vid' => $vocabulary_id,
    ]);
    if (!$terms) {
      $term = $term_storage->create([
        'name' => $term_name,
        'vid' => $vocabulary_id,
        'path' => ['alias' => '/' . Html::getClass($vocabulary_id) . '/' . Html::getClass($term_name)],
      ]);
      $term->save();
      $this->storeCreatedContentUuids([$term->uuid() => 'taxonomy_term']);
      return $term->id();
    }
    $term = reset($terms);
    return $term->id();
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
    $uri = $this->fileUnmanagedCopy($path);
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

  /**
   * Wrapper around file_unmanaged_copy().
   *
   * @param string $path
   *   Path to image.
   *
   * @return string|false
   *   The path to the new file, or FALSE in the event of an error.
   */
  protected function fileUnmanagedCopy($path) {
    $filename = basename($path);
    return file_unmanaged_copy($path, 'public://' . $filename, FILE_EXISTS_REPLACE);
  }

}
