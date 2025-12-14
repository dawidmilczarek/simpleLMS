/**
 * simpleLMS Admin JavaScript
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
            this.initElementsSortable();
            this.initCourseActions();
            this.initProductSelect();
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
         * Initialize elements sortable (for shortcode presets).
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
            var self = this;

            // Delete single course.
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

            // Select all checkboxes.
            $(document).on('change', '#cb-select-all', function() {
                var checked = $(this).prop('checked');
                $('.course-checkbox').prop('checked', checked);
            });

            // Update select-all state when individual checkbox changes.
            $(document).on('change', '.course-checkbox', function() {
                var total = $('.course-checkbox').length;
                var checked = $('.course-checkbox:checked').length;
                $('#cb-select-all').prop('checked', total === checked && total > 0);
            });

            // Bulk action.
            $(document).on('click', '#do-bulk-action', function() {
                var action = $('#bulk-action-selector').val();
                if (!action) {
                    return;
                }

                var $checked = $('.course-checkbox:checked');
                if ($checked.length === 0) {
                    alert(simpleLMS.i18n.noCoursesSelected || 'Please select at least one course.');
                    return;
                }

                if (action === 'delete') {
                    self.bulkDeleteCourses($checked);
                }
            });
        },

        /**
         * Bulk delete courses.
         */
        bulkDeleteCourses: function($checkboxes) {
            var courseIds = [];
            $checkboxes.each(function() {
                courseIds.push($(this).val());
            });

            var confirmMsg = simpleLMS.i18n.confirmBulkDelete || 'Are you sure you want to delete {count} courses?';
            confirmMsg = confirmMsg.replace('{count}', courseIds.length);

            if (!confirm(confirmMsg)) {
                return;
            }

            var $status = $('#bulk-status');
            $status.text(simpleLMS.i18n.deleting || 'Deleting...');

            $.post(simpleLMS.ajaxUrl, {
                action: 'simple_lms_bulk_delete_courses',
                nonce: simpleLMS.nonce,
                course_ids: courseIds
            }, function(response) {
                if (response.success) {
                    $checkboxes.each(function() {
                        $(this).closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    });
                    $status.text(response.data.message || 'Deleted successfully.');
                    setTimeout(function() {
                        $status.text('');
                    }, 3000);
                } else {
                    $status.text('');
                    alert(response.data.message || simpleLMS.i18n.error);
                }
            }).fail(function() {
                $status.text('');
                alert(simpleLMS.i18n.error);
            });
        },

        /**
         * Initialize product select with Select2.
         */
        initProductSelect: function() {
            var $productSelect = $('.simple-lms-product-select');

            if (!$productSelect.length || typeof $.fn.select2 === 'undefined') {
                return;
            }

            $productSelect.select2({
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: simpleLMS.ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'simple_lms_search_products',
                            nonce: simpleLMS.nonce,
                            search: params.term || ''
                        };
                    },
                    processResults: function(response) {
                        if (response.success) {
                            return { results: response.data };
                        }
                        return { results: [] };
                    },
                    cache: true
                }
            });
        }
    };

    $(document).ready(function() {
        SimpleLMSAdmin.init();
    });

})(jQuery);
