/**
 * BugGallery Admin Scripts
 *
 * Handles the sortable related links repeater in the bug_photo edit screen.
 * Uses jQuery UI Sortable for drag-and-drop reordering.
 */
(function ($) {
    'use strict';

    const RelatedLinks = {
        container: null,
        template: null,
        nextIndex: 0,

        init() {
            this.container = $('#buggallery-related-links-container');
            this.template = $('#buggallery-link-row-template').html();

            if (!this.container.length) {
                return;
            }

            // Calculate next index from existing rows
            this.nextIndex = this.container.find('.buggallery-link-row').length;

            // Initialize sortable
            this.container.sortable({
                handle: '.buggallery-drag-handle',
                placeholder: 'buggallery-link-placeholder',
                opacity: 0.7,
                update: () => this.reindex(),
            });

            // Add link button
            $('#buggallery-add-link').on('click', () => this.addRow());

            // Remove link button (delegated)
            this.container.on('click', '.buggallery-remove-link', function () {
                $(this).closest('.buggallery-link-row').fadeOut(200, function () {
                    $(this).remove();
                    RelatedLinks.reindex();
                });
            });

            // Auto-detect internal links
            this.container.on('change', '.buggallery-link-url', function () {
                const url = $(this).val();
                const row = $(this).closest('.buggallery-link-row');
                const typeSelect = row.find('.buggallery-link-type');

                // Check if URL matches our site
                if (url && url.indexOf(window.location.origin) === 0) {
                    typeSelect.val('internal');
                }
            });
        },

        addRow() {
            const html = this.template.replace(/\{\{INDEX\}\}/g, this.nextIndex);
            this.container.append(html);
            this.nextIndex++;

            // Focus the title input of the new row
            this.container
                .find('.buggallery-link-row:last .buggallery-link-title')
                .focus();
        },

        reindex() {
            this.container.find('.buggallery-link-row').each(function (i) {
                const row = $(this);
                row.attr('data-index', i);
                row.find('input, select').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr(
                            'name',
                            name.replace(/buggallery_links\[\d+\]/, 'buggallery_links[' + i + ']')
                        );
                    }
                });
            });
        },
    };

    $(document).ready(function () {
        RelatedLinks.init();
    });
})(jQuery);
