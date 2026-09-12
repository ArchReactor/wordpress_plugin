<?php
class ArchReactorRosterManager {
    public const ROSTER_PAGE_SLUG = 'membership-roster';

    public static function init() {

        add_action( 'init', [__CLASS__, 'add_rewrite_rule' ]);
        // Flush rewrite rules on activation so the user doesn't have to resave permalinks
        register_activation_hook( __FILE__, [__CLASS__, 'flush_rewrites' ] );
        // 2. Intercept the main query and inject a virtual WP_Post object
        add_filter( 'the_posts', [__CLASS__, 'inject_virtual_post' ], 10, 2 );
    }

    public static function add_rewrite_rule() {
        add_rewrite_rule( '^' . self::ROSTER_PAGE_SLUG . '/?$', 'index.php?is_virtual_roster=1', 'top' );
        add_rewrite_tag( '%is_virtual_roster%', '([^&]+)' );
    }

    public static function flush_rewrites() {
        static::add_rewrite_rule();
        flush_rewrite_rules();
    }

    public static function inject_virtual_post( $posts, $wp_query ) {
        // Only intercept if our custom rewrite query variable is present
        if ( ! $wp_query->is_main_query() || ! get_query_var( 'is_virtual_roster' ) || !current_user_can('membership-management') ) {
            return $posts;
        }

        // Load the dynamic block markup from your render file
        ob_start();
        include plugin_dir_path( __FILE__ ) . 'templates/roster.php';
        $block_markup = ob_get_clean();

        // Create a mock post object in memory
        $post = new WP_Post( (object) array(
            'ID'             => -9999, // Arbitrary negative ID to avoid conflicts
            'post_author'    => 1,
            'post_date'      => current_time( 'mysql' ),
            'post_date_gmt'  => current_time( 'mysql', 1 ),
            'post_content'   => $block_markup,
            'post_title'     => __( 'Membership Roster', 'textdomain' ),
            'post_excerpt'   => '',
            'post_status'    => 'publish',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'post_password'  => '',
            'post_name'      => self::ROSTER_PAGE_SLUG,
            'to_ping'        => '',
            'pinged'         => '',
            'post_modified'  => current_time( 'mysql' ),
            'post_modified_gmt' => current_time( 'mysql', 1 ),
            'post_content_filtered' => '',
            'post_parent'    => 0,
            'guid'           => home_url( '/' . self::ROSTER_PAGE_SLUG . '/' ),
            'menu_order'     => 0,
            'post_type'      => 'page',
            'post_mime_type' => '',
            'comment_count'  => 0,
            'filter'         => 'raw',
        ) );

        // Override internal query states so themes treat it as a standard page
        $wp_query->post           = $post;
        $wp_query->posts          = array( $post );
        $wp_query->post_count     = 1;
        $wp_query->found_posts    = 1;
        $wp_query->is_single      = false;
        $wp_query->is_preview     = false;
        $wp_query->is_page        = true;
        $wp_query->is_archive     = false;
        $wp_query->is_date        = false;
        $wp_query->is_year        = false;
        $wp_query->is_month       = false;
        $wp_query->is_day         = false;
        $wp_query->is_time        = false;
        $wp_query->is_author      = false;
        $wp_query->is_category    = false;
        $wp_query->is_tag         = false;
        $wp_query->is_tax         = false;
        $wp_query->is_search      = false;
        $wp_query->is_feed        = false;
        $wp_query->is_comment_feed = false;
        $wp_query->is_trackback   = false;
        $wp_query->is_home        = false;
        $wp_query->is_privacy_policy = false;
        $wp_query->is_404         = false;

        return array( $post );
    }

    public static function rfid_data()
    {
        $fails = \Civi\Api4\Activity::get(FALSE)
            ->addSelect('subject', 'activity_date_time')
            ->addWhere('activity_type_id', '=', 69)
            ->addWhere('status_id', '=', 3)
            ->addOrderBy('activity_date_time', 'DESC')
            ->setLimit(10)
            ->execute();
        $activities = \Civi\Api4\Activity::get(FALSE)
            ->addSelect('contact.sort_name', 'subject', 'activity_date_time')
            ->addJoin('Contact AS contact', 'LEFT', 'ActivityContact', ['contact.record_type_id', '=', 1]) //1 limits the contact type on the activity
            ->addWhere('activity_date_time', '>', '-6 months')
            ->addWhere('activity_type_id', '=', 69) //69=RFID 
            ->addWhere('status_id', '=', 2)
            ->addOrderBy('activity_date_time', 'DESC')
            ->execute();

        return array('fails' => $fails, 'activities' => $activities);
    }


}