/**
 * CC Fields - Admin JavaScript
 * Handles section management, sorting, repeaters, and media uploads
 */
(function($) {
    'use strict';

    var CCFields = {

        init: function() {
            this.sectionIndex = $('.ccf-section-panel').length;
            this.bindEvents();
            this.initSortable();
            this.initColorPickers();
        },

        bindEvents: function() {
            var self = this;

            // Toggle section picker
            $('#ccf-add-section-btn').on('click', function() {
                $('#ccf-section-picker').slideToggle(200);
            });

            // Add new section
            $(document).on('click', '.ccf-section-option', function() {
                var type = $(this).data('section');
                self.addSection(type);
                $('#ccf-section-picker').slideUp(200);
            });

            // Toggle collapse
            $(document).on('click', '.ccf-btn-toggle, .ccf-section-title, .ccf-section-icon-sm', function(e) {
                e.stopPropagation();
                $(this).closest('.ccf-section-panel').toggleClass('collapsed');
            });

            // Delete section
            $(document).on('click', '.ccf-btn-delete', function(e) {
                e.stopPropagation();
                if (confirm('Remove this section?')) {
                    $(this).closest('.ccf-section-panel').slideUp(200, function() {
                        $(this).remove();
                        self.reindex();
                    });
                }
            });

            // Duplicate section
            $(document).on('click', '.ccf-btn-duplicate', function(e) {
                e.stopPropagation();
                var panel = $(this).closest('.ccf-section-panel');
                var clone = panel.clone();
                clone.insertAfter(panel);
                self.reindex();
                // Scroll to new panel
                $('html, body').animate({ scrollTop: clone.offset().top - 100 }, 300);
            });

            // Image upload
            $(document).on('click', '.mgf-upload-image', function(e) {
                e.preventDefault();
                var container = $(this).closest('.ccf-image-field');
                self.openMediaUploader(container);
            });

            // Remove image
            $(document).on('click', '.mgf-remove-image', function(e) {
                e.preventDefault();
                var container = $(this).closest('.ccf-image-field');
                container.find('.ccf-image-id').val('');
                container.find('.ccf-image-preview').html('');
                $(this).hide();
            });

            // Add repeater item
            $(document).on('click', '.mgf-add-repeater-item', function() {
                self.addRepeaterItem($(this).closest('.ccf-repeater'));
            });

            // Delete repeater item
            $(document).on('click', '.ccf-btn-delete-item', function() {
                var repeater = $(this).closest('.ccf-repeater');
                $(this).closest('.ccf-repeater-item').slideUp(200, function() {
                    $(this).remove();
                    self.reindexRepeater(repeater);
                });
            });

            // Icon select
            $(document).on('click', '.ccf-icon-option', function() {
                var grid = $(this).closest('.ccf-icon-select');
                grid.find('.ccf-icon-option').removeClass('selected');
                $(this).addClass('selected');
                grid.find('.ccf-icon-value').val($(this).data('icon'));
            });
        },

        initSortable: function() {
            var self = this;

            // Main sections sortable
            $('#ccf-sections-list').sortable({
                handle: '.ccf-drag-handle',
                placeholder: 'ui-sortable-placeholder',
                tolerance: 'pointer',
                opacity: 0.8,
                update: function() {
                    self.reindex();
                }
            });

            // Repeater items sortable
            this.initRepeaterSortable();
        },

        initRepeaterSortable: function() {
            var self = this;
            $('.ccf-repeater-items').sortable({
                handle: '.ccf-drag-handle',
                placeholder: 'ui-sortable-placeholder',
                tolerance: 'pointer',
                opacity: 0.8,
                update: function() {
                    self.reindexRepeater($(this).closest('.ccf-repeater'));
                }
            });
        },

        initColorPickers: function() {
            $('.ccf-color-picker').wpColorPicker();
        },

        addSection: function(type) {
            var self = this;
            $.ajax({
                url: ccfAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'ccf_get_section_fields',
                    nonce: ccfAdmin.nonce,
                    section_type: type,
                    index: self.sectionIndex
                },
                success: function(response) {
                    if (response.success) {
                        var $panel = $(response.data.html);
                        $('#ccf-sections-list').append($panel);
                        self.sectionIndex++;
                        self.initRepeaterSortable();
                        self.initColorPickers();

                        // Scroll to new section
                        $('html, body').animate({
                            scrollTop: $panel.offset().top - 100
                        }, 300);
                    }
                }
            });
        },

        reindex: function() {
            $('#ccf-sections-list .ccf-section-panel').each(function(i) {
                $(this).attr('data-index', i);
                // Update all input names
                $(this).find('[name]').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/ccf_sections\[\d+\]/, 'ccf_sections[' + i + ']');
                        $(this).attr('name', name);
                    }
                });
            });
        },

        reindexRepeater: function(repeater) {
            repeater.find('.ccf-repeater-item').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('.ccf-repeater-item-title').text('Item ' + (i + 1));
            });
        },

        addRepeaterItem: function(repeater) {
            var self = this;
            var fieldsData = repeater.data('fields');
            var items = repeater.find('.ccf-repeater-items');
            var index = items.find('.ccf-repeater-item').length;

            // Find the name prefix from existing items or construct it
            var namePrefix = '';
            var existingItem = items.find('.ccf-repeater-item:first [name]').first();
            if (existingItem.length) {
                var existingName = existingItem.attr('name');
                // Get prefix up to the repeater level
                namePrefix = existingName.replace(/\[\d+\]\[[\w]+\]$/, '') + '[' + index + ']';
            } else {
                // Build from the section context
                var panel = repeater.closest('.ccf-section-panel');
                var sectionIndex = panel.attr('data-index');
                var fieldContainer = repeater.closest('.ccf-field');
                // Try to find the field name from the repeater's parent
                var repeaterField = repeater.closest('.ccf-field');
                var label = repeaterField.find('> label').text().toLowerCase().replace(/[^a-z]/g, '_').replace(/_+/g, '_');
                // Use the data-fields attribute to find the repeater name
                var allFields = panel.find('.ccf-field');
                var fieldName = '';
                panel.find('.ccf-repeater').each(function() {
                    if (this === repeater[0]) {
                        // Get the hidden input or find the field name
                        var prevLabel = $(this).closest('.ccf-field').find('> label').text();
                        // Look for matching field in section config
                        var sectionType = panel.attr('data-type');
                        if (ccfAdmin.sections[sectionType]) {
                            ccfAdmin.sections[sectionType].fields.forEach(function(f) {
                                if (f.label === prevLabel && f.type === 'repeater') {
                                    fieldName = f.name;
                                }
                            });
                        }
                    }
                });
                namePrefix = 'ccf_sections[' + sectionIndex + '][data][' + fieldName + '][' + index + ']';
            }

            var html = '<div class="ccf-repeater-item" data-index="' + index + '">';
            html += '<div class="ccf-repeater-item-header">';
            html += '<span class="ccf-drag-handle">&#9776;</span>';
            html += '<span class="ccf-repeater-item-title">Item ' + (index + 1) + '</span>';
            html += '<button type="button" class="ccf-btn-delete-item">&times;</button>';
            html += '</div>';
            html += '<div class="ccf-repeater-item-body">';

            if (fieldsData && fieldsData.length) {
                fieldsData.forEach(function(field) {
                    var fieldId = 'ccf_' + Math.random().toString(36).substr(2, 9);
                    html += '<div class="ccf-field ccf-field-' + field.type + '">';
                    if (field.label) {
                        html += '<label for="' + fieldId + '">' + self.escHtml(field.label) + '</label>';
                    }

                    switch (field.type) {
                        case 'text':
                            html += '<input type="text" id="' + fieldId + '" name="' + namePrefix + '[' + field.name + ']" value="" class="widefat">';
                            break;
                        case 'textarea':
                            html += '<textarea id="' + fieldId + '" name="' + namePrefix + '[' + field.name + ']" rows="4" class="widefat"></textarea>';
                            break;
                        case 'url':
                            html += '<input type="url" id="' + fieldId + '" name="' + namePrefix + '[' + field.name + ']" value="" class="widefat">';
                            break;
                        case 'number':
                            html += '<input type="number" id="' + fieldId + '" name="' + namePrefix + '[' + field.name + ']" value="" class="small-text">';
                            break;
                        case 'image':
                            html += '<div class="ccf-image-field">';
                            html += '<input type="hidden" name="' + namePrefix + '[' + field.name + ']" value="" class="ccf-image-id">';
                            html += '<div class="ccf-image-preview"></div>';
                            html += '<button type="button" class="button mgf-upload-image">Choose Image</button>';
                            html += '<button type="button" class="button mgf-remove-image" style="display:none">Remove</button>';
                            html += '</div>';
                            break;
                    }
                    html += '</div>';
                });
            }

            html += '</div></div>';

            items.append(html);
            self.initRepeaterSortable();
        },

        openMediaUploader: function(container) {
            var frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use Image' },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                container.find('.ccf-image-id').val(attachment.id);
                var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                container.find('.ccf-image-preview').html('<img src="' + url + '">');
                container.find('.mgf-remove-image').show();
            });

            frame.open();
        },

        escHtml: function(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        CCFields.init();
    });

})(jQuery);
