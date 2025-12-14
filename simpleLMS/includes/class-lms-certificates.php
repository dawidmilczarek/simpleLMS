<?php
/**
 * Certificate generation functionality.
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * LMS_Certificates class.
 */
class LMS_Certificates {

    /**
     * Constructor.
     */
    public function __construct() {
        // Frontend certificate generation.
        add_action( 'init', array( $this, 'handle_frontend_certificate_generation' ) );

        // Admin certificate generation.
        add_action( 'admin_post_simple_lms_generate_certificate', array( $this, 'handle_admin_certificate_generation' ) );

        // Register shortcode.
        add_shortcode( 'lms_certificate', array( $this, 'render_certificate_shortcode' ) );
    }

    /**
     * Get default certificate template.
     *
     * @return string
     */
    public static function get_default_certificate_template() {
        $template_path = SIMPLE_LMS_PLUGIN_DIR . 'templates/certificate-template.html';
        if ( file_exists( $template_path ) ) {
            return file_get_contents( $template_path );
        }
        return '';
    }

    /**
     * Handle frontend certificate generation.
     */
    public function handle_frontend_certificate_generation() {
        if ( ! isset( $_GET['lms_generate_certificate'] ) || '1' !== $_GET['lms_generate_certificate'] ) {
            return;
        }

        // Verify user is logged in.
        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in to generate a certificate.', 'simple-lms' ) );
        }

        // Verify nonce.
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'lms_generate_certificate' ) ) {
            wp_die( esc_html__( 'Invalid security token.', 'simple-lms' ) );
        }

        // Validate course ID.
        $course_id = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : 0;
        if ( ! $course_id || 'simple_lms_course' !== get_post_type( $course_id ) ) {
            wp_die( esc_html__( 'Invalid course.', 'simple-lms' ) );
        }

        // Check if certificate is enabled for this course.
        $certificate_enabled = get_post_meta( $course_id, '_simple_lms_certificate_enabled', true );
        if ( '0' === $certificate_enabled ) {
            wp_die( esc_html__( 'Certificate is not available for this course.', 'simple-lms' ) );
        }

        // Check user access.
        if ( ! $this->user_has_access( $course_id ) ) {
            wp_die( esc_html__( 'You do not have access to this course.', 'simple-lms' ) );
        }

        // Validate completion date.
        $completion_date = isset( $_GET['completion_date'] ) ? sanitize_text_field( wp_unslash( $_GET['completion_date'] ) ) : '';
        if ( empty( $completion_date ) ) {
            wp_die( esc_html__( 'Completion date is required.', 'simple-lms' ) );
        }

        // Check date is not in future.
        $completion_timestamp = strtotime( $completion_date );
        $today_timestamp      = strtotime( 'today' );
        if ( $completion_timestamp > $today_timestamp ) {
            wp_die( esc_html__( 'Completion date cannot be later than today.', 'simple-lms' ) );
        }

        // Check date is not earlier than course date.
        $course_date = get_post_meta( $course_id, '_simple_lms_date', true );
        if ( ! empty( $course_date ) ) {
            $course_timestamp = strtotime( $course_date );
            if ( $completion_timestamp < $course_timestamp ) {
                wp_die( esc_html__( 'Completion date cannot be earlier than the course date.', 'simple-lms' ) );
            }
        }

        // Get certificate data.
        $data = $this->get_certificate_data( $course_id, get_current_user_id(), $completion_date );

        // Generate PDF.
        $this->generate_certificate_pdf( $data );
    }

    /**
     * Handle admin certificate generation.
     */
    public function handle_admin_certificate_generation() {
        // Check permissions.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission for this operation.', 'simple-lms' ) );
        }

        // Verify nonce.
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'simple_lms_generate_certificate' ) ) {
            wp_die( esc_html__( 'Invalid security token.', 'simple-lms' ) );
        }

        // Validate required fields.
        $required_fields = array( 'user_name', 'course_title', 'trainer_name', 'course_duration', 'completion_date' );
        foreach ( $required_fields as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_die( esc_html__( 'All fields are required.', 'simple-lms' ) );
            }
        }

        // Check date is not in future.
        $completion_date      = sanitize_text_field( wp_unslash( $_POST['completion_date'] ) );
        $completion_timestamp = strtotime( $completion_date );
        $today_timestamp      = strtotime( 'today' );
        if ( $completion_timestamp > $today_timestamp ) {
            wp_die( esc_html__( 'Completion date cannot be later than today.', 'simple-lms' ) );
        }

        // Build data array.
        $data = array(
            'user_name'       => sanitize_text_field( wp_unslash( $_POST['user_name'] ) ),
            'course_title'    => sanitize_text_field( wp_unslash( $_POST['course_title'] ) ),
            'lecturer'        => sanitize_text_field( wp_unslash( $_POST['trainer_name'] ) ),
            'duration'        => sanitize_text_field( wp_unslash( $_POST['course_duration'] ) ),
            'completion_date' => $completion_date,
        );

        // Generate PDF.
        $this->generate_certificate_pdf( $data );
    }

    /**
     * Get certificate data from course and user.
     *
     * @param int    $course_id       Course ID.
     * @param int    $user_id         User ID.
     * @param string $completion_date Completion date.
     * @return array
     */
    public function get_certificate_data( $course_id, $user_id, $completion_date ) {
        $user = get_userdata( $user_id );
        $post = get_post( $course_id );

        // Get lecturer from taxonomy.
        $lecturer_terms = wp_get_post_terms( $course_id, 'simple_lms_lecturer', array( 'fields' => 'names' ) );
        $lecturer       = ! empty( $lecturer_terms ) && ! is_wp_error( $lecturer_terms ) ? implode( ', ', $lecturer_terms ) : '';

        // Get duration.
        $duration = get_post_meta( $course_id, '_simple_lms_duration', true );

        // Format completion date.
        $date_format            = Simple_LMS::get_setting( 'date_format', 'd.m.Y' );
        $formatted_date         = date_i18n( $date_format, strtotime( $completion_date ) );

        return array(
            'user_name'       => $user ? trim( $user->first_name . ' ' . $user->last_name ) : '',
            'course_title'    => $post ? $post->post_title : '',
            'lecturer'        => $lecturer,
            'duration'        => $duration,
            'completion_date' => $formatted_date,
        );
    }

    /**
     * Generate certificate PDF.
     *
     * @param array $data Certificate data.
     */
    public function generate_certificate_pdf( $data ) {
        // Load TCPDF.
        if ( ! class_exists( 'TCPDF' ) ) {
            require_once SIMPLE_LMS_PLUGIN_DIR . 'tcpdf/tcpdf.php';
        }

        // Get settings.
        $logo_url      = get_option( 'simple_lms_certificate_logo_url', '' );
        $signature_url = get_option( 'simple_lms_certificate_signature_url', '' );
        $template      = get_option( 'simple_lms_certificate_template', self::get_default_certificate_template() );

        $issuer_company = get_option( 'simple_lms_certificate_issuer_company', '' );
        $issuer_name    = get_option( 'simple_lms_certificate_issuer_name', '' );
        $issuer_title   = get_option( 'simple_lms_certificate_issuer_title', '' );

        // Parse template.
        $html = $this->parse_certificate_template( $template, array_merge( $data, array(
            'logo_url'       => $logo_url,
            'signature_url'  => $signature_url,
            'issuer_company' => $issuer_company,
            'issuer_name'    => $issuer_name,
            'issuer_title'   => $issuer_title,
        ) ) );

        // Create PDF.
        $pdf = new TCPDF();
        $pdf->SetCreator( PDF_CREATOR );
        $pdf->SetAuthor( $data['lecturer'] );
        $pdf->SetTitle( 'Certyfikat - ' . $data['course_title'] );
        $pdf->setPrintHeader( false );
        $pdf->setPrintFooter( false );
        $pdf->SetMargins( 15, 15, 15 );
        $pdf->AddPage();
        $pdf->SetFont( 'dejavusans', '', 12, '', true );
        $pdf->writeHTML( $html, true, false, true, false, '' );

        // Output PDF inline.
        $pdf->Output( 'certyfikat.pdf', 'I' );
        exit;
    }

    /**
     * Parse certificate template.
     *
     * @param string $template Template HTML.
     * @param array  $data     Data for placeholders.
     * @return string
     */
    private function parse_certificate_template( $template, $data ) {
        $placeholders = array(
            '{{CERT_USER_NAME}}'       => esc_html( $data['user_name'] ),
            '{{CERT_COURSE_TITLE}}'    => esc_html( $data['course_title'] ),
            '{{CERT_LECTURER}}'        => esc_html( $data['lecturer'] ),
            '{{CERT_DURATION}}'        => esc_html( $data['duration'] ),
            '{{CERT_COMPLETION_DATE}}' => esc_html( $data['completion_date'] ),
            '{{CERT_LOGO_URL}}'        => esc_url( $data['logo_url'] ),
            '{{CERT_SIGNATURE_URL}}'   => esc_url( $data['signature_url'] ),
            '{{CERT_ISSUER_COMPANY}}'  => esc_html( $data['issuer_company'] ),
            '{{CERT_ISSUER_NAME}}'     => esc_html( $data['issuer_name'] ),
            '{{CERT_ISSUER_TITLE}}'    => esc_html( $data['issuer_title'] ),
        );

        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
    }

    /**
     * Check if user has access to course.
     *
     * @param int $course_id Course ID.
     * @return bool
     */
    private function user_has_access( $course_id ) {
        // Admins always have access.
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Use LMS_Access_Control if available.
        if ( class_exists( 'LMS_Access_Control' ) ) {
            return LMS_Access_Control::user_has_access( $course_id, get_current_user_id() );
        }

        return true;
    }

    /**
     * Render certificate shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_certificate_shortcode( $atts ) {
        // Check if user is logged in.
        if ( ! is_user_logged_in() ) {
            return '<p class="lms-certificate-message">' . esc_html__( 'Please log in to view certificates.', 'simple-lms' ) . '</p>';
        }

        $user_id = get_current_user_id();

        // Query courses with certificate enabled.
        $args = array(
            'post_type'      => 'simple_lms_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'     => '_simple_lms_certificate_enabled',
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_simple_lms_certificate_enabled',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        $courses = get_posts( $args );

        // Filter courses by user access.
        $accessible_courses = array();
        foreach ( $courses as $course ) {
            if ( $this->user_has_access( $course->ID ) ) {
                $accessible_courses[] = $course;
            }
        }

        if ( empty( $accessible_courses ) ) {
            return '<p class="lms-certificate-message">' . esc_html__( 'No certificates available.', 'simple-lms' ) . '</p>';
        }

        // Build output.
        $output = '<div class="lms-certificates-list">';
        $output .= '<table class="lms-certificates-table">';
        $output .= '<thead><tr>';
        $output .= '<th>' . esc_html__( 'Course', 'simple-lms' ) . '</th>';
        $output .= '<th>' . esc_html__( 'Lecturer', 'simple-lms' ) . '</th>';
        $output .= '<th>' . esc_html__( 'Date', 'simple-lms' ) . '</th>';
        $output .= '<th>' . esc_html__( 'Certificate', 'simple-lms' ) . '</th>';
        $output .= '</tr></thead>';
        $output .= '<tbody>';

        $date_format = Simple_LMS::get_setting( 'date_format', 'd.m.Y' );
        $today       = date( 'Y-m-d' );

        foreach ( $accessible_courses as $course ) {
            // Get course data.
            $course_date = get_post_meta( $course->ID, '_simple_lms_date', true );
            $formatted_date = '';
            if ( ! empty( $course_date ) ) {
                $formatted_date = date_i18n( $date_format, strtotime( $course_date ) );
            }

            $lecturer_terms = wp_get_post_terms( $course->ID, 'simple_lms_lecturer', array( 'fields' => 'names' ) );
            $lecturer       = ! empty( $lecturer_terms ) && ! is_wp_error( $lecturer_terms ) ? implode( ', ', $lecturer_terms ) : '';

            $nonce = wp_create_nonce( 'lms_generate_certificate' );

            // Determine min date (course date) and default value.
            $min_date     = ! empty( $course_date ) ? $course_date : '';
            $default_date = $today;
            // If course date is in the future, don't allow certificate generation yet.
            if ( ! empty( $course_date ) && $course_date > $today ) {
                $default_date = $course_date;
            }

            $output .= '<tr>';
            $output .= '<td><a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a></td>';
            $output .= '<td>' . esc_html( $lecturer ) . '</td>';
            $output .= '<td>' . esc_html( $formatted_date ) . '</td>';
            $output .= '<td>';

            // Only show form if course date is not in the future.
            if ( empty( $course_date ) || $course_date <= $today ) {
                $output .= '<form method="get" action="" target="_blank" class="lms-cert-inline-form">';
                $output .= '<input type="hidden" name="lms_generate_certificate" value="1">';
                $output .= '<input type="hidden" name="course_id" value="' . esc_attr( $course->ID ) . '">';
                $output .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
                $output .= '<input type="date" name="completion_date" value="' . esc_attr( $default_date ) . '"';
                if ( ! empty( $min_date ) ) {
                    $output .= ' min="' . esc_attr( $min_date ) . '"';
                }
                $output .= ' max="' . esc_attr( $today ) . '" required>';
                $output .= '<button type="submit" class="lms-certificate-button">' . esc_html__( 'Download', 'simple-lms' ) . '</button>';
                $output .= '</form>';
            } else {
                $output .= '<span class="lms-certificate-unavailable">' . esc_html__( 'Available after course', 'simple-lms' ) . '</span>';
            }

            $output .= '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table></div>';

        return $output;
    }

    /**
     * Render certificate button for template placeholder.
     *
     * @param int $course_id Course ID.
     * @return string
     */
    public function render_certificate_button( $course_id ) {
        // Check if user is logged in.
        if ( ! is_user_logged_in() ) {
            return '';
        }

        // Check if certificate is enabled.
        $certificate_enabled = get_post_meta( $course_id, '_simple_lms_certificate_enabled', true );
        if ( '0' === $certificate_enabled ) {
            return '';
        }

        // Check user access.
        if ( ! $this->user_has_access( $course_id ) ) {
            return '';
        }

        $today       = date( 'Y-m-d' );
        $course_date = get_post_meta( $course_id, '_simple_lms_date', true );

        // Don't show certificate button if course date is in the future.
        if ( ! empty( $course_date ) && $course_date > $today ) {
            return '<p class="lms-certificate-unavailable">' . esc_html__( 'Certificate will be available after the course.', 'simple-lms' ) . '</p>';
        }

        $nonce    = wp_create_nonce( 'lms_generate_certificate' );
        $min_date = ! empty( $course_date ) ? $course_date : '';

        $output = '<form method="get" action="" target="_blank" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">';
        $output .= '<input type="hidden" name="lms_generate_certificate" value="1">';
        $output .= '<input type="hidden" name="course_id" value="' . esc_attr( $course_id ) . '">';
        $output .= '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
        $output .= '<input type="date" name="completion_date" value="' . esc_attr( $today ) . '"';
        if ( ! empty( $min_date ) ) {
            $output .= ' min="' . esc_attr( $min_date ) . '"';
        }
        $output .= ' max="' . esc_attr( $today ) . '" required>';
        $output .= '<button type="submit" class="lms-certificate-button">' . esc_html__( 'Download certificate', 'simple-lms' ) . '</button>';
        $output .= '</form>';

        return $output;
    }

    /**
     * Check if certificate is available for course and user.
     *
     * @param int $course_id Course ID.
     * @return bool
     */
    public function is_certificate_available( $course_id ) {
        // Check if user is logged in.
        if ( ! is_user_logged_in() ) {
            return false;
        }

        // Check if certificate is enabled.
        $certificate_enabled = get_post_meta( $course_id, '_simple_lms_certificate_enabled', true );
        if ( '0' === $certificate_enabled ) {
            return false;
        }

        // Check user access.
        return $this->user_has_access( $course_id );
    }
}
