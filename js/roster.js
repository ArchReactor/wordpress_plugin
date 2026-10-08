var $tabs;
var $activeTable;
var $expiredTable;
var $graceTable;
var $rfidFailTable;
var $rfidTable;

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
            activateTabByHash(hash); // Call the function to handle tab activation
        }
    });

    $('#rosteractive').addClass("cell-border").addClass("compact").addClass("stripe");
    $activeTable = $('#rosteractive').DataTable({
        paging: false,
        searching: false,
        ordering: false,
        layout: {
            bottomStart: null,
        },
        columnDefs: [
            { className: "dt-nowrap", "targets": [ 0, 1, 2 ] }
        ],
        columns: [
            { title: 'Member', data: 'sort_name' },
            { title: 'Membership', data: 'membership_type' },
            { title: 'End Date', data: 'end_date' },
            { title: 'Required Hours', data: 'required_hours' },
            { title: 'Volunteer Hours', data: 'total_hours' },
            { title: 'Volunteer Standing', data: 'good_standing' },
            { title: 'Member Agreement', data: 'membership_agreement' },
            { title: 'Liability Waiver', data: 'liability_waiver' },
            { title: 'Auto Pay', data: 'recurring' }
        ],
    });
    $('#refresh-active').on('click', function() {
        archreactor_roster_active();
    });

    $('#rosterexpired').addClass("cell-border").addClass("compact").addClass("stripe");
    $expiredTable = $('#rosterexpired').DataTable({
        paging: false,
        searching: false,
        ordering: false,
        layout: {
            bottomStart: null,
        },
        columnDefs: [
            { className: "dt-nowrap", "targets": [ 2, 3 ] }
        ],
        columns: [
            { title: 'Member', data: 'sort_name' },
            { title: 'Email', data: 'email' },
            { title: 'Membership', data: 'membership_type' },
            { title: 'End Date', data: 'end_date' },
            { title: 'Member Agreement', data: 'membership_agreement' },
            { title: 'Liability Waiver', data: 'liability_waiver' },
            { title: 'Attended Orientation', data: 'orientation' }
        ],
    });
    $('#refresh-expired').on('click', function() {
        archreactor_roster_expired();
    });

    $('#rostergrace').addClass("cell-border").addClass("compact").addClass("stripe");
    $graceTable = $('#rostergrace').DataTable({
        paging: false,
        searching: false,
        ordering: false,
        layout: {
            bottomStart: null,
        },
        columnDefs: [
            { className: "dt-nowrap", "targets": [ 0,1,2 ] }
        ],
        columns: [
            { title: 'Member', data: 'sort_name' },
            { title: 'Email', data: 'email' },
            { title: 'Membership', data: 'membership_type' },
            { title: 'End Date', data: 'end_date' },
            { title: 'Member Agreement', data: 'membership_agreement' },
            { title: 'Liability Waiver', data: 'liability_waiver' },
            { title: 'Attended Orientation', data: 'orientation' }
        ],
    });
    $('#refresh-grace').on('click', function() {
        archreactor_roster_grace();
    });

    $('#rosterrfidfail').addClass("cell-border").addClass("compact").addClass("stripe");
    $rfidFailTable = $('#rosterrfidfail').DataTable({
        paging: false,
        searching: false,
        ordering: false,
        layout: {
            bottomStart: null,
        },
        columnDefs: [
            { className: "dt-nowrap", "targets": [ 1 ] }
        ],
        columns: [
            { title: 'Lockout and failed card ID', data: 'subject' },
            { title: 'Date Time', data: 'activity_date_time' },
        ],
    });

    $('#rosterrfid').addClass("cell-border").addClass("compact").addClass("stripe");
    $rfidTable = $('#rosterrfid').DataTable({
        paging: false,
        searching: false,
        ordering: false,
        layout: {
            bottomStart: null,
        },
        columnDefs: [
            { className: "dt-nowrap", "targets": [ 0, 2 ] }
        ],
        columns: [
            { title: 'Member', data: 'name' },
            { title: 'Lockout', data: 'subject' },
            { title: 'Date Time', data: 'activity_date_time' },
        ],
    });
    $('#refresh-rfid').on('click', function() {
        archreactor_roster_rfid();
    });


    var hash = window.location.hash;
    if (hash) {
        activateTabByHash(hash);
    } else {
        archreactor_roster_active(); // Load active members by default if no hash is present
    }

    $(window).on('hashchange', function() {
        var hash = window.location.hash;
        if (hash) {
            activateTabByHash(hash);
        }
    });


    function activateTabByHash(hash) {
        var index = $tabs.find('a[href="' + hash + '"]').parent().index();
        if (index !== -1) {
            $tabs.tabs("option", "active", index);
            switch(hash) {
                case "#active":
                    archreactor_roster_active();
                    break;
                case "#expired":
                    archreactor_roster_expired();
                    break;
                case "#grace":
                    archreactor_roster_grace();
                    break;
                case '#rfid':
                    archreactor_roster_rfid();
                    break;

            }
        }
    }

    function archreactor_roster_active() {
        $('#refresh-active').prop('disabled', true).text('Refreshing...');
        $activeTable.clear().draw();
        console.log('archreactor_roster_active called', new Date().toLocaleTimeString());
        $.ajax({
            url: archreactor_roster_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'archreactor_roster_active',
                security: archreactor_roster_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $activeTable.rows.add(response.data.active).columns.adjust().draw();
                    $('#active_updated').text(response.data.updated);
                    $('#active_full').text(response.data.totals.full);
                    $('#active_supporting').text(response.data.totals.supporting);
                    $('#active_assisted').text(response.data.totals.assisted);
                    $('#active_total').text(response.data.totals.total);
                }
                $('#refresh-active').prop('disabled', false).text('Refresh');
                console.log('archreactor_roster_active finished', new Date().toLocaleTimeString());
            },
            error: function() {
                $('#refresh-active').prop('disabled', false).text('Refresh');
            }
        });
    }

    function archreactor_roster_expired() {
        $('#refresh-expired').prop('disabled', true).text('Refreshing...');
        $expiredTable.clear().draw();
        //console.log('archreactor_roster_expired called', new Date().toLocaleTimeString());
        $.ajax({
            url: archreactor_roster_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'archreactor_roster_expired',
                security: archreactor_roster_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $expiredTable.rows.add(response.data.expired).columns.adjust().draw();
                    $('#expired_updated').text(response.data.updated);
                }
                $('#refresh-expired').prop('disabled', false).text('Refresh');
                //console.log('archreactor_roster_expired finished', new Date().toLocaleTimeString());
            },
            error: function() {
                $('#refresh-expired').prop('disabled', false).text('Refresh');
            }
        });
    }
    
    function archreactor_roster_grace() {
        $('#refresh-grace').prop('disabled', true).text('Refreshing...');
        $graceTable.clear().draw();
        //console.log('archreactor_roster_grace called', new Date().toLocaleTimeString());
        $.ajax({
            url: archreactor_roster_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'archreactor_roster_grace',
                security: archreactor_roster_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $graceTable.rows.add(response.data.grace).columns.adjust().draw();
                    $('#grace_updated').text(response.data.updated);
                }
                $('#refresh-grace').prop('disabled', false).text('Refresh');
                //console.log('archreactor_roster_grace finished', new Date().toLocaleTimeString());
            },
            error: function() {
                $('#refresh-grace').prop('disabled', false).text('Refresh');
            }
        });
    }
    
    function archreactor_roster_rfid() {
        $('#refresh-rfid').prop('disabled', true).text('Refreshing...');
        $rfidFailTable.clear().draw();
        $rfidTable.clear().draw();

        $.ajax({
            url: archreactor_roster_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'archreactor_roster_rfid',
                security: archreactor_roster_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $rfidFailTable.rows.add(response.data.fails).columns.adjust().draw();
                    $rfidTable.rows.add(response.data.activities).columns.adjust().draw();
                    $('#rfid_updated').text(response.data.updated);
                    $('#rfid_nfails').text(response.data.numfails);
                    $('#rfid_nmonths').text(response.data.nummonths);
                }
                $('#refresh-rfid').prop('disabled', false).text('Refresh');
            },
            error: function() {
                $('#refresh-rfid').prop('disabled', false).text('Refresh');
            }
        });
    }
});
