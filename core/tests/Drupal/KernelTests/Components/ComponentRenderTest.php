<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Components;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Render\Component\Exception\InvalidComponentDataException;
use Drupal\Tests\Core\Theme\Component\ComponentKernelTestBase;

/**
 * Tests the correct rendering of components.
 *
 * @group sdc
 */
class ComponentRenderTest extends ComponentKernelTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'sdc_test'];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['sdc_theme_test'];

  /**
   * Test that components render correctly.
   */
  public function testRender(): void {
    $this->checkIncludeDefaultContent();
    $this->checkIncludeDataMapping();
    $this->checkEmbedWithNested();
    $this->checkPropValidation();
    $this->checkArrayObjectTypeCast();
    $this->checkNonExistingComponent();
    $this->checkLibraryOverrides();
    $this->checkAttributeMerging();
    $this->checkRenderElementAlters();
    $this->checkSlots();
    $this->checkInvalidSlot();
    $this->checkEmptyProps();
  }

  /**
   * Check using a component with an include and default context.
   */
  protected function checkIncludeDefaultContent(): void {
    $build = [
      '#type' => 'inline_template',
      '#template' => "{% embed('sdc_theme_test_base:my-card-no-schema') %}{% block card_body %}Foo bar{% endblock %}{% endembed %}",
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_theme_test_base:my-card-no-schema"] .component--my-card-no-schema__body:contains("Foo bar")'));
  }

  /**
   * Check using a component with an include and no default context.
   *
   * This covers passing a render array to a 'string' prop, and mapping the
   * prop to a context variable.
   */
  protected function checkIncludeDataMapping(): void {
    $content = [
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Another button ç',
      ],
    ];
    $build = [
      '#type' => 'inline_template',
      '#context' => ['content' => $content],
      '#template' => "{{ include('sdc_test:my-button', { text: content.label, iconType: 'external' }, with_context = false) }}",
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper button:contains("Another button ç")'));
  }

  /**
   * Render a card with slots that include a CTA component.
   */
  protected function checkEmbedWithNested(): void {
    $content = [
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Just a link',
      ],
    ];
    $build = [
      '#type' => 'inline_template',
      '#context' => ['content' => $content],
      '#template' => "{% embed 'sdc_theme_test:my-card' with { header: 'Card header', content: content } only %}{% block card_body %}This is a card with a CTA {{ include('sdc_test:my-cta', { text: content.heading, href: 'https://www.example.org', target: '_blank' }, with_context = false) }}{% endblock %}{% endembed %}",
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_theme_test:my-card"] h2.component--my-card__header:contains("Card header")'));
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_theme_test:my-card"] .component--my-card__body:contains("This is a card with a CTA")'));
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_theme_test:my-card"] .component--my-card__body a[data-component-id="sdc_test:my-cta"]:contains("Just a link")'));
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_theme_test:my-card"] .component--my-card__body a[data-component-id="sdc_test:my-cta"][href="https://www.example.org"][target="_blank"]'));

    // Now render a component and assert it contains the debug comments.
    $build = [
      '#type' => 'component',
      '#component' => 'sdc_test:my-banner',
      '#props' => [
        'heading' => $this->t('I am a banner'),
        'ctaText' => $this->t('Click me'),
        'ctaHref' => 'https://www.example.org',
        'ctaTarget' => '',
      ],
      '#slots' => [
        'banner_body' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('This is the contents of the banner body.'),
        ],
      ],
    ];
    $metadata = new BubbleableMetadata();
    $this->renderComponentRenderArray($build, $metadata);
    $this->assertEquals(['core/components.sdc_test--my-cta', 'core/components.sdc_test--my-banner'], $metadata->getAttachments()['library']);
  }

  /**
   * Check using the libraryOverrides.
   */
  protected function checkLibraryOverrides(): void {
    $build = [
      '#type' => 'inline_template',
      '#template' => "{{ include('sdc_theme_test:lib-overrides') }}",
    ];
    $metadata = new BubbleableMetadata();
    $this->renderComponentRenderArray($build, $metadata);
    $this->assertEquals(['core/components.sdc_theme_test--lib-overrides'], $metadata->getAttachments()['library']);
  }

  /**
   * Ensures the schema violations are reported properly.
   */
  protected function checkPropValidation(): void {
    // 1. Violates the minLength for the text property.
    $content = ['label' => '1'];
    $build = [
      '#type' => 'inline_template',
      '#context' => ['content' => $content],
      '#template' => "{{ include('sdc_test:my-button', { text: content.label, iconType: 'external' }, with_context = false) }}",
    ];
    try {
      $this->renderComponentRenderArray($build);
      $this->fail('Invalid prop did not cause an exception');
    }
    catch (\Throwable $e) {
      $this->addToAssertionCount(1);
    }

    // 2. Violates the required header property.
    $build = [
      '#type' => 'inline_template',
      '#context' => [],
      '#template' => "{{ include('sdc_theme_test:my-card', with_context = false) }}",
    ];
    try {
      $this->renderComponentRenderArray($build);
      $this->fail('Invalid prop did not cause an exception');
    }
    catch (\Throwable $e) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Ensure fuzzy coercing of arrays and objects works properly.
   */
  protected function checkArrayObjectTypeCast(): void {
    $content = ['test' => []];
    $build = [
      '#type' => 'inline_template',
      '#context' => ['content' => $content],
      '#template' => "{{ include('sdc_test:array-to-object', { testProp: content.test }, with_context = false) }}",
    ];
    try {
      $this->renderComponentRenderArray($build);
      $this->addToAssertionCount(1);
    }
    catch (\Throwable $e) {
      $this->fail('Empty array was not converted to object');
    }
  }

  /**
   * Ensures that including an invalid component creates an error.
   */
  protected function checkNonExistingComponent(): void {
    $build = [
      '#type' => 'inline_template',
      '#context' => [],
      '#template' => "{{ include('sdc_test:INVALID', with_context = false) }}",
    ];
    try {
      $this->renderComponentRenderArray($build);
      $this->fail('Invalid prop did not cause an exception');
    }
    catch (\Throwable $e) {
      $this->addToAssertionCount(1);
    }
  }

  /**
   * Ensures the attributes are merged properly.
   */
  protected function checkAttributeMerging(): void {
    $content = ['label' => 'I am a labels'];
    // 1. Check that if it exists Attribute object in the 'attributes' prop, you
    // get them merged.
    $build = [
      '#type' => 'inline_template',
      '#context' => [
        'content' => $content,
        'attributes' => new Attribute(['data-merged-attributes' => 'yes']),
      ],
      '#template' => "{{ include('sdc_test:my-button', { text: content.label, iconType: 'external', attributes: attributes }, with_context = false) }}",
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-merged-attributes="yes"][data-component-id="sdc_test:my-button"]'), $crawler->outerHtml());
    // 2. Check that if the 'attributes' exists, but there is some other data
    // type, then we don't touch it.
    $build = [
      '#type' => 'inline_template',
      '#context' => [
        'content' => $content,
        'attributes' => 'hard-coded-attr',
      ],
      '#template' => "{{ include('sdc_theme_test_base:my-card-no-schema', { header: content.label, attributes: attributes }, with_context = false) }}",
    ];
    $crawler = $this->renderComponentRenderArray($build);
    // The default data attribute should be missing.
    $this->assertEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_theme_test_base:my-card-no-schema"]'), $crawler->outerHtml());
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [hard-coded-attr]'), $crawler->outerHtml());
    // 3. Check that if the 'attributes' is empty, we get the defaults.
    $build = [
      '#type' => 'inline_template',
      '#context' => ['content' => $content],
      '#template' => "{{ include('sdc_theme_test_base:my-card-no-schema', { header: content.label }, with_context = false) }}",
    ];
    $crawler = $this->renderComponentRenderArray($build);
    // The default data attribute should not be missing.
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_theme_test_base:my-card-no-schema"]'), $crawler->outerHtml());
  }

  /**
   * Ensures the alter callbacks work properly.
   */
  public function checkRenderElementAlters(): void {
    $build = [
      '#type' => 'component',
      '#component' => 'sdc_test:my-banner',
      '#props' => [
        'heading' => $this->t('I am a banner'),
        'ctaText' => $this->t('Click me'),
        'ctaHref' => 'https://www.example.org',
        'ctaTarget' => '',
      ],
      '#propsAlter' => [
        fn ($props) => [...$props, 'heading' => $this->t('I am another banner')],
      ],
      '#slots' => [
        'banner_body' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('This is the contents of the banner body.'),
        ],
      ],
      '#slotsAlter' => [
        static fn ($slots) => [...$slots, 'banner_body' => ['#markup' => '<h2>Just something else.</h2>']],
      ],
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_test:my-banner"] .component--my-banner--header h3:contains("I am another banner")'));
    $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_test:my-banner"] .component--my-banner--body:contains("Just something else.")'));
  }

  /**
   * Ensure that the slots allow a render array or a scalar when using the render element.
   */
  public function checkSlots(): void {
    $slots = [
      'This is the contents of the banner body.',
      [
        '#plain_text' => 'This is the contents of the banner body.',
      ],
    ];
    foreach ($slots as $slot) {
      $build = [
        '#type' => 'component',
        '#component' => 'sdc_test:my-banner',
        '#props' => [
          'heading' => $this->t('I am a banner'),
          'ctaText' => $this->t('Click me'),
          'ctaHref' => 'https://www.example.org',
          'ctaTarget' => '',
        ],
        '#slots' => [
          'banner_body' => $slot,
        ],
      ];
      $crawler = $this->renderComponentRenderArray($build);
      $this->assertNotEmpty($crawler->filter('#sdc-wrapper [data-component-id="sdc_test:my-banner"] .component--my-banner--body:contains("This is the contents of the banner body.")'));
    }
  }

  /**
   * Ensure that the slots throw an error for invalid slots.
   */
  public function checkInvalidSlot(): void {
    $build = [
      '#type' => 'component',
      '#component' => 'sdc_test:my-banner',
      '#props' => [
        'heading' => $this->t('I am a banner'),
        'ctaText' => $this->t('Click me'),
        'ctaHref' => 'https://www.example.org',
        'ctaTarget' => '',
      ],
      '#slots' => [
        'banner_body' => new \stdClass(),
      ],
    ];
    $this->expectException(InvalidComponentDataException::class);
    $this->expectExceptionMessage('Unable to render component "sdc_test:my-banner". A render array or a scalar is expected for the slot "banner_body" when using the render element with the "#slots" property');
    $this->renderComponentRenderArray($build);
  }

  /**
   * Ensure that components can have 0 props.
   */
  public function checkEmptyProps(): void {
    $build = [
      '#type' => 'component',
      '#component' => 'sdc_test:no-props',
      '#props' => [],
    ];
    $crawler = $this->renderComponentRenderArray($build);
    $this->assertEquals(
      $crawler->filter('#sdc-wrapper')->innerText(),
      'This is a test string.'
    );
  }

  /**
   * Ensures some key aspects of the plugin definition are correctly computed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testPluginDefinition(): void {
    $plugin_manager = \Drupal::service('plugin.manager.sdc');
    assert($plugin_manager instanceof ComponentPluginManager);
    $definition = $plugin_manager->getDefinition('sdc_test:my-banner');
    $this->assertSame('my-banner', $definition['machineName']);
    $this->assertStringEndsWith('system/tests/modules/sdc_test/components/my-banner', $definition['path']);
    $this->assertEquals(['core/drupal'], $definition['library']['dependencies']);
    $this->assertNotEmpty($definition['library']['css']['component']);
    $this->assertSame('my-banner.twig', $definition['template']);
    $this->assertNotEmpty($definition['documentation']);
  }

}
