<?php

/*
used from Cancel Paypal Subscription under Member Forms post type

*/
class ArchReactorPaypal
{
    public static function init()
    {
        add_shortcode('archreactor_paypalcancel', array(__CLASS__, 'paypalcancel'));
    }

    public static function paypalcancel($atts, $content = null)
    {
        // normalize attribute keys, lowercase
        $atts = array_change_key_case((array) $atts, CASE_LOWER);

        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();

        // If we have an invalid contact, abort
        if ($cid == null) {
            return 'Error! Member not found. <br /> <a href="/dashboard">Return to Dashboard</a>';
        }

        if (isset($_REQUEST["trxn_id"])) {
            $subscriptionId = $_REQUEST["trxn_id"];
            $ch = curl_init();
            $clientId = get_option('archreactor_paypal_client_id');
            $secret = get_option('archreactor_paypal_secret');

            curl_setopt($ch, CURLOPT_URL, "https://api-m.paypal.com/v1/oauth2/token");
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

            $result = curl_exec($ch);

            if (empty($result)) die("Error: No response.");
            else {
                $json = json_decode($result);
                $access_token = $json->access_token;
                #print_r($access_token . "\n");
            }

            ob_start();

            curl_close($ch);

            if ($access_token) {
                echo ("Acess Token Accepted. <br />Canceling subscription " . $subscriptionId . "<br />");
                static::cancelPPSubscription($subscriptionId, $access_token);
                ?><br /> <a href="/dashboard">Return to Dashboard</a><?php
            } else {
                echo "Acess Token issue, subscription not canceled, please contact a board member for assistence<br />";
            }
        } else {

            $pp = (array) $contributionRecurs = \Civi\Api4\ContributionRecur::get(false)
            ->addSelect('contact_id', 'contact_id.display_name', 'trxn_id', 'auto_renew', 'frequency_unit', 'start_date', 'next_sched_contribution_date')
            ->addWhere('contact_id', '=', $cid)
            ->addWhere('cancel_date', 'IS NULL')
            ->addWhere('processor_id', 'CONTAINS', 'I-')
            ->execute();

            if (!$pp || count($pp) == 0) {
                return 'Active subscription not found.  <br /> <a href="/dashboard">Return to Dashboard</a>';
            }
            $row = $pp[0];
            ob_start();
            echo "ID: " . $row['id'] . "<br />\n";
            echo "Contact ID: " . $row['contact_id'] . "<br />\n";
            echo "Name: " . $row['contact_id.display_name'] . "<br />\n";
            echo "Transaction Token: " . $row['trxn_id'] . "<br />\n";
            echo "Auto Renew: " . ($row['auto_renew'] ? "Yes" : "No") . "<br />\n";
            echo "Frequency: " . $row['frequency_unit'] . "<br />\n";
            echo "Start Date: " . $row['start_date'] . "<br />\n";
            echo "Next Contribution Date: " . $row['next_sched_contribution_date'] . "<br />\n";

            ?>
            <br />
            <form method="post">
                <input type="hidden" name="trxn_id" value="<?php echo $row['trxn_id']; ?>">
                <input type="submit" value="Cancel this subscription">
            </form>
            <br /> <a href="/dashboard">Return to Dashboard</a>
            <?php
        }
        return ob_get_clean();
    }

    public static function cancelPPSubscription($subscriptionId, $access_token)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api-m.paypal.com/v1/billing/subscriptions/$subscriptionId/cancel");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer $access_token"
        ));

        $data = json_encode(array(
            'reason' => 'Reason for cancellation' // Optional reason for cancellation
        ));

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $result = curl_exec($ch);
        $resinfo = curl_getinfo($ch);
        if ($resinfo['http_code'] == 204) {
            echo "Subscription cancelled successfully.";
        } else {
            $json = json_decode($result, true);
            echo ("<br />");
            //echo $result;
            if (isset($json['name']) && $json['name'] === "INVALID_REQUEST") {
                echo "\nError: Subscription ID not found.\n";
            } else {
                echo "Error cancelling Subscription, please contact a board member for assistence.<br />";
                echo "Debug info: ";
                echo ($result);
            }
        }

        curl_close($ch);
    }

}