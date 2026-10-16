<?php
/**
 * Dynamic Template for Virtual Page
 * Feel free to include PHP variables or logic here.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>


<!-- wp:group {"align":"full"} -->
<div class="wp-block-group alignfull">
    <!-- wp:paragraph -->
    <div id="archreactor-roster">
        <ul>
            <li><a href="#active">Active Memberships</a></li>
            <li><a href="#new">New Members</a></li>
            <li><a href="#grace">Grace Memberships</a></li>
            <li><a href="#expired">Expired Memberships</a></li>
            <li><a href="#rfid">RFID Log</a></li>
        </ul>

        <div id="active">
            <div>
                Last updated: <span id="active_updated">...</span>
                &nbsp; <button id="refresh-active" type="button">Refresh</button> <br />
                Totals Full: <span id="active_full">...</span> Supporting: <span id="active_supporting">...</span> Assisted: <span id="active_assisted">...</span> Total: <span id="active_total">...</span>
            </div>
            <div style="display: flex; gap: 20px;">
                <table id='rosteractive' class='ux-cv-listing civicrm-ux-roster'><thead></thead><tbody></tbody></table>
            </div>
        </div>

        <div id="new">
            <div>
                Last updated: <span id="newmem_updated">...</span>
                &nbsp; <button id="refresh-newmem" type="button">Refresh</button> <br />
                Checking new members since: <span id="newmem_since">...</span> <br />
                Members without emails since: <span id="newmem_noemail">...</span>
            </div>
            <div style="display: flex; gap: 20px;">
                <form action="/scripts/orientationemail.php" method="post">
                    <table id='rosternewmem' class='ux-cv-listing civicrm-ux-roster'><thead></thead><tbody></tbody></table>
                    <button style="float:right" type="submit">Send Orientation Email</button>
                </form>
            </div>
        </div>

        <div id="grace">
            <div>
                Memberships in Grace.  Last updated: <span id="grace_updated">...</span>
                &nbsp; <button id="refresh-grace" type="button">Refresh</button>
            </div>
            <div style="display: flex; gap: 20px;">
                <table id='rostergrace' class='ux-cv-listing civicrm-ux-roster'><thead></thead><tbody></tbody></table>
            </div>
        </div>

        <div id="expired">
            <div>
                Expired or Cancelled Memberships.  Last updated: <span id="expired_updated">...</span>
                &nbsp; <button id="refresh-expired" type="button">Refresh</button>
            </div>
            <div style="display: flex; gap: 20px;">
                <table id='rosterexpired' class='ux-cv-listing civicrm-ux-roster'><thead></thead><tbody></tbody></table>
            </div>
        </div>

        <div id="rfid">
            <p>
                <a href="/scripts/updatelocks.php" data-type="link" data-id="/scripts/updatelocks.php" rel="noreferrer noopener">Update Lockouts (now works remote via restricted tunnel)</a>
                &nbsp; <button id="refresh-rfid" type="button">Refresh</button>
            </p>

            <div>
                Last <span id="rfid_nfails">...</span> failures and all lockout access last <span id="rfid_nmonths">...</span> months <br />
                Last updated: <span id="rfid_updated">...</span>
            </div>
            <div style="display: flex; gap: 20px;">
                <table id='rosterrfidfail' class='ux-cv-listing civicrm-ux-roster'><thead></thead><tbody></tbody></table>
                <table id='rosterrfid' class='ux-cv-listing civicrm-ux-roster'><thead></thead><tbody></tbody></table>
            </div>
        </div>
    </div>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:html -->
<style>
    .civicrm-ux-roster > thead > tr > th {
        background: #f8f4ee;
        border-bottom: 3px solid #ccc;
        white-space: nowrap;
    }

    .civicrm-ux-roster > tbody > tr > th {
        text-align: left;
        white-space: nowrap;
    }

    .civicrm-ux-roster th, .civicrm-ux-roster td, .civicrm-ux-roster caption {
        padding: 4px 10px 4px 5px;
    }

    #rosterrfid_wrapper {
        float: left;
    }
    #rosterrfidfail_wrapper {
        float: left;
    }
</style>

<!-- /wp:html -->
