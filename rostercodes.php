<?php
require_once 'utils.php';

/*
used from the Membership Roster Page
*/

class ArchReactorRoster
{
    public static function init()
    {
        add_shortcode('archreactor_rosterfile', [__CLASS__, 'send_rosterfile']);
        add_shortcode('archreactor_rosterrfid', [__CLASS__, 'render_rfid']);
    }

    public static function rosterfile($file){
        if (!file_exists("/var/www/local/data/" . $file)) {
            return "Data missing";
        }

        return file_get_contents("/var/www/local/data/" . $file);
    }
    public static function send_rosterfile($atts)
    {
        return self::rosterfile($atts["file"]);
    }

    public static function render_rfid()
    {
        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        //no contact?
        if ($cid == null) return null;

        $data = ArchReactorRosterManager::rfid_data();
        $fails = $data['fails'];
        $activities = $data['activities'];

        $timezone = new DateTimeZone('America/Chicago');
        $date = new DateTime('now', $timezone);
        ob_start();
?>
        
    <div>
        Last 10 failures and all lockout access last 6 months <br />
        Last updated: <?php echo $date->format("Y-m-d H:i:s"); ?>
    </div>
    <div style="display: flex; gap: 20px;">
    <?php
        $failtbl = array();
        foreach ($fails as $activity) {
            $failtbl[] = [
                'subject' => $activity['subject'],
                'datetime' => date_i18n("Y-m-d g:i:s A", date_create($activity['activity_date_time'])->getTimestamp())
            ];
        }
        print(ArchReactorUtils::renderTable($failtbl, null, null, "rosterrfidfail", "civicrm-ux-roster"));

        $passtbl = array();
        foreach ($activities as $activity) {
            $passtbl[] = [
                'name' => $activity['contact.sort_name'],
                'subject' => $activity['subject'],
                'datetime' => date_i18n("Y-m-d g:i:s A", date_create($activity['activity_date_time'])->getTimestamp())
            ];
        }
        print(ArchReactorUtils::renderTable($passtbl, null, null, "rosterrfid", "civicrm-ux-roster"));

    ?>
    </div>
    <?php
        return ob_get_clean();
    }

}
