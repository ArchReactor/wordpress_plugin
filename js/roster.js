var $tabs;
var $activeTable;
var $expiredTable;
var $graceTable;
var $newMemTable;
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

    $('#rosternewmem').addClass("cell-border").addClass("compact").addClass("stripe");
    $newMemTable = $('#rosternewmem').DataTable({
        paging: false,
        searching: false,
        ordering: false,
        layout: {
            bottomStart: null,
        },
        columnDefs: [
            { className: "dt-nowrap", "targets": [ 0,1,2, 3 ] },
            { "targets": -1, data: null, //uses full row data
                render: function (data, type, row) {
                    if (row.emailed_date !== '') {
                        return '<span>' + row.emailed_date + '</span>';
                    } else if(row.orientation) {
                        return '<input type="checkbox" name="email[]" value="' + row.email + '" class="row-checkbox">';
                    } else {
                        return '<input type="checkbox" name="email[]" value="' + row.email + '" checked="checked" class="row-checkbox">';
                    }
                }
            }
        ],
        columns: [
            { title: 'Member', data: 'sort_name' },
            { title: 'Email', data: 'email' },
            { title: 'Membership', data: 'membership_type' },
            { title: 'Join Date', data: 'join_date' },
            { title: 'Member Agreement', data: 'membership_agreement' },
            { title: 'Liability Waiver', data: 'liability_waiver' },
            { title: 'Attended Orientation', data: 'orientation' },
            { title: 'Send Email' }
        ],
    });
    $('#refresh-newmem').on('click', function() {
        archreactor_roster_newmem();
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
                case "#new":
                    archreactor_roster_newmem();
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
        $('#active_updated').text('...');
        $('#active_full').text('...');
        $('#active_supporting').text('...');
        $('#active_assisted').text('...');
        $('#active_total').text('...');
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
            error: function(xhr) {
                if (xhr.status === 403) {
                    alert("Session invalid, refresh the page.");
                } else {
                    console.error("Another error occurred: " + xhr.status);
                }
                $('#refresh-active').prop('disabled', false).text('Refresh');
            }
        });
    }

    function archreactor_roster_expired() {
        $('#refresh-expired').prop('disabled', true).text('Refreshing...');
        $expiredTable.clear().draw();
        $('#expired_updated').text('...');
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
            error: function(xhr) {
                if (xhr.status === 403) {
                    alert("Session invalid, refresh the page.");
                } else {
                    console.error("Another error occurred: " + xhr.status);
                }
                $('#refresh-expired').prop('disabled', false).text('Refresh');
            }
        });
    }
    
    function archreactor_roster_grace() {
        $('#refresh-grace').prop('disabled', true).text('Refreshing...');
        $graceTable.clear().draw();
        $('#grace_updated').text('...');
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
            error: function(xhr) {
                if (xhr.status === 403) {
                    alert("Session invalid, refresh the page.");
                } else {
                    console.error("Another error occurred: " + xhr.status);
                }
                $('#refresh-grace').prop('disabled', false).text('Refresh');
            }
        });
    }
    
    function archreactor_roster_newmem() {
        $('#refresh-newmem').prop('disabled', true).text('Refreshing...');
        $newMemTable.clear().draw();
        $('#newmem_updated').text('...');
        $('#newmem_since').text('...');
        $('#newmem_noemail').text('...');
        console.log('archreactor_roster_newmem called', new Date().toLocaleTimeString());
        $.ajax({
            url: archreactor_roster_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'archreactor_roster_newmem',
                security: archreactor_roster_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $newMemTable.rows.add(response.data.newmem).columns.adjust().draw();
                    $('#newmem_updated').text(response.data.updated);
                    $('#newmem_since').text(response.data.members_since);
                    $('#newmem_noemail').text(response.data.orientation_since);
                }
                $('#refresh-newmem').prop('disabled', false).text('Refresh');
                console.log('archreactor_roster_newmem finished', new Date().toLocaleTimeString());
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    alert("Session invalid, refresh the page.");
                } else {
                    console.error("Another error occurred: " + xhr.status);
                }
                $('#refresh-newmem').prop('disabled', false).text('Refresh');
            }
        });
    }

    function archreactor_roster_rfid() {
        $('#refresh-rfid').prop('disabled', true).text('Refreshing...');
        $rfidFailTable.clear().draw();
        $rfidTable.clear().draw();
        $('#rfid_updated').text('...');
        $('#rfid_nfails').text('...');
        $('#rfid_nmonths').text('...');

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
            error: function(xhr) {
                if (xhr.status === 403) {
                    alert("Session invalid, refresh the page.");
                } else {
                    console.error("Another error occurred: " + xhr.status);
                }
                $('#refresh-rfid').prop('disabled', false).text('Refresh');
            }
        });
    }
});
