((Drupal, drupalSettings, A11yAutocomplete, once) => {
  Drupal.Autocomplete = {};
  Drupal.Autocomplete.instances = {};

  // These are the default options when initializing an autocomplete. These
  // can be overridden in several ways. These are listed in order of highest
  // precedence to least:
  // 1 - An object literal in the input's `data-autocomplete` attribute with the
  //   structure `{camelCaseOptionName: value}`.
  // 2 - An input's `data-autocomplete-(hyphen delimited option name)`
  //   attribute.
  // 3 - The options object provided to the constructor.
  Drupal.Autocomplete.defaultOptions = {
    // Add jQuery UI classes so the autocomplete is styled the same as its
    // jQuery UI predecessor.
    inputClass: 'ui-autocomplete-input',
    ulClass: 'ui-menu ui-widget ui-widget-content ui-autocomplete ui-front',
    loadingClass: 'ui-autocomplete-loading',
    // In jQuery UI autocomplete, the ui-menu-item-wrapper class is added to
    // the `<a>` tag inside each list item. A11yAutocomplete does not wrap
    // items in`<a>` tags, so this class is moved to the `<li>` which provides
    // a visually identical autocomplete experience to the previous jQuery UI
    // autocomplete.
    itemClass: 'ui-menu-item-wrapper',
    // Do not create an autocomplete-specific live region since
    // #drupal-live-announce will be used.
    createLiveRegion: false,
    displayLabels: false,
    // The assistive hint overrides intentionally use placeholders without
    // having them populated in the Drupal.t() call. These placeholders are
    // replaced with their expected values in A11yAutocomplete, which uses
    // the same placeholder format.
    minCharAssistiveHint: Drupal.t(
      'Type @count or more characters for results',
    ),
    noResults: Drupal.t('No results found'),
    moreThanMaxResults: Drupal.t(
      'There are at least @count results available. Type additional characters to refine your search.',
    ),
    someResults: Drupal.t('There are @count results available.'),
    oneResult: Drupal.t('There is one result available.'),
    inputAssistiveHint: Drupal.t(
      'When autocomplete results are available use up and down arrows to review and enter to select.  Touch device users, explore by touch or with swipe gestures.',
    ),
    highlightedAssistiveHint: Drupal.t(
      '@selectedItem @position of @count is highlighted',
    ),
  };

  /**
   * Initializes an input to use Drupal Autocomplete.
   *
   * @param {Element} autocompleteInput
   *  The autocomplete input.
   */
  Drupal.Autocomplete.initialize = (autocompleteInput) => {
    const options = Drupal.Autocomplete.defaultOptions || {};
    const id = autocompleteInput.getAttribute('id');
    if (
      autocompleteInput.hasAttribute(
        'data-autocomplete-first-character-blacklist',
      )
    ) {
      options.firstCharacterDenylist = autocompleteInput.getAttribute(
        'data-autocomplete-first-character-blacklist',
      );
      Drupal.deprecationError({
        message:
          'The data-autocomplete-first-character-blacklist attribute is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use data-autocomplete-first-character-denylist instead See https://www.drupal.org/node/3083715',
      });
    }

    Drupal.Autocomplete.instances[id] = new A11yAutocomplete(
      autocompleteInput,
      options,
    );
    const instance = Drupal.Autocomplete.instances[id];

    /**
     * Sends a message to assistive technology.
     *
     * This overrides A11yAutocomplete.sendToLiveRegion() so screen reader
     * announcements are handled by Drupal.announce instead of live regions
     * provided by A11yAutocomplete.
     *
     * @param {string} message
     *   The message to be announced.
     */
    function autocompleteSendToLiveRegion(message) {
      Drupal.announce(message, 'assertive');
    }

    instance.sendToLiveRegion = autocompleteSendToLiveRegion;

    instance.input.addEventListener('autocomplete-destroy', (e) => {
      delete Drupal.Autocomplete.instances[
        e.detail.autocomplete.input.getAttribute('id')
      ];
    });
  };

  /**
   * Attaches the autocomplete behavior to fields configured for autocomplete.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   */
  Drupal.behaviors.autocomplete = {
    attach() {
      once('autocomplete-init', 'input.form-autocomplete').forEach(
        (autocompleteInput) => {
          // The default cardinality of A11yAutocomplete is 1. Fields in Drupal
          // without explicitly set cardinality should be set to -1, which
          // provides unlimited cardinality. This setting is applied via the
          // data-autocomplete-cardinality attribute as it the highest
          // precedence way to set an option.
          if (
            !autocompleteInput.hasAttribute('data-autocomplete-cardinality')
          ) {
            autocompleteInput.setAttribute(
              'data-autocomplete-cardinality',
              '-1',
            );
          }
          Drupal.Autocomplete.initialize(autocompleteInput);

          // By default, autocomplete inputs are processed with a backwards
          // compatible shim that provides jQuery UI autocomplete markup
          // structure and API surface. If the input has the
          // 'data-drupal-10-autocomplete' attribute, this shim is not invoked.
          // Without the shim, the markup and API will be what is provided in
          // Drupal 10.
          // @todo remove this conditional and its contents, in
          //   https://drupal.org/node/3206225, it is not needed in Drupal 10.
          if (!autocompleteInput.hasAttribute('data-drupal-10-autocomplete')) {
            Drupal.Autocomplete.jqueryUiShimInit(autocompleteInput);
          }
        },
      );
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        context.querySelectorAll('input.form-autocomplete').forEach((input) => {
          const id = input.getAttribute('id');
          Drupal.Autocomplete.instances[id].destroy();
        });
      }
    },
  };
})(Drupal, drupalSettings, A11yAutocomplete, once);
