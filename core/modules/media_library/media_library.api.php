<?php

/**
 * @file
 * Documentation related to Media Library.
 */

/**
 * @defgroup media_library_architecture Media Library Architecture
 * @{
 *
 * Media Library is a UI for the core Media module. It provides a visual
 * interface for users to manage media in their site, and it allows authors to
 * visually select media for use in entity reference and text fields, using a
 * modal dialog.
 *
 * In order to provide a consistent user experience, Media Library is
 * intentionally opinionated, with few extension points and no hooks. Most of
 * its code is internal and should not be extended or instantiated by external
 * code.
 *
 * @section openers Openers
 * Interaction with the modal media library dialog is mediated by "opener"
 * services. All openers must implement
 * \Drupal\media_library\MediaLibraryOpenerInterface.
 *
 * Openers are responsible for determining access to the media library, and for
 * generating an AJAX response when the user has finished selecting media items
 * in the library. An opener is a "bridge" between the opinionated media library
 * modal dialog and whatever is consuming it, allowing the dialog to be
 * triggered in a way that makes sense for that particular consumer. Examples in
 * Drupal core include entity reference fields and text editors.
 *
 * @see \Drupal\media_library\MediaLibraryOpenerInterface
 * @see \Drupal\media_library\MediaLibraryEditorOpener
 * @see \Drupal\media_library\MediaLibraryFieldWidgetOpener
 *
 * @section state Modal dialog state
 * When the media library modal is used, its configuration and state (such as
 * how many items are currently selected, the maximum number that can be
 * selected, which media types the user is allowed to see, and so forth) are
 * stored in an instance of \Drupal\media_library\MediaLibraryState. The state
 * object also stores the service ID of the opener being used, as well as any
 * additional parameters or data that are specific to that opener.
 *
 * The media library state is passed between the user and the server in the
 * URL's query parameters. Therefore, the state is also protected by a hash in
 * order to prevent tampering.
 *
 * @see \Drupal\media_library\MediaLibraryState
 *
 * @section add_form Adding media in the dialog
 * Users with appropriate permissions can add media to the library from directly
 * within the modal dialog.
 *
 * This interaction is implemented using forms, and is customizable by modules.
 * Since the media library is segmented by media type, each media type can
 * expose a different form for adding media of that type; the type's source
 * plugin specifies the actual form class to use. Here is an example of a media
 * source plugin definition which provides an add form for the media library:
 *
 * @code
 * #[MediaSource(
 *   id: "file",
 *   label: new TranslatableMarkup("File"),
 *   description: new TranslatableMarkup("Use local files for reusable media."),
 *   allowed_field_types: ["file"],
 *   forms = [
 *     "media_library_add" => "\Drupal\media_library\Form\FileUploadForm",
 *   ]
 * )]
 * @endcode
 *
 * This can also be done in hook_media_source_info_alter(). For example:
 *
 * @code
 * function example_media_source_info_alter(array &$sources) {
 *   $sources['file']['forms']['media_library_add'] = "\Drupal\media_library\Form\FileUploadForm";
 * }
 * @endcode
 *
 * The add form is a standard form class, and can be altered by modules and
 * themes just like any other form. For easier implementation, it is recommended
 * that modules extend \Drupal\media_library\Form\AddFormBase when providing add
 * forms.
 *
 * @see \Drupal\media_library\Form\AddFormBase
 * @see \Drupal\media_library\Form\FileUploadForm
 * @see \Drupal\media_library\Form\OEmbedForm
 *
 * @}
 */
