<?php
require_once 'utils.php';

class ArchReactorRosterManager {
    public const ROSTER_PAGE_SLUG = 'membership-roster';

    public static function init() {

        add_action( 'init', [__CLASS__, 'add_rewrite_rule' ]);
        // Flush rewrite rules on activation so the user doesn't have to resave permalinks
        register_activation_hook( __FILE__, [__CLASS__, 'flush_rewrites' ] );
        // 2. Intercept the main query and inject a virtual WP_Post object
        add_filter( 'the_posts', [__CLASS__, 'inject_virtual_post' ], 10, 2 );

        //javascript and ajax registration
        add_action('wp_ajax_archreactor_roster_active', [__CLASS__, 'active_json']);
        add_action('wp_ajax_archreactor_roster_expired', [__CLASS__, 'expired_json']);
        add_action('wp_ajax_archreactor_roster_grace', [__CLASS__, 'grace_json']);
        add_action('wp_ajax_archreactor_roster_rfid', [__CLASS__, 'rfid_json']);
        add_action('wp_enqueue_scripts', function() {
            wp_enqueue_script('archreactor-roster', plugins_url('js/roster.js', __FILE__), ['jquery'], '1.0', true);
            wp_enqueue_script( 'datatables-js', '//cdn.datatables.net/2.3.7/js/dataTables.min.js', array( 'jquery' ) );
            wp_enqueue_style( 'datatables-style', '//cdn.datatables.net/2.3.7/css/dataTables.dataTables.min.css' );
    
            wp_localize_script('archreactor-roster', 'archreactor_roster_vars', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('archreactor_roster_nonce')
            ]);
        });
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

    public static function active_data() {
        $active_members = \Civi\Api4\Contact::get(FALSE)
            ->addSelect("id", "display_name", "sort_name", "Volunteer.Volunteer_Hour_Good_Standing", "File_Uploads.Membership_Agreement_Initials", "File_Uploads.Liability_Waiver_Initials", "File_Uploads.Attended_Orientation")
            ->addWhere('groups', 'IN', 40) //40=active memberships
            ->addOrderBy('sort_name', 'ASC')
            ->execute();

        $contacts = array_map(function($row) {
            return [
                'id' => $row['id'],
                'display_name' => $row['display_name'],
                'sort_name' => $row['sort_name'],
                'good_standing' => $row['Volunteer.Volunteer_Hour_Good_Standing'] ? "Yes" : "No",
                'membership_agreement' => $row['File_Uploads.Membership_Agreement_Initials'],
                'liability_waiver' => $row['File_Uploads.Liability_Waiver_Initials'],
                'membership_type' => '??',
                'end_date' => '',
                'recurring' => '',
                'required_minutes' => 1440, //full year
                'total_minutes' => 0,
                'required_hours' => ArchReactorUtils::formatMinutesToHoursMinutes(1440),
                'total_hours' => ArchReactorUtils::formatMinutesToHoursMinutes(0),
            ];
        }, (array) $active_members);

        $ids = array_map(function($row) { return $row['id']; }, $contacts);
        $update_standing = [];
        $membership_totals = [
            'full' => 0,
            'supporting' => 0,
            'assisted' => 0,
            'total' => 0
        ];

        $all_activities = \Civi\Api4\Activity::get(FALSE)
            ->addSelect('activity_contact.contact_id', 'SUM(CiviVolunteer.Time_Completed_Minutes) AS total_minutes')
            ->addJoin('ActivityContact AS activity_contact', 'LEFT', ['id', '=', 'activity_contact.activity_id'])
            ->addWhere('status_id', '=', 2) //2=Completed
            ->addWhere('activity_date_time', '>', '-1 year')
            ->addWhere('activity_type_id', '=', 55) //55=Volunteer
            ->addWhere('activity_contact.contact_id', 'IN', $ids)
            ->addGroupBy('activity_contact.contact_id')
            ->execute();
        foreach ($all_activities as $m) {
            $c = array_find_key($contacts, fn($contact) => $contact['id'] === $m['activity_contact.contact_id']);
            if ($c !== false) {
                $contacts[$c]['total_minutes'] = intval($m['total_minutes']);
            }
        }

        $all_memberships = \Civi\Api4\Membership::get(FALSE)
            ->addSelect("id", "contact_id", "join_date", "end_date", "contribution_recur_id", "membership_type_id", "membership_type_id:name")
            ->addWhere('contact_id', 'IN', $ids)
            ->addWhere('status_id', 'IN', [1,2,3]) //new, current, grace
            ->addOrderBy('contact_id', 'ASC')
            ->addOrderBy('id', 'DESC')
            ->execute();
        foreach ($all_memberships as $m) {
            $c = array_find_key($contacts, fn($contact) => $contact['id'] === $m['contact_id']);
            if ($c !== false) {
                if(!isset($contacts[$c]['membership_type']) || $contacts[$c]['membership_type'] === '??') {
                    $contacts[$c]['membership_type'] = $m['membership_type_id:name'];
                    $contacts[$c]['end_date'] = $m['end_date'];
                    $contacts[$c]['contribution_recur_id'] = $m['contribution_recur_id'] ? 'Yes' : '';
                    $old_db_standing = $contacts[$c]['good_standing'] === 'Yes';
                    $join_date = new DateTime($m['join_date']);
                    if($join_date > (new DateTime('-1 year'))){
                        $contacts[$c]['required_minutes'] = round(ArchReactorUtils::dateMonthDifference($join_date, new DateTime()) * 120); //prorate based on n months
                    }
                    $contacts[$c]['good_standing'] = $contacts[$c]['total_minutes'] > $contacts[$c]['required_minutes'];
                    if($contacts[$c]['good_standing'] !== $old_db_standing) {
                        \Civi\Api4\Contact::update(FALSE)
                            ->addWhere('id', '=', $m['contact_id'])
                            ->addValue('Volunteer_Hour_Good_Standing', $contacts[$c]['good_standing'])
                            ->execute();
                    }
                    $contacts[$c]['good_standing'] = $contacts[$c]['good_standing'] ? "Yes" : "No";
                    $contacts[$c]['required_hours'] = ArchReactorUtils::formatMinutesToHoursMinutes($contacts[$c]['required_minutes']);
                    $contacts[$c]['total_hours'] = ArchReactorUtils::formatMinutesToHoursMinutes($contacts[$c]['total_minutes']);

                    if($m['membership_type_id'] === 1 || $m['membership_type_id'] === 4) { //full or family membership
                        $contacts[$c]['membership_type'] = "Full";
                        $membership_totals['full']++;
                    } else if($m['membership_type_id'] === 2 || $m['membership_type_id'] === 5) { //supporting or supporting family membership
                        $contacts[$c]['membership_type'] = "Supporting";
                        $membership_totals['supporting']++;
                    } else if($m['membership_type_id'] === 3) { //assisted 
                        $contacts[$c]['membership_type'] = "Assisted";
                        $membership_totals['assisted']++;
                    } else {
                        $contacts[$c]['membership_type'] = $m['membership_type_id:name'];
                    }
                    $membership_totals['total']++;
                }
            }
        }

        //usort($contacts, fn($a, $b) => strcmp($b['sort_name'], $a['sort_name'])); //b first makes a reverse sort
        $date = new \DateTime();

        return [
            'active' => $contacts,
            'totals' => $membership_totals,
            'updated' => $date->format("Y-m-d g:i:s A"), 
        ];
    }

    public static function active_json()
    {
        //verify nonce for security
        check_ajax_referer('archreactor_roster_nonce', 'security');

        if (!current_user_can('membership-management')) {
            wp_send_json_error('Unauthorized');
        }

        $data = self::active_data();
        wp_send_json_success($data);
    }

    public static function expired_data() {
        $expired_members = \Civi\Api4\Contact::get(FALSE)
            ->addSelect("id", "display_name", "sort_name", "File_Uploads.Membership_Agreement_Initials", "File_Uploads.Liability_Waiver_Initials", "email_primary.email", "File_Uploads.Attended_Orientation")
            ->addWhere('groups', 'IN', 64) //64=Expired memberships
            ->addOrderBy('id', 'DESC')
            ->execute();

        $contacts = array_map(function($row) {
            return [
                'id' => $row['id'],
                'display_name' => $row['display_name'],
                'sort_name' => $row['sort_name'],
                'membership_agreement' => $row['File_Uploads.Membership_Agreement_Initials'],
                'liability_waiver' => $row['File_Uploads.Liability_Waiver_Initials'],
                'email' => $row['email_primary.email'],
                'orientation' => $row['File_Uploads.Attended_Orientation'],
                'membership_type' => '??',
                'end_date' => ''
            ];
        }, (array) $expired_members);

        $ids = array_map(function($row) { return $row['id']; }, $contacts);
        $all_memberships = \Civi\Api4\Membership::get(FALSE)
            ->addSelect("id", "contact_id", "end_date", "membership_type_id:name")
            ->addWhere('contact_id', 'IN', $ids)
            ->addWhere('status_id', '!=', 5) //5=pending
            ->addOrderBy('contact_id', 'ASC')
            ->addOrderBy('id', 'DESC')
            ->execute();
        foreach ($all_memberships as $m) {
            $c = array_find_key($contacts, fn($contact) => $contact['id'] === $m['contact_id']);
            if ($c !== false) {
                if(!isset($contacts[$c]['membership_type']) || $contacts[$c]['membership_type'] === '??') {
                    $contacts[$c]['membership_type'] = $m['membership_type_id:name'];
                    $contacts[$c]['end_date'] = $m['end_date'];
                }
            }
        }
        usort($contacts, fn($a, $b) => strcmp($b['end_date'], $a['end_date'])); //b first makes a reverse sort
        $date = new \DateTime();
        return [
            'expired' => $contacts,
            'updated' => $date->format("Y-m-d g:i:s A"), 
        ];
    }

    public static function expired_json()
    {
        //verify nonce for security
        check_ajax_referer('archreactor_roster_nonce', 'security');

        if (!current_user_can('membership-management')) {
            wp_send_json_error('Unauthorized');
        }

        $data = self::expired_data();
        wp_send_json_success($data);
    }

    public static function grace_data() {
        $grace_members = \Civi\Api4\Contact::get(FALSE)
            ->addSelect("id", "display_name", "sort_name", "File_Uploads.Membership_Agreement_Initials", "File_Uploads.Liability_Waiver_Initials", "email_primary.email", "File_Uploads.Attended_Orientation", "membership.membership_type_id:name", "membership.end_date")
            ->addJoin("Membership AS membership", "LEFT")
            ->addWhere("membership.status_id", "=", 3) //3=Grace,
            ->addOrderBy("membership.end_date", "DESC")
            ->addOrderBy("display_name", "ASC")
            ->execute();

        $contacts = array_map(function($row) {
            return [
                'id' => $row['id'],
                'display_name' => $row['display_name'],
                'sort_name' => $row['sort_name'],
                'membership_agreement' => $row['File_Uploads.Membership_Agreement_Initials'],
                'liability_waiver' => $row['File_Uploads.Liability_Waiver_Initials'],
                'email' => $row['email_primary.email'],
                'orientation' => $row['File_Uploads.Attended_Orientation'],
                'membership_type' => $row['membership.membership_type_id:name'],
                'end_date' => $row['membership.end_date']
            ];
        }, (array) $grace_members);

        $date = new \DateTime();
        return [
            'grace' => $contacts,
            'updated' => $date->format("Y-m-d g:i:s A"), 
        ];
    }

    public static function grace_json()
    {
        //verify nonce for security
        check_ajax_referer('archreactor_roster_nonce', 'security');

        if (!current_user_can('membership-management')) {
            wp_send_json_error('Unauthorized');
        }

        $data = self::grace_data();
        wp_send_json_success($data);
    }

    public static function rfid_data()
    {
        $nfails = 10;
        $nmonths = 6; 
        $fails = \Civi\Api4\Activity::get(FALSE)
            ->addSelect('subject', 'activity_date_time')
            ->addWhere('activity_type_id', '=', 69) //69=RFID 
            ->addWhere('status_id', '=', 3) //canceled, used as failed
            ->addOrderBy('activity_date_time', 'DESC')
            ->setLimit($nfails)
            ->execute();
        $activities = \Civi\Api4\Activity::get(FALSE)
            ->addSelect('contact.sort_name', 'subject', 'activity_date_time')
            ->addJoin('Contact AS contact', 'LEFT', 'ActivityContact', ['contact.record_type_id', '=', 1]) //1 limits the contact to the Assignee
            ->addWhere('activity_date_time', '>', '-' . $nmonths . ' months')
            ->addWhere('activity_type_id', '=', 69) //69=RFID 
            ->addWhere('status_id', '=', 2) //completed
            ->addOrderBy('activity_date_time', 'DESC')
            ->execute();

        $timezone = new DateTimeZone('America/Chicago');
        $date = new DateTime('now', $timezone);

        return array(
            'fails' => array_map(function($row) {
                return [
                    'id' => $row['id'],
                    'subject' => $row['subject'], 
                    'activity_date_time' => date_i18n("Y-m-d g:i:s A", date_create($row['activity_date_time'])->getTimestamp())
                ];
            }, (array) $fails),
            'activities' => array_map(function($row) {
                return [
                    'id' => $row['id'],
                    'name' => $row['contact.sort_name'],
                    'subject' => $row['subject'], 
                    'activity_date_time' => date_i18n("Y-m-d g:i:s A", date_create($row['activity_date_time'])->getTimestamp())
                ];
            }, (array) $activities), 
            'updated' => $date->format("Y-m-d g:i:s A"), 
            'numfails' => $nfails, 'nummonths' => $nmonths
        );
    }

    public static function rfid_json()
    {
        //verify nonce for security
        check_ajax_referer('archreactor_roster_nonce', 'security');

        if (!current_user_can('membership-management')) {
            wp_send_json_error('Unauthorized');
        }

        $data = self::rfid_data();
        wp_send_json_success($data);
    }
}