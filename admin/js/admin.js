/**
 * Simple LMS Admin JavaScript
 */
(function($) {
    'use strict';

    var SimpleLMSAdmin = {

        /**
         * Initialize.
         */
        init: function() {
            this.initRepeaters();
            this.initDurationCalculation();
            this.initPresetForm();
            this.initElementsSortable();
            this.initCourseActions();
        },

        /**
         * Initialize repeater fields.
         */
        initRepeaters: function() {
            var self = this;

            // Add new item.
            $(document).on('click', '.add-repeater-item', function() {
                var $button = $(this);
                var type = $button.data('type');
                var $repeater = $button.closest('.simple-lms-repeater');
                var $items = $repeater.find('.repeater-items');
                var template = $('#tmpl-simple-lms-' + type + '-row').html();
                var index = Date.now(); // Use timestamp for unique index.

                // Replace placeholder index.
                template = template.replace(/\{\{INDEX\}\}/g, index);

                // Apply default values.
                if (type === 'video') {
                    var defaultTitle = $repeater.data('default-title') || '';
                    if (defaultTitle) {
                        template = template.replace('value=""', 'value="' + self.escapeHtml(defaultTitle) + '"');
                    }
                } else if (type === 'material') {
                    var defaultLabel = $repeater.data('default-label') || '';
                    if (defaultLabel) {
                        template = template.replace('value=""', 'value="' + self.escapeHtml(defaultLabel) + '"');
                    }
                }

                $items.append(template);

                // Update item title on input change.
                self.updateItemTitle($items.find('.repeater-item').last());
            });

            // Remove item.
            $(document).on('click', '.remove-repeater-item', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).closest('.repeater-item').slideUp(200, function() {
                    $(this).remove();
                });
            });

            // Toggle item content.
            $(document).on('click', '.repeater-item-header', function(e) {
                if ($(e.target).hasClass('remove-repeater-item')) {
                    return;
                }
                $(this).next('.repeater-item-content').slideToggle(200);
            });

            // Update item title on input change.
            $(document).on('input', '.video-title-input, .material-label-input', function() {
                var $item = $(this).closest('.repeater-item');
                self.updateItemTitle($item);
            });

            // Make repeater items sortable.
            $('.repeater-items').sortable({
                handle: '.handle',
                placeholder: 'repeater-item-placeholder',
                opacity: 0.7
            });
        },

        /**
         * Update item title in header.
         */
        updateItemTitle: function($item) {
            var title = $item.find('.video-title-input, .material-label-input').first().val();
            var type = $item.hasClass('video-item') ? 'Video' : 'Material';
            $item.find('.item-title').text(title || type);
        },

        /**
         * Initialize duration calculation from time inputs.
         */
        initDurationCalculation: function() {
            var $startTime = $('#simple_lms_time_start');
            var $endTime = $('#simple_lms_time_end');
            var $duration = $('#simple_lms_duration');

            if (!$startTime.length || !$endTime.length || !$duration.length) {
                return;
            }

            var calculateDuration = function() {
                var start = $startTime.val();
                var end = $endTime.val();

                if (!start || !end) {
                    return;
                }

                var startParts = start.split(':');
                var endParts = end.split(':');

                var startMinutes = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);
                var endMinutes = parseInt(endParts[0]) * 60 + parseInt(endParts[1]);

                var diff = endMinutes - startMinutes;
                if (diff < 0) {
                    diff += 24 * 60; // Handle overnight.
                }

                var hours = Math.floor(diff / 60);
                var minutes = diff % 60;

                var durationText = '';
                if (hours > 0) {
                    durationText += hours + 'h';
                }
                if (minutes > 0) {
                    if (hours > 0) {
                        durationText += ' ';
                    }
                    durationText += minutes + 'min';
                }

                // Only update if user hasn't manually edited.
                if (!$duration.data('manual-edit')) {
                    $duration.val(durationText);
                }
            };

            $startTime.on('change', calculateDuration);
            $endTime.on('change', calculateDuration);

            // Track manual edits.
            $duration.on('input', function() {
                $(this).data('manual-edit', true);
            });
        },

        /**
         * Initialize preset form.
         */
        initPresetForm: function() {
            var self = this;
            var $form = $('#preset-form');

            if (!$form.length) {
                return;
            }

            // Handle form submission.
            $form.on('submit', function(e) {
                e.preventDefault();
                self.savePreset();
            });

            // Edit preset.
            $(document).on('click', '.edit-preset', function() {
                var presetName = $(this).data('preset');
                self.loadPreset(presetName);
            });

            // Delete preset.
            $(document).on('click', '.delete-preset', function() {
                var presetName = $(this).data('preset');
                if (confirm(simpleLMS.i18n.confirmDelete)) {
                    self.deletePreset(presetName);
                }
            });

            // Cancel edit.
            $('#cancel-edit').on('click', function() {
                self.resetForm();
            });
        },

        /**
         * Save preset via AJAX.
         */
        savePreset: function() {
            var $form = $('#preset-form');
            var $submit = $form.find('button[type="submit"]');
            var originalText = $submit.text();

            // Get checked elements in order.
            var elements = [];
            $('#elements-sortable li').each(function() {
                var $checkbox = $(this).find('input[type="checkbox"]');
                if ($checkbox.is(':checked')) {
                    elements.push($checkbox.val());
                }
            });

            var data = {
                action: 'simple_lms_save_shortcode_preset',
                nonce: simpleLMS.nonce,
                preset_name: $('#preset_name').val(),
                preset_label: $('#preset_label').val(),
                statuses: $form.find('input[name="statuses[]"]:checked').map(function() { return this.value; }).get(),
                categories: $form.find('input[name="categories[]"]:checked').map(function() { return this.value; }).get(),
                tags: $form.find('input[name="tags[]"]:checked').map(function() { return this.value; }).get(),
                orderby: $('#orderby').val(),
                order: $('#order').val(),
                limit: $('#limit').val(),
                columns: $('#columns').val(),
                elements: elements
            };

            $submit.text(simpleLMS.i18n.saving).prop('disabled', true);

            $.post(simpleLMS.ajaxUrl, data, function(response) {
                if (response.success) {
                    $submit.text(simpleLMS.i18n.saved);
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    alert(response.data.message || simpleLMS.i18n.error);
                    $submit.text(originalText).prop('disabled', false);
                }
            }).fail(function() {
                alert(simpleLMS.i18n.error);
                $submit.text(originalText).prop('disabled', false);
            });
        },

        /**
         * Load preset into form for editing.
         */
        loadPreset: function(presetName) {
            var preset = simpleLMSPresets[presetName];
            if (!preset) {
                return;
            }

            $('#editing_preset').val(presetName);
            $('#preset_name').val(preset.name).prop('readonly', true);
            $('#preset_label').val(preset.label || '');
            $('#orderby').val(preset.orderby || 'date');
            $('#order').val(preset.order || 'DESC');
            $('#limit').val(preset.limit !== undefined ? preset.limit : -1);
            $('#columns').val(preset.columns || 3);

            // Clear and set checkboxes.
            $('#preset-form input[type="checkbox"]').prop('checked', false);

            if (preset.statuses && preset.statuses.length) {
                preset.statuses.forEach(function(id) {
                    $('input[name="statuses[]"][value="' + id + '"]').prop('checked', true);
                });
            }

            if (preset.categories && preset.categories.length) {
                preset.categories.forEach(function(id) {
                    $('input[name="categories[]"][value="' + id + '"]').prop('checked', true);
                });
            }

            if (preset.tags && preset.tags.length) {
                preset.tags.forEach(function(id) {
                    $('input[name="tags[]"][value="' + id + '"]').prop('checked', true);
                });
            }

            // Reorder and check elements.
            if (preset.elements && preset.elements.length) {
                var $sortable = $('#elements-sortable');
                preset.elements.forEach(function(element) {
                    var $item = $sortable.find('li[data-element="' + element + '"]');
                    $item.find('input[type="checkbox"]').prop('checked', true);
                    $sortable.append($item);
                });
            }

            // Update UI.
            $('#preset-form-title').text('Edit Preset: ' + presetName);
            $('#cancel-edit').show();

            // Scroll to form.
            $('html, body').animate({
                scrollTop: $('#preset-form-title').offset().top - 50
            }, 300);
        },

        /**
         * Delete preset via AJAX.
         */
        deletePreset: function(presetName) {
            var data = {
                action: 'simple_lms_delete_shortcode_preset',
                nonce: simpleLMS.nonce,
                preset_name: presetName
            };

            $.post(simpleLMS.ajaxUrl, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || simpleLMS.i18n.error);
                }
            }).fail(function() {
                alert(simpleLMS.i18n.error);
            });
        },

        /**
         * Reset form to add new mode.
         */
        resetForm: function() {
            var $form = $('#preset-form');
            $form[0].reset();
            $('#editing_preset').val('');
            $('#preset_name').prop('readonly', false);
            $('#preset-form-title').text('Add New Preset');
            $('#cancel-edit').hide();

            // Reset elements to default order and selection.
            var defaultElements = ['title', 'status', 'date', 'time', 'duration', 'lecturer'];
            var $sortable = $('#elements-sortable');

            $sortable.find('input[type="checkbox"]').prop('checked', false);
            defaultElements.forEach(function(element) {
                $sortable.find('li[data-element="' + element + '"] input[type="checkbox"]').prop('checked', true);
            });
        },

        /**
         * Initialize elements sortable.
         */
        initElementsSortable: function() {
            $('#elements-sortable').sortable({
                handle: '.handle',
                placeholder: 'elements-placeholder',
                opacity: 0.7
            });
        },

        /**
         * Escape HTML.
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Initialize course actions.
         */
        initCourseActions: function() {
            // Delete course.
            $(document).on('click', '.delete-course', function(e) {
                e.preventDefault();

                var courseId = $(this).data('course-id');
                if (!courseId) {
                    return;
                }

                if (!confirm(simpleLMS.i18n.confirmDeleteCourse)) {
                    return;
                }

                var $row = $(this).closest('tr');

                $.post(simpleLMS.ajaxUrl, {
                    action: 'simple_lms_delete_course',
                    nonce: simpleLMS.nonce,
                    course_id: courseId
                }, function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || simpleLMS.i18n.error);
                    }
                }).fail(function() {
                    alert(simpleLMS.i18n.error);
                });
            });
        }
    };

    $(document).ready(function() {
        SimpleLMSAdmin.init();
    });

})(jQuery);
