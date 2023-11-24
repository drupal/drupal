/**
 * @file
 * Attaches the behaviors for the Layout Builder module.
 */

(($, Drupal, Sortable) => {
  const { ajax, behaviors, debounce, announce, formatPlural } = Drupal;

  /*
   * Boolean that tracks if block listing is currently being filtered. Declared
   * outside of behaviors so value is retained on rebuild.
   */
  let layoutBuilderBlocksFiltered = false;

  /**
   * Provides the ability to filter the block listing in "Add block" dialog.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach block filtering behavior to "Add block" dialog.
   */
  behaviors.layoutBuilderBlockFilter = {
    attach(context) {
      const $categories = $('.js-layout-builder-categories', context);
      const $filterLinks = $categories.find('.js-layout-builder-block-link');

      /**
       * Filters the block list.
       *
       * @param {jQuery.Event} e
       *   The jQuery event for the keyup event that triggered the filter.
       */
      const filterBlockList = (e) => {
        const query = e.target.value.toLowerCase();

        /**
         * Shows or hides the block entry based on the query.
         *
         * @param {number} index
         *   The index in the loop, as provided by `jQuery.each`
         * @param {HTMLElement} link
         *   The link to add the block.
         */
        const toggleBlockEntry = (index, link) => {
          const $link = $(link);
          const textMatch =
            link.textContent.toLowerCase().indexOf(query) !== -1;
          // Checks if a category is currently hidden.
          // Toggles the category on if so.
          if (
            Drupal.elementIsHidden(
              $link.closest('.js-layout-builder-category')[0],
            )
          ) {
            $link.closest('.js-layout-builder-category').show();
          }
          // Toggle the li tag of the matching link.
          $link.parent().toggle(textMatch);
        };

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          // Attribute to note which categories are closed before opening all.
          $categories
            .find('.js-layout-builder-category:not([open])')
            .attr('remember-closed', '');

          // Open all categories so every block is available to filtering.
          $categories.find('.js-layout-builder-category').attr('open', '');
          // Toggle visibility of links based on query.
          $filterLinks.each(toggleBlockEntry);

          // Only display categories containing visible links.
          $categories
            .find(
              '.js-layout-builder-category:not(:has(.js-layout-builder-block-link:visible))',
            )
            .hide();

          announce(
            formatPlural(
              $categories.find('.js-layout-builder-block-link:visible').length,
              '1 block is available in the modified list.',
              '@count blocks are available in the modified list.',
            ),
          );
          layoutBuilderBlocksFiltered = true;
        } else if (layoutBuilderBlocksFiltered) {
          layoutBuilderBlocksFiltered = false;
          // Remove "open" attr from categories that were closed pre-filtering.
          $categories
            .find('.js-layout-builder-category[remember-closed]')
            .removeAttr('open')
            .removeAttr('remember-closed');
          // Show all categories since filter is turned off.
          $categories.find('.js-layout-builder-category').show();
          // Show all li tags since filter is turned off.
          $filterLinks.parent().show();
          announce(Drupal.t('All available blocks are listed.'));
        }
      };

      $(
        once('block-filter-text', 'input.js-layout-builder-filter', context),
      ).on('input', debounce(filterBlockList, 200));
    },
  };

  /**
   * Callback used in {@link Drupal.behaviors.layoutBuilderBlockDrag}.
   *
   * @param {HTMLElement} item
   *   The HTML element representing the repositioned block.
   * @param {HTMLElement} from
   *   The HTML element representing the previous parent of item
   * @param {HTMLElement} to
   *   The HTML element representing the current parent of item
   *
   * @internal This method is a callback for layoutBuilderBlockDrag and is used
   *  in FunctionalJavascript tests. It may be renamed if the test changes.
   *  @see https://www.drupal.org/node/3084730
   */
  Drupal.layoutBuilderBlockUpdate = function (item, from, to) {
    const $item = $(item);
    const $from = $(from);

    // Check if the region from the event and region for the item match.
    const itemRegion = $item.closest('.js-layout-builder-region');
    if (to === itemRegion[0]) {
      // Find the destination delta.
      const deltaTo = $item.closest('[data-layout-delta]').data('layout-delta');
      // If the block didn't leave the original delta use the destination.
      const deltaFrom = $from
        ? $from.closest('[data-layout-delta]').data('layout-delta')
        : deltaTo;
      ajax({
        url: [
          $item.closest('[data-layout-update-url]').data('layout-update-url'),
          deltaFrom,
          deltaTo,
          itemRegion.data('region'),
          $item.data('layout-block-uuid'),
          $item.prev('[data-layout-block-uuid]').data('layout-block-uuid'),
        ]
          .filter((element) => element !== undefined)
          .join('/'),
      }).execute();
    }
  };

  /**
   * Provides the ability to drag blocks to new positions in the layout.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach block drag behavior to the Layout Builder UI.
   */
  behaviors.layoutBuilderBlockDrag = {
    attach(context) {
      const regionSelector = '.js-layout-builder-region';
      Array.prototype.forEach.call(
        context.querySelectorAll(regionSelector),
        (region) => {
          Sortable.create(region, {
            draggable: '.js-layout-builder-block',
            ghostClass: 'ui-state-drop',
            group: 'builder-region',
            onEnd: (event) =>
              Drupal.layoutBuilderBlockUpdate(event.item, event.from, event.to),
          });
        },
      );
    },
  };

  /**
   * Disables interactive elements in previewed blocks.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach disabling interactive elements behavior to the Layout Builder UI.
   */
  behaviors.layoutBuilderDisableInteractiveElements = {
    attach() {
      // Disable interactive elements inside preview blocks.
      const $blocks = $('#layout-builder [data-layout-block-uuid]');
      $blocks.find('input, textarea, select').prop('disabled', true);
      $blocks
        .find('a')
        // Don't disable contextual links.
        // @see \Drupal\contextual\Element\ContextualLinksPlaceholder
        .not(
          (index, element) =>
            $(element).closest('[data-contextual-id]').length > 0,
        )
        .on('click mouseup touchstart', (e) => {
          e.preventDefault();
          e.stopPropagation();
        });

      /*
       * In preview blocks, remove from the tabbing order all input elements
       * and elements specifically assigned a tab index, other than those
       * related to contextual links.
       */
      $blocks
        .find(
          'button, [href], input, select, textarea, iframe, [tabindex]:not([tabindex="-1"]):not(.tabbable)',
        )
        .not(
          (index, element) =>
            $(element).closest('[data-contextual-id]').length > 0,
        )
        .attr('tabindex', -1);
    },
  };

  // After a dialog opens, highlight element that the dialog is acting on.
  $(window).on('dialog:aftercreate', (event, dialog, $element) => {
    if (Drupal.offCanvas.isOffCanvas($element)) {
      // Start by removing any existing highlighted elements.
      $('.is-layout-builder-highlighted').removeClass(
        'is-layout-builder-highlighted',
      );

      /*
       * Every dialog has a single 'data-layout-builder-target-highlight-id'
       * attribute. Every dialog-opening element has a unique
       * 'data-layout-builder-highlight-id' attribute.
       *
       * When the value of data-layout-builder-target-highlight-id matches
       * an element's value of data-layout-builder-highlight-id, the class
       * 'is-layout-builder-highlighted' is added to element.
       */
      const id = $element
        .find('[data-layout-builder-target-highlight-id]')
        .attr('data-layout-builder-target-highlight-id');
      if (id) {
        $(`[data-layout-builder-highlight-id="${id}"]`).addClass(
          'is-layout-builder-highlighted',
        );
      }

      // Remove wrapper class added by move block form.
      $('#layout-builder').removeClass('layout-builder--move-blocks-active');

      /**
       * If dialog has a data-add-layout-builder-wrapper attribute, get the
       * value and add it as a class to the Layout Builder UI wrapper.
       *
       * Currently, only the move block form uses
       * data-add-layout-builder-wrapper, but any dialog can use this attribute
       * to add a class to the Layout Builder UI while opened.
       */
      const layoutBuilderWrapperValue = $element
        .find('[data-add-layout-builder-wrapper]')
        .attr('data-add-layout-builder-wrapper');
      if (layoutBuilderWrapperValue) {
        $('#layout-builder').addClass(layoutBuilderWrapperValue);
      }
    }
  });

  /*
   * When a Layout Builder dialog is triggered, the main canvas resizes. After
   * the resize transition is complete, see if the target element is still
   * visible in viewport. If not, scroll page so the target element is again
   * visible.
   *
   * @todo Replace this custom solution when a general solution is made
   *   available with https://www.drupal.org/node/3033410
   */
  if (document.querySelector('[data-off-canvas-main-canvas]')) {
    const mainCanvas = document.querySelector('[data-off-canvas-main-canvas]');

    // This event fires when canvas CSS transitions are complete.
    mainCanvas.addEventListener('transitionend', () => {
      const $target = $('.is-layout-builder-highlighted');

      if ($target.length > 0) {
        // These four variables are used to determine if the element is in the
        // viewport.
        const targetTop = $target.offset().top;
        const targetBottom = targetTop + $target.outerHeight();
        const viewportTop = $(window).scrollTop();
        const viewportBottom = viewportTop + $(window).height();

        // If the element is not in the viewport, scroll it into view.
        if (targetBottom < viewportTop || targetTop > viewportBottom) {
          const viewportMiddle = (viewportBottom + viewportTop) / 2;
          const scrollAmount = targetTop - viewportMiddle;

          // Check whether the browser supports scrollBy(options). If it does
          // not, use scrollBy(x-coord, y-coord) instead.
          if ('scrollBehavior' in document.documentElement.style) {
            window.scrollBy({
              top: scrollAmount,
              left: 0,
              behavior: 'smooth',
            });
          } else {
            window.scrollBy(0, scrollAmount);
          }
        }
      }
    });
  }

  $(window).on('dialog:afterclose', (event, dialog, $element) => {
    if (Drupal.offCanvas.isOffCanvas($element)) {
      // Remove the highlight from all elements.
      $('.is-layout-builder-highlighted').removeClass(
        'is-layout-builder-highlighted',
      );

      // Remove wrapper class added by move block form.
      $('#layout-builder').removeClass('layout-builder--move-blocks-active');
    }
  });

  /**
   * Toggles content preview in the Layout Builder UI.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach content preview toggle to the Layout Builder UI.
   */
  behaviors.layoutBuilderToggleContentPreview = {
    attach(context) {
      const $layoutBuilder = $('#layout-builder');

      // The content preview toggle.
      const $layoutBuilderContentPreview = $('#layout-builder-content-preview');

      // data-content-preview-id specifies the layout being edited.
      const contentPreviewId =
        $layoutBuilderContentPreview.data('content-preview-id');

      /**
       * Tracks if content preview is enabled for this layout. Defaults to true
       * if no value has previously been set.
       */
      const isContentPreview =
        JSON.parse(localStorage.getItem(contentPreviewId)) !== false;

      /**
       * Disables content preview in the Layout Builder UI.
       *
       * Disabling content preview hides block content. It is replaced with the
       * value of the block's data-layout-content-preview-placeholder-label
       * attribute.
       *
       * @todo Revisit in https://www.drupal.org/node/3043215, it may be
       *   possible to remove all but the first line of this function.
       */
      const disableContentPreview = () => {
        $layoutBuilder.addClass('layout-builder--content-preview-disabled');

        /**
         * Iterate over all Layout Builder blocks to hide their content and add
         * placeholder labels.
         */
        $('[data-layout-content-preview-placeholder-label]', context).each(
          (i, element) => {
            const $element = $(element);

            // Hide everything in block that isn't contextual link related.
            $element.children(':not([data-contextual-id])').hide(0);

            const contentPreviewPlaceholderText = $element.attr(
              'data-layout-content-preview-placeholder-label',
            );

            const contentPreviewPlaceholderLabel = Drupal.theme(
              'layoutBuilderPrependContentPreviewPlaceholderLabel',
              contentPreviewPlaceholderText,
            );
            $element.prepend(contentPreviewPlaceholderLabel);
          },
        );
      };

      /**
       * Enables content preview in the Layout Builder UI.
       *
       * When content preview is enabled, the Layout Builder UI returns to its
       * default experience. This is accomplished by removing placeholder
       * labels and un-hiding block content.
       *
       * @todo Revisit in https://www.drupal.org/node/3043215, it may be
       *   possible to remove all but the first line of this function.
       */
      const enableContentPreview = () => {
        $layoutBuilder.removeClass('layout-builder--content-preview-disabled');

        // Remove all placeholder labels.
        $('.js-layout-builder-content-preview-placeholder-label').remove();

        // Iterate over all blocks.
        $('[data-layout-content-preview-placeholder-label]').each(
          (i, element) => {
            $(element).children().show();
          },
        );
      };

      $('#layout-builder-content-preview', context).on('change', (event) => {
        const isChecked = event.currentTarget.checked;

        localStorage.setItem(contentPreviewId, JSON.stringify(isChecked));

        if (isChecked) {
          enableContentPreview();
          announce(
            Drupal.t('Block previews are visible. Block labels are hidden.'),
          );
        } else {
          disableContentPreview();
          announce(
            Drupal.t('Block previews are hidden. Block labels are visible.'),
          );
        }
      });

      /**
       * On rebuild, see if content preview has been set to disabled. If yes,
       * disable content preview in the Layout Builder UI.
       */
      if (!isContentPreview) {
        $layoutBuilderContentPreview.attr('checked', false);
        disableContentPreview();
      }
    },
  };

  /**
   * Creates content preview placeholder label markup.
   *
   * @param {string} contentPreviewPlaceholderText
   *   The text content of the placeholder label
   *
   * @return {string}
   *   A HTML string of the placeholder label.
   */
  Drupal.theme.layoutBuilderPrependContentPreviewPlaceholderLabel = (
    contentPreviewPlaceholderText,
  ) => {
    const contentPreviewPlaceholderLabel = document.createElement('div');
    contentPreviewPlaceholderLabel.className =
      'layout-builder-block__content-preview-placeholder-label js-layout-builder-content-preview-placeholder-label';
    contentPreviewPlaceholderLabel.innerHTML = contentPreviewPlaceholderText;

    return `<div class="layout-builder-block__content-preview-placeholder-label js-layout-builder-content-preview-placeholder-label">${contentPreviewPlaceholderText}</div>`;
  };

  // Remove all contextual links outside the layout.
  $(window).on('drupalContextualLinkAdded', (event, data) => {
    const element = data.$el;
    const contextualId = element.attr('data-contextual-id');
    if (contextualId && !contextualId.startsWith('layout_builder_block:')) {
      element.remove();
    }
  });
})(jQuery, Drupal, Sortable);
