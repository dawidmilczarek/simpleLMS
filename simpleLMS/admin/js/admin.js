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
            this.initSlugGeneration();
            this.initScreenOptions();
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

            // Duplicate single course.
            $(document).on('click', '.duplicate-course', function(e) {
                e.preventDefault();

                var courseId = $(this).data('course-id');
                if (!courseId) {
                    return;
                }

                if (!confirm(simpleLMS.i18n.confirmDuplicateCourse)) {
                    return;
                }

                var $link = $(this);
                var originalText = $link.text();
                $link.text(simpleLMS.i18n.duplicating);

                $.post(simpleLMS.ajaxUrl, {
                    action: 'simple_lms_duplicate_course',
                    nonce: simpleLMS.nonce,
                    course_id: courseId
                }, function(response) {
                    if (response.success && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        $link.text(originalText);
                        alert(response.data.message || simpleLMS.i18n.error);
                    }
                }).fail(function() {
                    $link.text(originalText);
                    alert(simpleLMS.i18n.error);
                });
            });
        },

        /**
         * Initialize product select with Select2.
         */
        initProductSelect: function() {
            if (typeof $.fn.select2 === 'undefined') {
                return;
            }

            // Membership plans select (no AJAX, options are pre-loaded).
            var $membershipSelect = $('.simple-lms-membership-select');
            if ($membershipSelect.length) {
                $membershipSelect.select2({
                    width: '100%',
                    placeholder: simpleLMS.i18n.selectMemberships || '',
                    allowClear: true
                });
            }

            // Subscription products select (with AJAX).
            var $productSelect = $('.simple-lms-product-select');
            if ($productSelect.length) {
                $productSelect.select2({
                    width: '100%',
                    placeholder: simpleLMS.i18n.selectProducts || '',
                    allowClear: true,
                    minimumInputLength: 0,
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
        },

        /**
         * Initialize slug generation from title.
         */
        initSlugGeneration: function() {
            var $title = $('#course_title');
            var $slug = $('#course_slug');

            if (!$title.length || !$slug.length) {
                return;
            }

            var self = this;

            // Only auto-generate if slug is empty (new course or empty slug).
            $title.on('blur', function() {
                if (!$slug.val() && $title.val()) {
                    $slug.val(self.slugify($title.val()));
                }
            });

            // Clean slug on input.
            $slug.on('input', function() {
                var cleaned = self.slugify($(this).val());
                $(this).val(cleaned);
            });
        },

        /**
         * Initialize column toggle in screen options.
         */
        initScreenOptions: function() {
            $(document).on('change', '.simple-lms-column-toggle', function() {
                var $checkbox = $(this);
                var column = $checkbox.data('column');
                var visible = $checkbox.is(':checked');

                $.post(simpleLMS.ajaxUrl, {
                    action: 'simple_lms_toggle_column',
                    nonce: simpleLMS.nonce,
                    column: column,
                    visible: visible
                });
            });
        },

        /**
         * Convert string to URL-friendly slug.
         */
        slugify: function(text) {
            // Basic transliteration for Polish characters.
            var charMap = {
                'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n',
                'ó': 'o', 'ś': 's', 'ź': 'z', 'ż': 'z',
                'Ą': 'a', 'Ć': 'c', 'Ę': 'e', 'Ł': 'l', 'Ń': 'n',
                'Ó': 'o', 'Ś': 's', 'Ź': 'z', 'Ż': 'z'
            };

            var slug = text.toLowerCase();

            // Replace Polish characters.
            for (var char in charMap) {
                slug = slug.replace(new RegExp(char, 'g'), charMap[char]);
            }

            return slug
                .replace(/[^a-z0-9\s-]/g, '') // Remove non-alphanumeric.
                .replace(/\s+/g, '-')          // Replace spaces with hyphens.
                .replace(/-+/g, '-')           // Replace multiple hyphens with single.
                .replace(/^-|-$/g, '');        // Trim hyphens from start/end.
        }
    };

    $(document).ready(function() {
        SimpleLMSAdmin.init();
    });

})(jQuery);
