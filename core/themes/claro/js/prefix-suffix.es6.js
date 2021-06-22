/**
 * @file
 * Optimizes the presentation of form item affixes.
 */
(($, Drupal, debounce) => {
  /**
   * Constructs a new instance of the Drupal.PrefixSuffix class.
   *
   * This adds functionality to inputs with affixes that optimizes presentation
   * based on viewport width and affix content length.
   *
   * @param {HTMLElement} elementWrapper
   *   The  wrapper of the form element that has an affix.
   */
  Drupal.PrefixSuffix = class {
    constructor(elementWrapper) {
      const input = elementWrapper.querySelector('input');

      this.elementWrapper = elementWrapper;
      this.prefix = elementWrapper.querySelector(
        '[data-drupal-form-item-prefix]',
      );
      this.suffix = elementWrapper.querySelector(
        '[data-drupal-form-item-suffix]',
      );

      const prefixSuffix = debounce(this.calculatePrefixSuffix.bind(this), 300);
      $(window).on('resize.prefixSuffix', prefixSuffix);
      $(input).on('formUpdated.machineName', prefixSuffix);

      // When CKEditor is ready, the input widths may change. The prefixes and
      // suffixes need to be recalculated against these changed widths.
      if (window.CKEDITOR) {
        CKEDITOR.on('instanceReady', prefixSuffix);
      }

      // Initialize the prefix+suffix style calculations.
      prefixSuffix();
    }

    /**
     * Resets several values so positions and widths can be recalculated.
     */
    resetDynamicValues() {
      this.elementWrapper.classList.remove(
        'form-item__wrapper--stacked-prefix',
      );
      this.elementWrapper.classList.remove(
        'form-item__wrapper--stacked-suffix',
      );
      if (this.prefix) {
        this.prefix.style.width = 'auto';
        this.prefix.classList.remove('form-item__prefix--stacked');
        this.prefix.classList.remove('form-item__affix--stacked');
      }

      if (this.suffix) {
        this.suffix.classList.remove('form-item__suffix--stacked');
        this.suffix.classList.remove('form-item__affix--stacked');
        this.suffix.style.left = 'auto';
        this.suffix.style.right = 'auto';
        this.suffix.style.width = 'auto';
      }
    }

    /**
     * Determine the width of an affixed form input inside a table cell.
     *
     * When an affix is stacked and inside a table cell, it's possible for that
     * affix to be the element to undesirably inform the width of the cell and
     * input. This temporarily removes the content from the stacked affix
     * prior to determining width, and brings the content back immediately
     * afterward.
     *
     *
     * @param {boolean} stackedPrefix
     *   If the prefix is above the input.
     * @param {boolean} stackedSuffix
     *   If the suffix is below the input.
     *
     * @return {number}
     *   The total width of the wrapper's non-affix child elements.
     */
    getInputWidthInTableCell(stackedPrefix, stackedSuffix) {
      const { children } = this.elementWrapper;
      let inputWidth = 0;
      let prefixContent = '';
      let suffixContent = '';

      // If stacked affixes are present, their content must be temporarily
      // removed so they don't influence the calculated width of the input they
      // are affixed to.
      if (stackedPrefix) {
        prefixContent = this.prefix.innerHTML;
        this.prefix.innerHTML = '';
      }
      if (stackedSuffix) {
        suffixContent = this.suffix.innerHTML;
        this.suffix.innerHTML = '';
      }

      for (let i = 0; i < children.length; i++) {
        const rect = children[i].getBoundingClientRect();
        if (
          !children[i].hasAttribute('data-drupal-form-item-prefix') &&
          !children[i].hasAttribute('data-drupal-form-item-suffix')
        ) {
          inputWidth += rect.width;
        }
      }

      if (stackedPrefix) {
        this.prefix.innerHTML = prefixContent;
      }
      if (stackedSuffix) {
        this.suffix.innerHTML = suffixContent;
      }

      return inputWidth;
    }

    /**
     * Dynamically assigns classes and styles to prefixes and suffixes.
     */
    calculatePrefixSuffix() {
      const { children } = this.elementWrapper;

      // Reset these elements to their default styling and remove all
      // dynamically added classes before recalculating.
      this.resetDynamicValues();

      // This object will collect the y offsets of each element's vertical
      // center.
      const yCenters = {};

      // When the loop below is complete, this will have the total width of all
      // child elements that are not affixes.
      let inputWidth = 0;
      for (let i = 0; i < children.length; i++) {
        const rect = children[i].getBoundingClientRect();

        // This is the y offset of the element's vertical center.
        const yCenter = rect.top + rect.height / 2;

        if (children[i].hasAttribute('data-drupal-form-item-prefix')) {
          yCenters.prefix = yCenter;
        } else if (children[i].hasAttribute('data-drupal-form-item-suffix')) {
          yCenters.suffix = yCenter;
        } else {
          yCenters.input = yCenter;

          // Temporarily hide the input in order to determine if width is set
          // to 100%.
          children[i].style.display = 'none';
          const cssWidth = window
            .getComputedStyle(children[i])
            .getPropertyValue('width');
          children[i].style.display = 'flex';

          if (cssWidth === '100%') {
            inputWidth = cssWidth;
          } else if (typeof inputWidth === 'number') {
            inputWidth += rect.width;
          }
        }
      }

      const stackedPrefix =
        yCenters.hasOwnProperty('prefix') && yCenters.prefix !== yCenters.input;

      const stackedSuffix =
        yCenters.hasOwnProperty('suffix') && yCenters.suffix !== yCenters.input;

      // If the form element is in a table cell and has a stacked prefix or
      // suffix, the input width must be determined differently.
      // jQuery is used for calling closest(), as it is not yet supported
      // natively in IE11.
      if (
        $(this.elementWrapper).closest('td').length > 0 &&
        (stackedSuffix || stackedPrefix) &&
        typeof inputWidth === 'number'
      ) {
        inputWidth = this.getInputWidthInTableCell(
          stackedPrefix,
          stackedSuffix,
        );
      }

      // If the form element has a prefix, but that prefix is above the input.
      if (stackedPrefix) {
        this.elementWrapper.classList.add('form-item__wrapper--stacked-prefix');
        this.prefix.classList.add('form-item__prefix--stacked');
        this.prefix.classList.add('form-item__affix--stacked');

        // The prefix should have the same width as the input below it.
        this.prefix.style.width =
          typeof inputWidth === 'number' ? `${inputWidth}px` : inputWidth;
      }

      // If the form element has a suffix, but that suffix is below the input.
      if (stackedSuffix) {
        this.elementWrapper.classList.add('form-item__wrapper--stacked-suffix');
        this.suffix.classList.add('form-item__suffix--stacked');
        this.suffix.classList.add('form-item__affix--stacked');

        // The suffix should have the same width as the input above it.
        this.suffix.style.width =
          typeof inputWidth === 'number' ? `${inputWidth}px` : inputWidth;

        // If there is a prefix next to the input and a suffix below the input,
        // the suffix must be styled so it is horizontally aligned with the
        // input instead of the prefix.
        if (
          yCenters.hasOwnProperty('prefix') &&
          yCenters.prefix === yCenters.input
        ) {
          const edge =
            document.documentElement.dir === 'rtl' ? 'right' : 'left';
          this.suffix.style[edge] = `${this.prefix.offsetWidth}px`;
        }
      }
    }
  };

  /**
   * Initializes presentation behavior for form element affixes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the presentation behavior to form element with affixes.
   */
  Drupal.behaviors.prefixSuffix = {
    attach(context) {
      const $prefixSuffixElements = $(context)
        .find('[data-drupal-form-item-wrapper-with-affix]')
        .once('prefix-suffix');

      $prefixSuffixElements.map(
        (index, element) => new Drupal.PrefixSuffix(element),
      );
    },
  };
})(jQuery, Drupal, Drupal.debounce);
