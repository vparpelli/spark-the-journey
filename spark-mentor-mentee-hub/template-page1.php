<?php
/*
 * Template Name: Spark Hub – Dashboard
 * Template Post Type: page
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $website;
get_template_part( '/inc/base/GeneralClass' );
$website = new GeneralClass();
$website->fields();

// ── Salesforce data ───────────────────────────────────────────────────────────
$sf      = false;
$wp_user = wp_get_current_user();

if ( $wp_user->exists() ) {
    $sf_contact_id = spark_sf_get_contact_id_by_email( $wp_user->user_email );
    if ( $sf_contact_id ) {
        $sf = spark_sf_get_dashboard_data( $sf_contact_id );
    }
}

// Helper: format a SF date string (YYYY-MM-DD) to a readable format, or show a fallback.
function smh_format_date( ?string $val, string $fallback = '—' ): string {
    if ( empty( $val ) ) return esc_html( $fallback );
    $ts = strtotime( $val );
    return $ts ? esc_html( date( 'M j, Y', $ts ) ) : esc_html( $fallback );
}

function smh_val( $val, string $fallback = '—' ): string {
    return ( $val !== null && $val !== '' ) ? esc_html( $val ) : esc_html( $fallback );
}

get_header();
?>

<main role="main" class="smh-page">
  <div class="smh-page__inner">
    <div class="smh-rows">

      <!-- Row 1: Form (left 1/3) + Progress Report (right 2/3) -->
      <div class="smh-row smh-row--top">

        <!-- Log a Progress Note -->
        <div class="smh-card">
          <h2 class="smh-widget__title">Log a Progress Note</h2>
          <div class="smh-form-embed">
            <?php
            $checkin_url = $sf['checkin_url'] ?? '';
            $is_real_url = $sf && filter_var( $checkin_url, FILTER_VALIDATE_URL );
            ?>
            <?php if ( $is_real_url ) : ?>
              <iframe src="<?php echo esc_url( $checkin_url ); ?>" width="100%" height="400" frameborder="0"></iframe>
            <?php else : ?>
              <p class="smh-placeholder">Embedded form will appear here.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Progress Report -->
        <div class="smh-card">
          <h2 class="smh-widget__title">Progress Report</h2>

          <?php if ( ! $sf ) : ?>
            <p class="smh-error">Could not load data. Please try again later.</p>
          <?php else : ?>

          <div class="smh-progress__body">
            <div class="smh-progress__stats">
              <div class="smh-stat">
                <span class="smh-stat__label">Last pair meet-up:</span>
                <span class="smh-stat__value"><?php echo smh_format_date( $sf['last_pair_meetup'] ); ?></span>
              </div>
              <div class="smh-stat">
                <span class="smh-stat__label">Last Spark check-in:</span>
                <span class="smh-stat__value"><?php echo smh_format_date( $sf['last_spark_checkin'] ); ?></span>
              </div>
              <div class="smh-stat">
                <span class="smh-stat__label">Check-in this semester:</span>
                <span class="smh-stat__value"><?php echo smh_val( $sf['checkin_this_semester'] ); ?></span>
              </div>
              <div class="smh-stat">
                <span class="smh-stat__label">Last progress note:</span>
                <span class="smh-stat__value"><?php echo smh_format_date( $sf['last_progress_note'] ); ?></span>
              </div>
            </div>

            <div class="smh-progress__right">
              <div>
                <h3 class="smh-topics__heading">Topics Covered</h3>
                <div class="smh-topics__grid">
                  <label class="smh-topic"><input type="checkbox" disabled> Goal setting</label>
                  <label class="smh-topic"><input type="checkbox" disabled> Career exploration</label>
                  <label class="smh-topic"><input type="checkbox" disabled> Budgeting</label>
                  <label class="smh-topic"><input type="checkbox" disabled> College fit &amp; match</label>
                  <label class="smh-topic"><input type="checkbox" disabled> Networking</label>
                  <label class="smh-topic"><input type="checkbox" disabled> Identity &amp; belonging</label>
                  <label class="smh-topic"><input type="checkbox" disabled> Financial aid</label>
                  <label class="smh-topic"><input type="checkbox" disabled> Elevator pitch</label>
                  <label class="smh-topic"><input type="checkbox" disabled> Personal statement</label>
                </div>
              </div>
            </div>
          </div>

          <?php endif; ?>
        </div>

      </div><!-- /.smh-row--top -->

      <!-- Row 2: Upcoming Events (left 2/3) + Quick Links (right 1/3) -->
      <div class="smh-row smh-row--bottom">

        <!-- Upcoming Events -->
        <div class="smh-card">
          <h2 class="smh-widget__title">Upcoming Events</h2>
          <div class="smh-events__list">
            <?php if ( $sf && ! empty( $sf['events'] ) ) : ?>
              <?php foreach ( $sf['events'] as $event ) : ?>
              <div class="smh-event">
                <div class="smh-event__meta">
                  <span class="smh-event__date"><?php echo smh_format_date( $event['date'] ); ?></span>
                  <span class="smh-event__sep">|</span>
                  <span class="smh-event__name"><?php echo smh_val( $event['name'] ); ?></span>
                </div>
                <?php if ( ! empty( $event['description'] ) ) : ?>
                <p class="smh-event__desc"><?php echo esc_html( $event['description'] ); ?></p>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            <?php else : ?>
              <p class="smh-placeholder">No upcoming events at this time.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="smh-card">
          <h2 class="smh-widget__title">Quick Links</h2>
          <div class="smh-links__list">
            <a href="<?php echo $sf ? esc_url( $sf['schedule_call_url'] ) : '#'; ?>" class="smh-link-btn" target="_blank" rel="noopener">Schedule a call with your PC</a>
            <a href="<?php echo $sf ? esc_url( $sf['rsvp_url'] ) : '#'; ?>" class="smh-link-btn" target="_blank" rel="noopener">RSVP for an event</a>
            <a href="<?php echo $sf ? esc_url( $sf['emergency_funds_url'] ) : '#'; ?>" class="smh-link-btn" target="_blank" rel="noopener">Apply for emergency funds</a>
            <a href="#" class="smh-link-btn">Launch Spark&rsquo;s Learning Portal</a>
          </div>
        </div>

      </div><!-- /.smh-row--bottom -->

    </div><!-- /.smh-rows -->
  </div><!-- /.smh-page__inner -->
</main>

<?php get_footer(); ?>
