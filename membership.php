<?php
require_once 'utils.php';

/*
used from the Dashboard under Member Forms post type
*/

class ArchReactorMembership
{
    public static function init()
    {
        add_shortcode('archreactor_groups', array(__CLASS__, 'render_groups'));
        add_shortcode('archreactor_status', array(__CLASS__, 'render_status'));
        add_shortcode('archreactor_membershiprow', array(__CLASS__, 'render_membership'));
        add_shortcode('archreactor_contrib', array(__CLASS__, 'render_contrib'));
        add_shortcode('archreactor_hours', array(__CLASS__, 'render_hours'));
        add_shortcode('archreactor_dashui', array(__CLASS__, 'render_dashboard_ui'));
    }

    public static function render_membership()
    {
        $dateFormat = get_option('date_format');
        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        //no contact?
        if ($cid == null) return null;

        $apiQuery = \Civi\Api4\Membership::get(FALSE)
            ->addSelect(
                'id',
                'membership_type_id:label',
                'status_id:label',
                'join_date',
                'end_date',
                'contact.display_name',
                'contact_owner.display_name'
            )
            ->addJoin('Contact AS contact', 'LEFT', ['contact.id', '=', 'contact_id'])
            ->addJoin('Contact AS contact_owner', 'LEFT', ['contact_owner.id', '=', 'owner_membership_id.contact_id'])
            ->addWhere('contact_id', '=', $cid);

        $apiQuery->addOrderBy('end_date', 'ASC');

        $memberships = $apiQuery->execute();

        // Start output buffering
        ob_start();
        if (count($memberships) == 0) {
?>
            <p>You have no active memberships.</p>
        <?php
        } else {
            $headers = [
                'display_name' => "Member",
                'membership_type_label' => "Membership Type",
                'join_date' => "Join Date",
                'end_date' => "End Date",
                'status_label' => "Membership Status"
            ];
            $rows = array();
            foreach ($memberships as $membership) {
                $rows[] = [
                    'display_name' => $membership['contact_owner.display_name'] ?? $membership['contact.display_name'],
                    'membership_type_label' => $membership['membership_type_id:label'],
                    'join_date' => !empty($membership['join_date']) ? date_i18n($dateFormat, date_create($membership['join_date'])->getTimestamp()) : '-',
                    'end_date' => !empty($membership['end_date']) ? date_i18n($dateFormat, date_create($membership['end_date'])->getTimestamp()) : '-',
                    'status_label' => $membership['status_id:label'],
                ];
            }

            print(ArchReactorUtils::renderTable($rows, null, $headers, "membershiprow", "civicrm-ux-membership alignwide"));
        }

        return ob_get_clean();
    }

    public static function render_hours()
    {
        $dateFormat = get_option('date_format');
        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        //no contact?
        if ($cid == null) return null;

        $apiQuery = \Civi\Api4\Activity::get(FALSE)
            ->addSelect('activity_date_time', 'CiviVolunteer.Volunteer_Role_Id:name', 'CiviVolunteer.Time_Completed_Minutes', 'details')
            ->addWhere('target_contact_id', '=', $cid)
            ->addWhere('activity_type_id', '=', 55) //volunteer
            ->addWhere('status_id', '=', 2)
            ->addWhere('activity_date_time', 'BETWEEN', [date('Y-m-d', strtotime("-1 year")), date('Y-m-d', strtotime("+1 day"))])
            ->addOrderBy('activity_date_time', 'DESC');

        $hours = $apiQuery->execute();

        // Start output buffering
        ob_start();
        if (count($hours) == 0) {
        ?>
            <p>No hours recorded in the past year.</p>
        <?php
        } else {
        ?>
            <table class="civicrm-ux-membership alignwide">
                <tr>
                    <th>Activity Date</th>
                    <th>Activity</th>
                    <th>Time Completed</th>
                    <th>Details</th>
                </tr>
                <?php
                $totalminutes = 0;
                foreach ($hours as $event) {
                    if (!empty($event['activity_date_time'])) {
                        $formattedDate = date_i18n($dateFormat, date_create($event['activity_date_time'])->getTimestamp());
                    } else {
                        $formattedDate = '-';
                    }
                    $totalminutes += (float)($event['CiviVolunteer.Time_Completed_Minutes']);
                ?>

                    <tr>
                        <td><?php echo $formattedDate; ?></td>
                        <td><?php echo $event['CiviVolunteer.Volunteer_Role_Id:name']; ?></td>
                        <td><?php echo number_format((float)($event['CiviVolunteer.Time_Completed_Minutes'] / 60), 1); ?> hours</td>
                        <td><?php echo $event['details']; ?></td>
                    </tr>

                <?php
                }
                ?>

                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><?php echo number_format($totalminutes / 60, 1); ?> hours, Avg <?php echo number_format($totalminutes / 60 / 12, 1); ?> per month</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        <?php
        }

        return ob_get_clean();
    }

    public static function render_contrib()
    {
        $dateFormat = get_option('date_format');
        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        //no contact?
        if ($cid == null) return null;

        $contrib = \Civi\Api4\Contribution::get(false)
            ->addSelect('total_amount', 'financial_type_id:name', 'receive_date', 'contribution_status_id:name')
            ->addWhere('contact_id', '=', $cid)
            ->addWhere('receive_date', 'BETWEEN', [date('Y-m-d', strtotime("-1 year")), date('Y-m-d', strtotime("+1 day"))])
            ->addOrderBy('receive_date', 'DESC')
            ->execute();

        // Start output buffering
        ob_start();
        if (count($contrib) == 0) {
        ?>
            <p>No contributions recorded in the past year.</p>
        <?php
        } else {
        ?>
            <table class="civicrm-ux-membership alignwide">
                <tr>
                    <th>Total Amount</th>
                    <th>Financial Type</th>
                    <th>Date Received</th>
                    <th>Status</th>
                </tr>
                <?php
                foreach ($contrib as $c) {
                    if (!empty($c['receive_date'])) {
                        $formattedDate = date_i18n($dateFormat, date_create($c['receive_date'])->getTimestamp());
                    } else {
                        $formattedDate = '-';
                    }
                ?>

                    <tr>
                        <td>$<?php echo number_format((float)($c['total_amount']), 2); ?> hours</td>
                        <td><?php echo $c['financial_type_id:name']; ?></td>
                        <td><?php echo $formattedDate; ?></td>
                        <td><?php echo $c['contribution_status_id:name']; ?></td>
                    </tr>

                <?php
                }
                ?>
            </table>
<?php
        }

        return ob_get_clean();
    }

    public static function render_groups($atts)
    {
        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array) $atts, CASE_LOWER);

        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();

        // If we have an invalid contact, abort
        if ($cid == null) {
            return "";
        }

        $groups = (array) \Civi\Api4\Group::get(FALSE)
            ->addSelect('title', 'contact.status')
            ->addJoin('Contact AS contact', 'INNER', 'GroupContact')
            ->addWhere('group_type', '=', 3)
            ->addWhere('contact.id', '=', $cid)
            ->addOrderBy('title', 'ASC')
            ->execute();

        if (!$groups || count($groups) == 0) {
            return "";
        }

        ob_start();
        print(ArchReactorUtils::renderTable($groups, null, null, "membershipgroups", "civicrm-ux-membership"));
        return ob_get_clean();
    }

    public static function render_status()
    {
        $status = [
            'waiver' => '<img src="/img/stop_32.png" />',
            'agreement' => '<img src="/img/stop_32.png" />',
            'vgood' => '<img src="/img/stop_32.png" />',
            'wifi' => 'ARGuest: orangewalls',
            'discord' => 'Membership required',
            'calendar' => 'Membership required',
        ];

        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        // If there's no contact...
        if ($cid == null) {
            return $status;
        }

        $contacts = \Civi\Api4\Contact::get(FALSE)
            ->addSelect('File_Uploads.Liability_Waiver_Initials', 'File_Uploads.Membership_Agreement_Initials', 'Volunteer.Volunteer_Hour_Good_Standing', 'membership.end_date')
            ->addJoin('Membership AS membership', 'LEFT', ['id', '=', $cid], ['membership.status_id', 'IN', [1, 2, 3]])
            ->addWhere('id', '=', $cid)
            ->addOrderBy('membership.end_date', 'DESC')
            ->execute();
        if (count($contacts) == 0) {
            return $status;
        }

        if ($contacts[0]['File_Uploads.Liability_Waiver_Initials']) {
            $status['waiver'] = '<img src="' . esc_url( plugins_url( 'assets/tick_32.png', __FILE__ ) ) . '" alt="Got it" />';
        } else {
            $status['waiver'] = '<a href="/form/liabilitywaiver"><img src="' . esc_url( plugins_url( 'assets/stop_32.png', __FILE__ ) ) . '" alt="Missing" /></a>';
        }
        if ($contacts[0]['membership.end_date'] && strtotime($contacts[0]['membership.end_date'] . "+1 month") >= time()) {
            if ($contacts[0]['File_Uploads.Membership_Agreement_Initials']) {
                $status['agreement'] = '<img src="' . esc_url( plugins_url( 'assets/tick_32.png', __FILE__ ) ) . '" alt="Got it" />';
            } else {
                $status['agreement'] = '<a href="/form/memberagreement"><img src="' . esc_url( plugins_url( 'assets/stop_32.png', __FILE__ ) ) . '" alt="Missing" /></a>';
            }
            if ($contacts[0]['Volunteer.Volunteer_Hour_Good_Standing']) {
                $status['vgood'] = '<img src="' . esc_url( plugins_url( 'assets/tick_32.png', __FILE__ ) ) . '" alt="Got it" />';
            } else {
                $status['vgood'] = '<a href="/form/volunteer-time-reporting"><img src="' . esc_url( plugins_url( 'assets/stop_32.png', __FILE__ ) ) . '" alt="More hours needed" /></a>';
            }
            $status['wifi'] = '<details><summary>' . get_option('archreactor_members_wifi') . ' <span style="font-size: small;">(expand for password)</span></summary>' . get_option('archreactor_members_wifi_password') . '</details>';
            $status['discord'] = '<a href="' . esc_attr(get_option('archreactor_discord_invite')) . '">' . get_option('archreactor_discord_invite') . '</a>';
            $status['calendar'] = '<details><summary>Add to your Calendar event<span style="font-size: small;">(expand for email)</span></summary> ' . get_option('archreactor_calendar_email') . '</details>';
        } else {
            $status['agreement'] = ' ';
            $status['vgood'] = ' ';
            $status['wifi'] = get_option('archreactor_guest_wifi') . ': '. get_option('archreactor_guest_wifi_password');
            $status['discord'] = 'Membership required';
            $status['calendar'] = 'Membership required';
        }
        $rows = array();
        $rows[] = ['title' => 'Liability Waiver', 'status' => $status['waiver']];
        $rows[] = ['title' => 'Membership Agreement', 'status' => $status['agreement']];
        $rows[] = ['title' => 'Volunteer Hours', 'status' => $status['vgood']];
        $rows[] = ['title' => 'WiFi', 'status' => $status['wifi']];
        $rows[] = ['title' => 'Discord invite', 'status' => $status['discord']];
        $rows[] = ['title' => 'Calendar invite', 'status' => $status['calendar']];

        ob_start();
        print(ArchReactorUtils::renderTable($rows, null, null, "membershipstatus", "civicrm-ux-membership"));
        return ob_get_clean();
    }

    public static function render_dashboard_ui() {
        ob_start();
        ?>
<style>
    .civicrm-ux-membership > thead > tr > th {
        background: #f8f4ee;
        border-bottom: 3px solid #ccc;
        white-space: nowrap;
    }

    .civicrm-ux-membership > tbody > tr > th {
        text-align: left;
        white-space: nowrap;
    }

    .civicrm-ux-membership th, .civicrm-ux-membership td, .civicrm-ux-membership caption {
        padding: 4px 10px 4px 5px;
    }
</style>

<script type="text/javascript" src="//cdn.datatables.net/2.3.7/js/dataTables.min.js" id="datatable-js-js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/2.3.7/css/dataTables.dataTables.min.css">
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('#membershipgroups').addClass("cell-border").addClass("compact").addClass("stripe");
        jQuery('#membershipgroups').DataTable({
            paging: false,
            searching: false,
            ordering: false,
            layout: {
                bottomStart: null,
            },
            columns: [
                { title: 'ID', visible: false },
                { title: 'Group' },
                { title: 'Status' },
            ],
        });
        jQuery('#membershipstatus').addClass("cell-border").addClass("compact").addClass("stripe");
        jQuery('#membershipstatus').DataTable({
            paging: false,
            searching: false,
            ordering: false,
            layout: {
                bottomStart: null,
            },
            columns: [
                { title: 'Item' },
                { title: 'Status' },
            ],
        });
    });
</script>
        <?php
        return ob_get_clean();
    }
}

