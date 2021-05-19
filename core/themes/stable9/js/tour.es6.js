/**
 * @file
 * Provides backwards compatibility for Tours that no longer use Joyride.
 */

((Drupal) => {
  /**
   * Converts the markup of a Shepherd tour tip to match Joyride.
   *
   * @param {Tour} shepherdTour
   *   A ShepherdJS tour object.
   *
   * @internal
   */
  Drupal.tour.convertToJoyrideMarkup = (shepherdTour) => {
    /**
     * Changes the tag of an element.
     *
     * @param {HTMLElement} element
     *  The element that will have its tag changed.
     * @param {string} tag
     *  The tag the element should be changed to.
     */
    const changeTag = (element, tag) => {
      if (element) {
        const newTagElement = document.createElement(tag);
        [...element.attributes].forEach((attr) => {
          newTagElement.setAttribute(attr.name, attr.value);
        });
        newTagElement.innerHTML = element.innerHTML;
        element.parentNode.replaceChild(newTagElement, element);
      }
    };

    // Create variables for the elements that will be rearranged.
    const shepherdElement = shepherdTour.currentStep.el;
    const shepherdContent = shepherdElement.querySelector('.shepherd-content');
    const shepherdCancel = shepherdElement.querySelector(
      '.shepherd-cancel-icon',
    );
    const shepherdTitle = shepherdElement.querySelector('.shepherd-title');
    const shepherdText = shepherdElement.querySelector('.shepherd-text');
    const shepherdNext = shepherdElement.querySelector('footer .button');
    const tourProgress = shepherdElement.querySelector('.tour-progress');

    // Add attributes to the elements so they match what they were when Joyride
    // was providing Tour functionality.
    shepherdElement.classList.add('joyride-tip-guide');
    shepherdContent.classList.add('joyride-content-wrapper');
    shepherdNext.classList.add('joyride-next-tip');
    shepherdNext.setAttribute('href', '#');
    shepherdNext.setAttribute('role', 'button');
    shepherdNext.removeAttribute('type');
    shepherdCancel.classList.add('joyride-close-tip');
    shepherdCancel.removeAttribute('type');
    shepherdCancel.setAttribute('href', '#');
    shepherdCancel.setAttribute('role', 'button');
    shepherdElement.setAttribute(
      'data-index',
      shepherdTour.currentStep.options.index,
    );
    shepherdElement.querySelector('footer').remove();

    // If the class list includes `tip-uses-get-output`, then the tip was created
    // by a deprecated tip plugin. This means the markup has some differences
    // that require some different steps to rebuild it as Joyride BC markup.
    // @todo remove the contents of the 'if' in this conditional in
    //   https://drupal.org/node/3195193.
    if (shepherdElement.classList.contains('tip-uses-get-output')) {
      // Move the next button.
      shepherdText.appendChild(shepherdNext);

      // Move the cancel button and remove the now unnecessary header.
      shepherdText.appendChild(shepherdCancel);
      shepherdContent.querySelector('.shepherd-header').remove();

      // Remove empty paragraphs from the text container markup.
      Array.from(shepherdText.children).forEach((node) => {
        if (
          node.tagName === 'P' &&
          node.textContent === '' &&
          node.classList.length === 0
        ) {
          node.remove();
        }
      });

      // Move the contents of shepherdText directly into shepherdContent to
      // remove the now redundant `<p>` provided by TipPlugin. Shepherd already
      // wraps its content in a `<p>`, so the Plugin provided tag is redundant.
      shepherdContent.innerHTML = shepherdText.innerHTML;
    } else {
      // Rearrange elements so their structure matches Joyride's.
      shepherdContent.insertBefore(shepherdTitle, shepherdContent.firstChild);
      shepherdContent.insertBefore(tourProgress, shepherdText.nextSibling);
      shepherdContent.appendChild(shepherdCancel);
      shepherdContent.querySelector('.shepherd-header').remove();
      shepherdContent.insertBefore(shepherdNext, tourProgress.nextSibling);
      shepherdCancel.innerHTML = '<span aria-hidden="true">×</span>';
      shepherdTitle.classList.add('tour-tip-label');

      // Convert elements to use the tags they used in Joyride.
      changeTag(shepherdTitle, 'h2');

      // Remove the wrapper Shepherd adds for tip content.
      shepherdText.outerHTML = shepherdText.innerHTML;
    }

    // Convert the next and cancel buttons to links so they match Joyride's
    // markup. They must be re-queried as they were potentially moved elsewhere
    // in the DOM.
    changeTag(shepherdElement.querySelector('.joyride-close-tip'), 'a');
    changeTag(shepherdElement.querySelector('.joyride-next-tip'), 'a');

    // The arrow protruding from a tip pointing to the element it references.
    const shepherdArrow = shepherdElement.querySelector('.shepherd-arrow');

    if (shepherdArrow) {
      shepherdArrow.classList.add('joyride-nub');

      if (shepherdTour.currentStep.options.attachTo.on) {
        // Shepherd's positions are opposite of Joyride's as they specify the
        // tip location relative to the corresponding element as opposed to
        // their location on the tip itself.
        const stepToTipPosition = {
          bottom: 'top',
          top: 'bottom',
          left: 'right',
          right: 'left',
        };
        shepherdArrow.classList.add(
          // Split at '-' as shepherd positioning accommodates dash-delimited
          // secondary axis positioning.
          // shepherdTour.currentStep.options.attachTo.on.split('-')[0]
          stepToTipPosition[
            // Split at '-' as shepherd positioning accommodates dash-delimited
            // secondary axis positioning.
            shepherdTour.currentStep.options.attachTo.on.split('-')[0]
          ],
        );
      }
      changeTag(shepherdArrow, 'span');
    } else {
      // If there is no Shepherd arrow, there still needs to be markup for a
      // non-displayed nub to match Joyride's markup.
      const nub = document.createElement('span');
      nub.classList.add('joyride-nub');
      nub.setAttribute('style', 'display: none;');
      shepherdElement.insertBefore(nub, shepherdElement.firstChild);
    }

    // When the next and cancel buttons were converted to links, they became
    // new DOM elements that no longer have their associated event listeners.
    // The events must be reintroduced here.
    shepherdElement
      .querySelector('.joyride-next-tip')
      .addEventListener('click', (e) => {
        e.preventDefault();
        shepherdTour.next();
      });
    shepherdElement
      .querySelector('.joyride-close-tip')
      .addEventListener('click', (e) => {
        e.preventDefault();
        shepherdTour.cancel();
      });
    shepherdElement.querySelector('.joyride-next-tip').focus();
  };
})(Drupal);
