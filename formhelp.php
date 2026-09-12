<?php

/*
used from various forms in the Contact post type
*/
class ArchReactorFormHelp {
    public static function init() {
        //allows contact7 forms to use shortcodes
        add_filter( 'wpcf7_form_elements', 'do_shortcode' );

        add_shortcode('archreactor_idfield', [__CLASS__, 'generate_idfields']);
        add_shortcode('archreactor_hours2minutes', [__CLASS__, 'render_hours2minutes']);
        add_shortcode('archreactor_lookupaddress', [__CLASS__, 'lookup_address']);

    }

    /* populate fields with logged in civicrm contact ID
        $atts:
            fields: comma seperated list of field names that hold the ID
    */
    public static function generate_idfields($atts)
    {
        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        //no contact?
        if ($cid == null) return null;

        // Start output buffering
        ob_start();
?>
    <script>
    "<?php echo $atts["fields"]; ?>".split(",").forEach(f=> {
        jQuery('.wpcf7 input[name="'+f+'"]').val("<?php echo $cid; ?>");  
    });
    </script>
<?php
        
        return ob_get_clean();
    }

    /* Convert entered hours to minutes
    $atts:
        hoursfield: name of field to watch
        minutesfields: comma seperated list of field names that hold the minutes
    */
    public static function render_hours2minutes($atts)
    {
        // Start output buffering
        ob_start();
?>
    <script>
    jQuery('.wpcf7 input[name="<?php echo $atts["hoursfield"]; ?>"]').on('change', e => {
        var h = parseFloat(e.target.value);
        if(isNaN(h)) return;
        var m = h * 60.0;
        "<?php echo $atts["minutesfields"]; ?>".split(",").forEach(f=> {
        jQuery('.wpcf7 input[name="'+f+'"]').val(m);  
        });
    });
    </script>
<?php
        
        return ob_get_clean();
    }

    /* populate fields with address for logged in civicrm contact ID
        form field names must match the civicrm field names
    */
    public static function lookup_address()
    {
        $cid = CRM_Core_Session::singleton()->getLoggedInContactID();
        //no contact?
        if ($cid == null) return null;

        $addresses = \Civi\Api4\Address::get(false)
            ->addSelect('id', 'location_type_id', 'street_address', 'city', 'state_province_id:abbr', 'postal_code', 'country_id:abbr')
            ->addWhere('contact_id', '=', $cid)
            ->addOrderBy('location_type_id', 'ASC')
            ->setLimit(1)
            ->execute();
        $address = $addresses[0] ?? null;
        // Start output buffering
        ob_start();
        if ($address) {
        ?>
            <script>
                jQuery('.wpcf7 input[name="id"]').val("<?php echo $address['id']; ?>");  
                jQuery('.wpcf7 input[name="location_type_id"]').val("<?php echo $address['location_type_id']; ?>");  
                jQuery('.wpcf7 input[name="street_address"]').val("<?php echo $address['street_address']; ?>");  
                jQuery('.wpcf7 input[name="city"]').val("<?php echo $address['city']; ?>");  
                jQuery('.wpcf7 input[name="state_province_id:abbr"]').val("<?php echo $address['state_province_id:abbr']; ?>");  
                jQuery('.wpcf7 input[name="postal_code"]').val("<?php echo $address['postal_code']; ?>");  
                jQuery('.wpcf7 input[name="country_id:abbr"]').val("<?php echo $address['country_id:abbr']; ?>"); 
            </script>
        <?php
        } else {
        ?>
            <script>
                jQuery('.wpcf7 input[name="id"]').val("0");  
                jQuery('.wpcf7 input[name="location_type_id"]').val("1");  
                jQuery('.wpcf7 input[name="state_province_id:abbr"]').val("MO");  
                jQuery('.wpcf7 input[name="country_id:abbr"]').val("US"); 
            </script>
        <?php
        }

        
        return ob_get_clean();
    }

}

