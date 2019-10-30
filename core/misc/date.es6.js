/**
 * @file
 * Polyfill for HTML5 date input.
 */

(function($, Modernizr, Drupal) {
  /**
   * Attach datepicker fallback on date elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior. Adds a class that hides formatting instructions
   *   on date/time fields when the browser supports a native datepicker.
   */
  Drupal.behaviors.date = {
    attach(context, settings) {
      const dataFieldElements = 'data-drupal-field-elements';
      const dataDatepickerProcessed = 'data-datepicker-is-processed';

      /**
       * Returns a CSS selector for a date field to process.
       *
       * The dataDatepickerProcessed attribute prevents a field from being
       * selected and processed more than once.
       *
       * @param {string} elements
       *   The data attribute value.
       *
       * @return {string}
       *   A CSS Selector.
       */
      const getDateSelector = elements =>
        [
          `[${dataFieldElements}="${elements}"]`,
          `:not([${dataDatepickerProcessed}="${elements}"])`,
        ].join('');

      // If the browser does not support a native datepicker, add date
      // formatting instructions on date/time fields.
      if (Modernizr.inputtypes.date === false) {
        Array.prototype.forEach.call(
          document.querySelectorAll(getDateSelector('date-time')),
          dateTime => {
            const dateInput = dateTime.querySelector('input[type="date"]');
            const timeInput = dateTime.querySelector('input[type="time"]');
            const help = Drupal.theme.dateTimeHelp({
              dateId: `${dateInput.id}--description`,
              dateDesc: dateInput.dataset.help,
              timeId: `${timeInput.id}--description`,
              timeDesc: timeInput.dataset.help,
            });

            [dateInput, timeInput].forEach(input => {
              input.setAttribute(
                'aria-describedby',
                `${input.id}--description`,
              );
              // If the browser does not support date or time inputs, the input
              // is treated as the type "text". The type attribute should be
              // changed to reflect this.
              input.setAttribute('type', 'text');
            });

            Drupal.DatepickerPolyfill.attachDescription(dateTime, help);

            // Set attribute to prevent element from being processed again.
            dateTime.setAttribute(dataDatepickerProcessed, 'date-time');
          },
        );

        Array.prototype.forEach.call(
          document.querySelectorAll(getDateSelector('date')),
          date => {
            const dateInput = date.querySelector('input[type="date"]');
            const help = Drupal.theme.dateHelp({
              dateDesc: dateInput.dataset.help,
            });

            // Date-only input will be described by description directly.
            const id = `${date.id}--description`;
            dateInput.setAttribute('aria-describedby', id);

            // If the browser does not support date inputs, the input is treated
            // as the type "text". The type attribute should be changed to
            // changed to reflect this.
            dateInput.setAttribute('type', 'text');
            Drupal.DatepickerPolyfill.attachDescription(date, help, id);

            // Set attribute to prevent element from selection on next run.
            date.setAttribute(dataDatepickerProcessed, 'date');
          },
        );
      }
    },
  };

  /**
   * Provides overridable utility functions for the datepicker polyfill.
   */
  Drupal.DatepickerPolyfill = class {
    /**
     * Adds help text to polyfilled date/time elements.
     *
     * The help text is added to existing description elements when present.
     * If a description element is not present, one is created.
     *
     * @param {HTMLElement} element
     *   The input element.
     * @param {string} help
     *   The help text.
     * @param {string} id
     *   The input id.
     */
    static attachDescription(element, help, id) {
      let description = element.nextElementSibling;

      // If no description element exists, create one.
      if (
        !(
          description &&
          description.getAttribute('data-drupal-field-elements') ===
            'description'
        )
      ) {
        description = Drupal.DatepickerPolyfill.descriptionWrapperElement(id);
        element.parentNode.insertBefore(description, element.nextSibling);
      }
      description.insertAdjacentHTML('beforeend', help);
    }

    /**
     * Creates a description wrapper element.
     *
     * @param {string} id
     *   The id of the input being described.
     *
     * @return {HTMLElement}
     *   The description wrapper DOM element.
     */
    static descriptionWrapperElement(id) {
      const description = document.createElement('div');
      description.classList.add('description');
      description.setAttribute('data-drupal-field-elements', 'description');
      if (id) {
        description.setAttribute('id', id);
      }
      return description;
    }
  };

  /**
   * Theme function for no-native-datepicker date input help text.
   *
   * @param {string} dateDesc
   *   The help text.
   *
   * @return {string}
   *   The HTML markup for the help text.
   */
  Drupal.theme.dateHelp = ({ dateDesc }) =>
    `<div class="no-native-datepicker-help">${dateDesc}</div>`;

  /**
   * Theme function for no-native-datepicker date+time inputs help text.
   *
   * @param {string} dateId
   *   The date input aria-describedby value.
   * @param {string} timeId
   *   The time input aria-describedby value.
   * @param {string} dateDesc
   *   The date help text.
   * @param {string} timeDesc
   *   The time help text.
   *
   * @return {string}
   *   The HTML markup for the help text.
   */
  Drupal.theme.dateTimeHelp = ({ dateId, timeId, dateDesc, timeDesc }) =>
    `<div class="no-native-datepicker-help">
       <span id="${dateId}">${dateDesc}</span> <span id="${timeId}">${timeDesc}</span>
     </div>`;
})(jQuery, Modernizr, Drupal);
