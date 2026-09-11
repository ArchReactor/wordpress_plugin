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
            <?php echo ArchReactorRoster::rosterfile('memberships.txt'); ?>
        </div>
        
        <div id="new">
            <?php echo ArchReactorRoster::rosterfile('new_memberships.txt'); ?>
        </div>

        <div id="grace">
            <?php echo ArchReactorRoster::rosterfile('grace_memberships.txt'); ?>
        </div>

        <div id="expired">
            <?php echo ArchReactorRoster::rosterfile('expired_memberships.txt'); ?>
        </div>

        <div id="rfid">
            <p><a href="/scripts/updatelocks.php" data-type="link" data-id="/scripts/updatelocks.php" rel="noreferrer noopener">Update Lockouts (now works remote via restricted tunnel)</a></p>
            <?php echo ArchReactorRoster::render_rfid(); ?>
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

<script type="text/javascript" src="//cdn.datatables.net/2.3.7/js/dataTables.min.js" id="datatable-js-js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/2.3.7/css/dataTables.dataTables.min.css">
<script type="text/javascript" src="/scripts/js/roster.js" id="roster-js"></script>

<script type="text/javascript" >
    var $tabs;
    console.log("Initializing tabs for #archreactor-roster");
    jQuery(document).ready(function($) {
        $tabs = $('#archreactor-roster').tabs({
            // Triggered every time a new tab is activated
            activate: function(event, ui) {
                // ui.newTab is the list item (<li>), ui.newTab.find('a') gets the anchor
                var hash = ui.newTab.find('a').attr('href');
                
                // Update the URL hash without triggering a page jump
                if (history.pushState) {
                    history.pushState(null, null, hash);
                } else {
                    window.location.hash = hash; // Fallback for very old browsers
                }
            }
        });
        var hash = window.location.hash;
        if (hash) {
            // Find the index of the tab anchor matching the hash
            var index = $tabs.find('a[href="' + hash + '"]').parent().index();
            
            // If a matching tab is found, activate it
            if (index !== -1) {
                $tabs.tabs('option', 'active', index);
            }
        }

        $(window).on('hashchange', function() {
            var hash = window.location.hash;
            if (hash) {
                var index = $tabs.find('a[href="' + hash + '"]').parent().index();
                if (index !== -1) {
                    $tabs.tabs("option", "active", index);
                }
            }
        });    
    });

</script>
<!-- /wp:html -->
