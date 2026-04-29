<?php
/**
 * Template: Analytics dashboard page.
 *
 * Why: extracted from Reel_It_Settings::render_analytics_page() to keep the
 * class file under the 200-line limit. Receives $days, $stats, and
 * $top_videos from the calling method.
 *
 * @package Reel_It
 * @since   1.5.1
 * @var     int   $days       Current lookback window.
 * @var     array $stats      Summary stats array.
 * @var     array $top_videos Top performing videos.
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wrap reel-it-wrap reel-it-analytics-wrap">
    <div class="reel-it-header">
        <h1><?php esc_html_e( 'Video Analytics', 'reel-it' ); ?></h1>
        <div class="reel-it-date-filter">
            <a href="<?php echo esc_url( add_query_arg( 'days', 7 ) ); ?>" class="button <?php echo $days === 7 ? 'button-primary' : ''; ?>"><?php esc_html_e( '7 Days', 'reel-it' ); ?></a>
            <a href="<?php echo esc_url( add_query_arg( 'days', 30 ) ); ?>" class="button <?php echo $days === 30 ? 'button-primary' : ''; ?>"><?php esc_html_e( '30 Days', 'reel-it' ); ?></a>
            <a href="<?php echo esc_url( add_query_arg( 'days', 90 ) ); ?>" class="button <?php echo $days === 90 ? 'button-primary' : ''; ?>"><?php esc_html_e( '90 Days', 'reel-it' ); ?></a>
        </div>
    </div>

    <div class="reel-it-stats-grid">
        <div class="reel-it-stat-card">
            <span class="reel-it-stat-icon dashicons dashicons-visibility"></span>
            <div class="reel-it-stat-content">
                <span class="reel-it-stat-value"><?php echo esc_html( number_format( $stats['total_plays'] ) ); ?></span>
                <span class="reel-it-stat-label"><?php esc_html_e( 'Total Plays', 'reel-it' ); ?></span>
            </div>
        </div>
        <div class="reel-it-stat-card">
            <span class="reel-it-stat-icon dashicons dashicons-yes-alt"></span>
            <div class="reel-it-stat-content">
                <span class="reel-it-stat-value"><?php echo esc_html( number_format( $stats['total_completions'] ) ); ?></span>
                <span class="reel-it-stat-label"><?php esc_html_e( 'Completions', 'reel-it' ); ?></span>
            </div>
        </div>
        <div class="reel-it-stat-card">
            <span class="reel-it-stat-icon dashicons dashicons-chart-line"></span>
            <div class="reel-it-stat-content">
                <span class="reel-it-stat-value"><?php echo esc_html( $stats['completion_rate'] ); ?>%</span>
                <span class="reel-it-stat-label"><?php esc_html_e( 'Completion Rate', 'reel-it' ); ?></span>
            </div>
        </div>
        <div class="reel-it-stat-card">
            <span class="reel-it-stat-icon dashicons dashicons-cart"></span>
            <div class="reel-it-stat-content">
                <span class="reel-it-stat-value"><?php echo esc_html( number_format( $stats['total_clicks'] ) ); ?></span>
                <span class="reel-it-stat-label"><?php esc_html_e( 'Product Clicks', 'reel-it' ); ?></span>
            </div>
        </div>
    </div>

    <div class="reel-it-card">
        <h2><?php esc_html_e( 'Top Performing Videos', 'reel-it' ); ?></h2>
        <?php if ( empty( $top_videos ) ) : ?>
            <p class="reel-it-no-data"><?php esc_html_e( 'No analytics data yet. Views will appear once visitors start watching your videos.', 'reel-it' ); ?></p>
        <?php else : ?>
            <table class="widefat striped reel-it-analytics-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Video', 'reel-it' ); ?></th>
                        <th><?php esc_html_e( 'Plays', 'reel-it' ); ?></th>
                        <th><?php esc_html_e( 'Completions', 'reel-it' ); ?></th>
                        <th><?php esc_html_e( 'Completion Rate', 'reel-it' ); ?></th>
                        <th><?php esc_html_e( 'Product Clicks', 'reel-it' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $top_videos as $video ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $video['title'] ); ?></strong></td>
                            <td><?php echo esc_html( number_format( $video['plays'] ) ); ?></td>
                            <td><?php echo esc_html( number_format( $video['completions'] ) ); ?></td>
                            <td><?php echo esc_html( $video['completion_rate'] ); ?>%</td>
                            <td><?php echo esc_html( number_format( $video['clicks'] ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
