var $tabs;
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
                case '#rfid':
                    archreactor_roster_rfid();
                    break;
            }
        }
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
