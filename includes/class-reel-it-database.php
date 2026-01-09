<?php
/**
 * Database operations for Reel It
 *
 * @since      1.1.1
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Database operations class
 *
 * @since      1.1.1
 * @package    Reel_It
 * @subpackage Reel_It/includes
 */
class Reel_It_Database {

    /**
     * The table name for video feeds
     *
     * @since    1.1.1
     * @access   private
     * @var      string    $feeds_table    Table name for video feeds.
     */
    private $feeds_table;

    /**
     * The table name for feed videos
     *
     * @since    1.1.1
     * @access   private
     * @var      string    $feed_videos_table    Table name for feed videos.
     */
    private $feed_videos_table;

    /**
     * Initialize the class and set table names
     *
     * @since    1.1.1
     */
    public function __construct() {
        global $wpdb;
        $this->feeds_table = $wpdb->prefix . 'reel_it_feeds';
        $this->feed_videos_table = $wpdb->prefix . 'reel_it_feed_videos';
    }

    /**
     * Create database tables
     *
     * @since    1.1.1
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Feeds table
        $feeds_sql = "CREATE TABLE IF NOT EXISTS {$this->feeds_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";

        // Feed videos table
        $feed_videos_sql = "CREATE TABLE IF NOT EXISTS {$this->feed_videos_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            feed_id int(11) NOT NULL,
            video_id bigint(20) unsigned NOT NULL,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY feed_id (feed_id),
            KEY video_id (video_id),
            FOREIGN KEY (feed_id) REFERENCES {$this->feeds_table}(id) ON DELETE CASCADE,
            FOREIGN KEY (video_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $feeds_sql );
        dbDelta( $feed_videos_sql );
        
        // Check if we need to migrate existing tables
        $this->migrate_video_id_column();
    }
    
    /**
     * Migrate video_id column to bigint(20) unsigned for existing installations
     *
     * @since    1.1.1
     */
    private function migrate_video_id_column() {
        global $wpdb;
        
        // Check if the table exists and if video_id column needs migration
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $this->feed_videos_table
        ) );
        
        if ( $table_exists ) {
            // Check if video_id column is still int(11)
            $column_info = $wpdb->get_row( $wpdb->prepare(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'video_id'",
                DB_NAME,
                $this->feed_videos_table
            ) );
            
            if ( $column_info && strpos( $column_info->COLUMN_TYPE, 'int(11)' ) !== false ) {
                // Drop existing foreign key if it exists
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}reel_it_feed_videos DROP FOREIGN KEY IF EXISTS wp_reel_it_feed_videos_ibfk_2" );
                
                // Modify the column to bigint(20) unsigned
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}reel_it_feed_videos MODIFY COLUMN video_id bigint(20) unsigned NOT NULL" );
                
                // Re-add the foreign key
                $wpdb->query( "ALTER TABLE {$wpdb->prefix}reel_it_feed_videos ADD CONSTRAINT wp_reel_it_feed_videos_ibfk_2 FOREIGN KEY (video_id) REFERENCES {$wpdb->prefix}posts(ID) ON DELETE CASCADE" );
            }
        }
    }

    /**
     * Get all video feeds
     *
     * @since    1.1.1
     * @return   array    List of feeds
     */
    public function get_feeds() {
        global $wpdb;
        
        $feeds = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}reel_it_feeds ORDER BY name ASC"
        );
        
        return $feeds;
    }

    /**
     * Get all video feeds with thumbnail and video count information
     *
     * @since    1.1.2
     * @return   array    List of feeds with thumbnail and count data
     */
    public function get_feeds_with_thumbnails() {
        global $wpdb;
        
        $cached = get_transient( 'reel_it_feeds_data' );
        if ( false !== $cached ) {
            return $cached;
        }

        $feeds = $wpdb->get_results(
            "SELECT
                f.*,
                COUNT(fv.video_id) as video_count,
                MIN(fv.video_id) as first_video_id
            FROM {$wpdb->prefix}reel_it_feeds f
            LEFT JOIN {$wpdb->prefix}reel_it_feed_videos fv ON f.id = fv.feed_id
            GROUP BY f.id
            ORDER BY f.name ASC"
        );
        
        // Add thumbnail URLs to each feed
        foreach ( $feeds as $feed ) {
            $feed->video_count = intval( $feed->video_count );
            $feed->thumbnail_url = '';
            $feed->thumbnail_alt = '';
            
            $thumb_data = $this->generate_thumbnail_data( $feed->first_video_id, $feed->name );
            $feed->thumbnail_url = $thumb_data['url'];
            $feed->thumbnail_alt = $thumb_data['alt'];
        }
        
        set_transient( 'reel_it_feeds_data', $feeds, HOUR_IN_SECONDS );
        
        return $feeds;
    }

    /**
     * Get thumbnail URL for a specific feed
     *
     * @since    1.1.2
     * @param    int    $feed_id    Feed ID
     * @return   array    Thumbnail data including URL, alt text, and video count
     */
    public function get_feed_thumbnail_data( $feed_id ) {
        global $wpdb;
        
        $feed_data = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                f.id,
                f.name,
                COUNT(fv.video_id) as video_count,
                MIN(fv.video_id) as first_video_id
            FROM {$wpdb->prefix}reel_it_feeds f
            LEFT JOIN {$wpdb->prefix}reel_it_feed_videos fv ON f.id = fv.feed_id
            WHERE f.id = %d
            GROUP BY f.id",
            $feed_id
        ) );
        
        if ( ! $feed_data ) {
            return array(
                'success' => false,
                'message' => __( 'Feed not found.', 'reel-it' )
            );
        }
        
        $thumbnail_data = array(
            'success' => true,
            'feed_id' => $feed_id,
            'video_count' => intval( $feed_data->video_count ),
            'thumbnail_url' => '',
            'thumbnail_alt' => '',
            'has_videos' => $feed_data->video_count > 0
        );
        
        $thumb_data = $this->generate_thumbnail_data( $feed_data->first_video_id, $feed_data->name );
        $thumbnail_data['thumbnail_url'] = $thumb_data['url'];
        $thumbnail_data['thumbnail_alt'] = $thumb_data['alt'];
        
        return $thumbnail_data;
    }

    /**
     * Get a single feed by ID
     *
     * @since    1.1.1
     * @param    int    $feed_id    Feed ID
     * @return   object|null    Feed data or null if not found
     */
    public function get_feed( $feed_id ) {
        global $wpdb;
        
        $feed = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}reel_it_feeds WHERE id = %d",
                $feed_id
            )
        );
        
        return $feed;
    }

    /**
     * Create a new video feed
     *
     * @since    1.1.1
     * @param    string    $name        Feed name
     * @param    string    $description Feed description
     * @return   int|false    Feed ID or false on failure
     */
    public function create_feed( $name, $description = '' ) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->feeds_table,
            array(
                'name' => $name,
                'description' => $description,
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s' )
        );
        
        if ( $result ) {
            $this->clear_feed_cache();
        }
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a video feed
     *
     * @since    1.1.1
     * @param    int       $feed_id     Feed ID
     * @param    string    $name        Feed name
     * @param    string    $description Feed description
     * @return   bool      True on success, false on failure
     */
    public function update_feed( $feed_id, $name, $description = '' ) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->feeds_table,
            array(
                'name' => $name,
                'description' => $description,
                'updated_at' => current_time( 'mysql' )
            ),
            array( 'id' => $feed_id ),
            array( '%s', '%s', '%s' )
        );
        
        if ( $result !== false ) {
            $this->clear_feed_cache();
        }
        
        return $result !== false;
    }

    /**
     * Delete a video feed
     *
     * @since    1.1.1
     * @param    int    $feed_id    Feed ID
     * @return   bool    True on success, false on failure
     */
    public function delete_feed( $feed_id ) {
        global $wpdb;
        
        // First delete all videos in the feed
        $wpdb->delete(
            $this->feed_videos_table,
            array( 'feed_id' => $feed_id ),
            array( '%d' )
        );
        
        // Then delete the feed
        $result = $wpdb->delete(
            $this->feeds_table,
            array( 'id' => $feed_id ),
            array( '%d' )
        );
        
        if ( $result !== false ) {
            $this->clear_feed_cache();
        }

        return $result !== false;
    }

    /**
     * Get videos for a specific feed
     *
     * @since    1.1.1
     * @param    int    $feed_id    Feed ID
     * @return   array    List of videos
     */
    public function get_feed_videos( $feed_id ) {
        global $wpdb;
        
        $videos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT fv.*, p.post_title, p.guid as video_url, p.post_mime_type
                FROM {$wpdb->prefix}reel_it_feed_videos fv
                INNER JOIN {$wpdb->prefix}posts p ON fv.video_id = p.ID
                WHERE fv.feed_id = %d
                ORDER BY fv.sort_order ASC, fv.id ASC",
                $feed_id
            )
        );
        
        return $videos;
    }

    /**
     * Add a video to a feed
     *
     * @since    1.1.1
     * @param    int    $feed_id    Feed ID
     * @param    int    $video_id   Video ID
     * @param    int    $sort_order Sort order
     * @return   int|false    Feed video ID or false on failure
     */
    public function add_video_to_feed( $feed_id, $video_id, $sort_order = 0 ) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->feed_videos_table,
            array(
                'feed_id' => $feed_id,
                'video_id' => $video_id,
                'sort_order' => $sort_order,
                'created_at' => current_time( 'mysql' )
            ),
            array( '%d', '%d', '%d', '%s' )
        );
        
        if ( $result ) {
            $this->clear_feed_cache();
        }
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Remove a video from a feed
     *
     * @since    1.1.1
     * @param    int    $feed_id    Feed ID
     * @param    int    $video_id   Video ID
     * @return   bool    True on success, false on failure
     */
    public function remove_video_from_feed( $feed_id, $video_id ) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->feed_videos_table,
            array(
                'feed_id' => $feed_id,
                'video_id' => $video_id
            ),
            array( '%d', '%d' )
        );
        
        if ( $result !== false ) {
            $this->clear_feed_cache();
        }
        
        return $result !== false;
    }

    /**
     * Update video sort order in a feed
     *
     * @since    1.1.1
     * @param    int    $feed_id    Feed ID
     * @param    int    $video_id   Video ID
     * @param    int    $sort_order Sort order
     * @return   bool    True on success, false on failure
     */
    public function update_video_sort_order( $feed_id, $video_id, $sort_order ) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->feed_videos_table,
            array( 'sort_order' => $sort_order ),
            array(
                'feed_id' => $feed_id,
                'video_id' => $video_id
            ),
            array( '%d' ),
            array( '%d', '%d', '%d' )
        );
        
        if ( $result !== false ) {
            $this->clear_feed_cache();
        }
        
        return $result !== false;
    }

    /**
     * Get available videos for selection (from media library)
     *
     * @since    1.1.1
     * @param    string    $search    Search term
     * @param    int       $page     Page number
     * @param    int       $per_page Videos per page
     * @return   array    Videos data
     */
    public function get_available_videos( $args_or_search = '', $page = 1, $per_page = 20 ) {
        $defaults = array(
            'search' => '',
            'page' => 1,
            'per_page' => Reel_It::DEFAULT_PAGINATION,
            'author' => null, // null means all authors (except if we enforce restrict to own uploads via other means)
            'fields' => 'all', // 'all' or 'ids'
            'post_status' => 'inherit'
        );

        // Backwards compatibility for ($search, $page, $per_page)
        if ( ! is_array( $args_or_search ) ) {
            $args = array(
                'search' => $args_or_search,
                'page' => $page,
                'per_page' => $per_page
            );
        } else {
            $args = $args_or_search;
        }

        $args = wp_parse_args( $args, $defaults );

        $query_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'video',
            'post_status' => $args['post_status'],
            'posts_per_page' => $args['per_page'],
            'paged' => $args['page'],
        );

        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = $args['search'];
        }

        if ( ! empty( $args['author'] ) ) {
            $query_args['author'] = $args['author'];
        }

        if ( $args['fields'] === 'ids' ) {
            $query_args['fields'] = 'ids';
        }

        $query = new WP_Query( $query_args );
        $videos = array();

        if ( $query->have_posts() ) {
            // Optimized fetching if we only have IDs or full objects
            $posts = $query->posts;

            if ( $args['fields'] === 'ids' && ! empty( $posts ) ) {
                // If we requested IDs but want to return structured data like title/url/etc, we need to fetch them.
                // However, the original function returned a specific structure.
                // If the caller requested 'ids', they probably just want the IDs? 
                // But the return signature implies an array of video objects.
                // If 'fields' => 'ids' is passed, WP_Query returns just IDs.
                
                // If we want to support what Block Secure does (fetch limited fields + cache), 
                // we should allow this method to return what is requested.
                // But this method returns ['videos' => [], 'total' => ...].
                
                // Let's stick to returning formatted video arrays, but optimise the fetch.
                // If WP_Query returned IDs (because we passed fields=ids), we fetch details efficiently.
                
                // Check if $posts are IDs (integers/strings) or Objects
                if ( is_numeric( $posts[0] ?? null ) ) {
                    _prime_post_caches( $posts, false, false );
                     $attachments = get_posts( array(
                        'post_type' => 'attachment',
                        'post__in' => $posts,
                        'posts_per_page' => -1,
                        'orderby' => 'post__in',
                    ));
                    $posts = $attachments;
                }
            }
            
            foreach ( $posts as $post ) {
                $attachment_id = $post->ID;
                
                // Check permission if author arg was set (implied context) or if purely using generic query
                // Privacy check usually happens outside, but here we just return data.
                
                $videos[] = array(
                    'id' => $attachment_id,
                    'title' => $post->post_title,
                    'url' => wp_get_attachment_url( $attachment_id ),
                    'thumbnail' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
                    'mime' => $post->post_mime_type,
                );
            }
        }

        wp_reset_postdata();

        return array(
            'videos' => $videos,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        );
    }

    /**
     * Helper to generate thumbnail data structure
     *
     * @since 1.2.0
     * @param int $video_id
     * @param string $feed_name
     * @return array { url: string, alt: string }
     */
    private function generate_thumbnail_data( $video_id, $feed_name ) {
        $data = array(
            'url' => '',
            'alt' => ''
        );

        if ( $video_id ) {
            $thumbnail_url = wp_get_attachment_image_url( $video_id, 'thumbnail' );
            if ( $thumbnail_url ) {
                $data['url'] = $thumbnail_url;
                $data['alt'] = get_post_meta( $video_id, '_wp_attachment_image_alt', true );
                if ( empty( $data['alt'] ) ) {
                    $data['alt'] = sprintf(
                        /* translators: %s: Feed name */
                        __( 'Thumbnail for %s', 'reel-it' ),
                        $feed_name
                    );
                }
            }
        }
        return $data;
    }

    /**
     * Clear feed cache
     *
     * @since 1.2.0
     */
    private function clear_feed_cache() {
        delete_transient( 'reel_it_feeds_data' );
    }
}