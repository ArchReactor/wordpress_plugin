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
        //add_shortcode('archreactor_rosterrfid', [__CLASS__, 'render_rfid']);
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

/*
    public static function render_rfid()
    {
        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        //no contact?
        if ($cid == null) return null;

        $data = ArchReactorRosterManager::rfid_data();
        $fails = $data['fails'];
        $activities = $data['activities'];
        $updated = $data['updated'];
        $nfails = $data['numfails'];
        $nmonths = $data['nummonths'];

        ob_start();
?>
        
    <div>
        Last <span id="rfid_nfails"><?php echo $nfails; ?></span> failures and all lockout access last <span id="rfid_nmonths"><?php echo $nmonths; ?></span> months <br />
        Last updated: <span id="rfid_updated"><?php echo $updated; ?></span>
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
                'name' => $activity['name'],
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
*/
}
