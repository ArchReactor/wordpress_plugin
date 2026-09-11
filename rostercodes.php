<?php
require_once 'utils.php';

/*
used from the Membership Roster Page
*/

class ArchReactorRoster
{
    public static function init()
    {
        add_shortcode('archreactor_rosterfile', array(__CLASS__, 'send_rosterfile'));
        add_shortcode('archreactor_rosterrfid', array(__CLASS__, 'render_rfid'));
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

        ob_start();
?>
        
    <div>
        Last 10 failures and all lockout access last 6 months <br />
        Last updated: <?php echo date("Y-m-d H:i:s"); ?>
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
