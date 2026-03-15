/**
 * BugGallery Photographer Dashboard Scripts
 * Handles front-end upload form interactions.
 */
(function () {
    'use strict';

    function setupRelatedLinksEditor() {
        var list = document.getElementById('buggallery-related-links-list');
        var addBtn = document.getElementById('buggallery-add-related-link');
        var template = document.getElementById('buggallery-related-link-template');

        if (!list || !addBtn || !template) {
            return;
        }

        var dragSource = null;

        function reindexRows() {
            var rows = list.querySelectorAll('.buggallery-related-link-row');
            rows.forEach(function (row, index) {
                row.dataset.index = String(index);

                var titleInput = row.querySelector('input[name*="[title]"]');
                var urlInput = row.querySelector('input[name*="[url]"]');
                var typeSelect = row.querySelector('select[name*="[type]"]');

                if (titleInput) {
                    titleInput.name = 'buggallery_links[' + index + '][title]';
                }
                if (urlInput) {
                    urlInput.name = 'buggallery_links[' + index + '][url]';
                }
                if (typeSelect) {
                    typeSelect.name = 'buggallery_links[' + index + '][type]';
                }
            });
        }

        function createRow(index) {
            var html = template.innerHTML.replace(/{{INDEX}}/g, String(index));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            var row = wrapper.firstElementChild;

            row.addEventListener('dragstart', function (event) {
                dragSource = row;
                row.classList.add('is-dragging');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', row.dataset.index || '');
                }
            });

            row.addEventListener('dragend', function () {
                row.classList.remove('is-dragging');
                list.querySelectorAll('.is-drop-target').forEach(function (el) {
                    el.classList.remove('is-drop-target');
                });
            });

            row.addEventListener('dragover', function (event) {
                event.preventDefault();
                row.classList.add('is-drop-target');
            });

            row.addEventListener('dragleave', function () {
                row.classList.remove('is-drop-target');
            });

            row.addEventListener('drop', function (event) {
                event.preventDefault();
                row.classList.remove('is-drop-target');

                if (!dragSource || dragSource === row) {
                    return;
                }

                var rowRect = row.getBoundingClientRect();
                var shouldInsertAfter = event.clientY > rowRect.top + rowRect.height / 2;

                if (shouldInsertAfter) {
                    row.after(dragSource);
                } else {
                    row.before(dragSource);
                }

                reindexRows();
            });

            var removeBtn = row.querySelector('.buggallery-remove-related-link');
            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    row.remove();
                    reindexRows();
                });
            }

            return row;
        }

        addBtn.addEventListener('click', function () {
            var index = list.querySelectorAll('.buggallery-related-link-row').length;
            var row = createRow(index);
            list.appendChild(row);
        });

        // Initialize with one empty row so photographers can start immediately.
        if (!list.querySelector('.buggallery-related-link-row')) {
            list.appendChild(createRow(0));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupRelatedLinksEditor();
    });
})();
