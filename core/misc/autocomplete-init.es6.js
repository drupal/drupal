((Drupal, drupalSettings, A11yAutocomplete, Popper, once) => {
  Drupal.Autocomplete = {};
  Drupal.Autocomplete.instances = {};
  Drupal.Autocomplete.defaultOptions = {
    // Add jQuery UI classes so the autocomplete is styled the same as its
    // jQuery UI predecessor.
    inputClass: 'ui-autocomplete-input',
    ulClass: 'ui-menu ui-widget ui-widget-content ui-autocomplete ui-front',
    loadingClass: 'ui-autocomplete-loading',
    itemClass: 'ui-menu-item-wrapper',
    // Do not create an autocomplete-specific live region since
    // #drupal-live-announce will be used.
    createLiveRegion: false,
    displayLabels: false,
  };

  /**
   * Initializes an input to use Drupal Autocomplete.
   *
   * @param {Element} autocompleteInput
   *  The autocomplete input.
   */
  Drupal.Autocomplete.initialize = (autocompleteInput) => {
    const options = Drupal.Autocomplete.defaultOptions || {};
    options.inputAssistiveHint = Drupal.t(
      'When autocomplete results are available use up and down arrows to review and enter to select.  Touch device users, explore by touch or with swipe gestures.',
    );

    // Disable the creation of autocomplete-specific live regions.
    // Drupal.announce() will be used instead.
    options.liveRegion = false;

    /**
     * Formats a message reporting the number of results in a search.
     *
     * This overrides A11yAutocomplete.suggestionItem()
     *
     * @param {number} count
     *   The number of results.
     *
     * @return {string}
     *   The message to be announced by assistive technology.
     */
    function autocompleteResultsMessage(count) {
      const { maxItems } = this.options;
      if (count === 0) {
        return Drupal.t('No results found');
      }

      return Drupal.formatPlural(
        count,
        'There is one result available.',
        maxItems <= this.totalSuggestions
          ? 'There are at least @count results available. Type additional characters to refine your search.'
          : 'There are @count results available.',
      );
    }

    /**
     * Formats a message reporting a suggestion has been highlighted.
     *
     * This overrides A11yAutocomplete.highlightMessage()
     *
     * @param {object} item
     *   The suggestion item being highlighted.
     *
     * @return {string}
     *   The message to be announced by assistive technology.
     */
    function autocompleteHighlightMessage(item) {
      return Drupal.t('@item @count of @total is highlighted', {
        '@item': item.innerText,
        '@count': item.getAttribute('aria-posinset'),
        '@total': this.ul.children.length,
      });
    }

    /**
     * Sends a message to assistive technology.
     *
     * This overrides A11yAutocomplete.sendToLiveRegion()
     *
     * @param {string} message
     *   The message to be announced.
     */
    function autocompleteSendToLiveRegion(message) {
      Drupal.announce(message, 'assertive');
    }

    // Several A11yAutocomplete methods are overridden so Drupal.t() and
    // Drupal.announce() can be used without the class itself requiring
    // Drupal.
    const id = autocompleteInput.getAttribute('id');
    Drupal.Autocomplete.instances[id] = new A11yAutocomplete(
      autocompleteInput,
      options,
    );
    const instance = Drupal.Autocomplete.instances[id];
    instance.resultsMessage = autocompleteResultsMessage;
    instance.sendToLiveRegion = autocompleteSendToLiveRegion;
    instance.highlightMessage = autocompleteHighlightMessage;

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
    attach(context) {
      once('autocomplete-init', 'input.form-autocomplete').forEach(
        (autocompleteInput) => {
          // The default cardinality of A11yAutocomplete is 1. Fields in Drupal
          // without explicitly set cardinality should be set to -1, which
          // provides unlimited cardinality.
          if (
            !autocompleteInput.hasAttribute('data-autocomplete-cardinality')
          ) {
            autocompleteInput.setAttribute(
              'data-autocomplete-cardinality',
              '-1',
            );
          }
          Drupal.Autocomplete.initialize(autocompleteInput);

          // @todo remove this conditional and its contents, in
          //   https://drupal.org/node/3206225, it is not needed in Drupal 10.
          if (!autocompleteInput.hasAttribute('data-core-autocomplete')) {
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
})(Drupal, drupalSettings, A11yAutocomplete, Popper, once);
