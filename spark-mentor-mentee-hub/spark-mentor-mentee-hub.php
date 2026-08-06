<?php
/**
 * Plugin Name: Spark Mentor-Mentee Hub
 * Description: Dashboard pages for Spark mentors and students: Page 1 (progress dashboard) and Page 2 (historical notes + college tracker).
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────────────────────────
   College Tracker checklist items — edit here to update Page 2
   ────────────────────────────────────────────────────────────── */
define( 'SMH_COLLEGE_FALL_STEPS', [
    'Draft your college essay',
    'Finalize your college list',
    'Create a master calendar',
    'Retake SAT (if needed)',
    'Start submitting applications',
    'File your FAFSA',
    'Start scholarship applications',
] );

define( 'SMH_COLLEGE_SPRING_STEPS', [
    'Submit the rest of your college applications',
    'Submit more scholarship applications',
    'Complete financial aid verification (if needed)',
    'Review and compare your award letters',
    'Make a college decision & notify colleges',
    'Pay your enrollment fee and/or housing deposit',
    'Create a calendar of enrollment steps',
] );

/* ──────────────────────────────────────────────────────────────
   Shared CSS — enqueued on both template pages
   ────────────────────────────────────────────────────────────── */
add_action( 'wp_enqueue_scripts', function () {
    if ( is_page_template( 'spark-mentor-mentee-hub/template-page1.php' ) ||
         is_page_template( 'spark-mentor-mentee-hub/template-page2.php' ) ) {
        wp_enqueue_style(
            'spark-hub',
            plugin_dir_url( __FILE__ ) . 'style.css',
            [],
            '1.0'
        );
    }
} );

/* ──────────────────────────────────────────────────────────────
   Register page templates
   ────────────────────────────────────────────────────────────── */
add_filter( 'theme_page_templates', function ( $templates ) {
    $templates['spark-mentor-mentee-hub/template-page1.php'] = 'Spark Hub – Dashboard';
    $templates['spark-mentor-mentee-hub/template-page2.php'] = 'Spark Hub – Notes & College Tracker';
    return $templates;
} );

add_filter( 'template_include', function ( $template ) {
    if ( is_page() ) {
        $tpl = get_post_meta( get_the_ID(), '_wp_page_template', true );
        if ( in_array( $tpl, [
            'spark-mentor-mentee-hub/template-page1.php',
            'spark-mentor-mentee-hub/template-page2.php',
        ], true ) ) {
            $path = WP_PLUGIN_DIR . '/' . $tpl;
            if ( file_exists( $path ) ) {
                return $path;
            }
        }
    }
    return $template;
} );

/* ──────────────────────────────────────────────────────────────
   REST API — Progress Notes CRUD (moved from spark-page2)
   ────────────────────────────────────────────────────────────── */
add_action( 'rest_api_init', function () {
    $ns = 'spark-hub/v1';

    register_rest_route( $ns, '/notes', [
        'methods'             => 'GET',
        'callback'            => 'smh_get_notes',
        'permission_callback' => '__return_true',
    ] );

    register_rest_route( $ns, '/notes', [
        'methods'             => 'POST',
        'callback'            => 'smh_create_note',
        'permission_callback' => '__return_true',
        'args'                => [
            'date' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'note' => [ 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ],
        ],
    ] );

    register_rest_route( $ns, '/notes/(?P<id>[a-f0-9\-]+)', [
        'methods'             => 'PUT',
        'callback'            => 'smh_update_note',
        'permission_callback' => '__return_true',
        'args'                => [
            'date' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            'note' => [ 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ],
        ],
    ] );

    register_rest_route( $ns, '/notes/(?P<id>[a-f0-9\-]+)', [
        'methods'             => 'DELETE',
        'callback'            => 'smh_delete_note',
        'permission_callback' => '__return_true',
    ] );
} );

function smh_get_notes() {
    return rest_ensure_response( smh_load_notes() );
}

function smh_create_note( WP_REST_Request $req ) {
    $notes = smh_load_notes();
    $new   = [
        'id'   => wp_generate_uuid4(),
        'date' => $req->get_param( 'date' ),
        'note' => $req->get_param( 'note' ),
    ];
    array_unshift( $notes, $new );
    smh_save_notes( $notes );
    return rest_ensure_response( $new );
}

function smh_update_note( WP_REST_Request $req ) {
    $id    = $req->get_param( 'id' );
    $notes = smh_load_notes();
    foreach ( $notes as &$n ) {
        if ( $n['id'] === $id ) {
            $n['date'] = $req->get_param( 'date' );
            $n['note'] = $req->get_param( 'note' );
            smh_save_notes( $notes );
            return rest_ensure_response( $n );
        }
    }
    return new WP_Error( 'not_found', 'Note not found', [ 'status' => 404 ] );
}

function smh_delete_note( WP_REST_Request $req ) {
    $id    = $req->get_param( 'id' );
    $notes = smh_load_notes();
    $notes = array_values( array_filter( $notes, fn( $n ) => $n['id'] !== $id ) );
    smh_save_notes( $notes );
    return rest_ensure_response( [ 'deleted' => true ] );
}

function smh_load_notes() {
    $notes = get_option( 'smh_progress_notes', null );
    if ( $notes === null ) {
        $notes = [
            [ 'id' => wp_generate_uuid4(), 'date' => '2025-11-03', 'note' => 'Student showed strong improvement in essay-writing skills. Discussed college list and narrowed down to 8 schools.' ],
            [ 'id' => wp_generate_uuid4(), 'date' => '2025-10-20', 'note' => 'Completed SAT prep session. Student feels confident. Reviewed scholarship calendar and flagged three deadlines.' ],
            [ 'id' => wp_generate_uuid4(), 'date' => '2025-10-06', 'note' => 'Goal-setting meeting. Set weekly check-in cadence. Student identified engineering as primary focus area.' ],
            [ 'id' => wp_generate_uuid4(), 'date' => '2025-09-22', 'note' => 'Initial intake session. Reviewed background, interests, and academic history. Student is highly motivated.' ],
        ];
        smh_save_notes( $notes );
    }
    return $notes;
}

function smh_save_notes( $notes ) {
    update_option( 'smh_progress_notes', $notes );
}
