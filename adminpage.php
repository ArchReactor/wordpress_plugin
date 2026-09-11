<?php

/* usage
$text   = get_option('archreactor_members_wifi', 'default');
*/

class ArchReactorAdmin
{
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'settings_init'));
    }

    public static function add_admin_menu() {
        add_options_page(
            'Archreactor Configuration', 
            'Archreactor',              
            'manage_options',
            'archreactor_settings',
            array(__CLASS__, 'settings'),
            1
        );
    }

    public static function settings_init() {
        $dashboard_fields = array(
            array('name' => 'archreactor_guest_wifi', 'title' => 'Guest WiFi SSID'),
            array('name' => 'archreactor_guest_wifi_password', 'title' => 'Guest WiFi Password', 'type' => 'password'),
            array('name' => 'archreactor_members_wifi', 'title' => 'Members WiFi SSID'),
            array('name' => 'archreactor_members_wifi_password', 'title' => 'Members WiFi Password', 'type' => 'password'),
            array('name' => 'archreactor_discord_invite', 'title' => 'Discord Invite Link'),
            array('name' => 'archreactor_calendar_email', 'title' => 'Calendar Email'),
        );
        $paypal_fields = array(
            array('name' => 'archreactor_paypal_client_id', 'title' => 'PayPal Client ID'),
            array('name' => 'archreactor_paypal_secret', 'title' => 'PayPal Secret'),
        );

        // Create the visual container section
        add_settings_section(
            'archreactor_section_dashboard',
            'Dashboard Settings',
            array(__CLASS__, 'dashboard_callback'),
            'archreactor-settings-dashboard'
        );
        add_settings_section(
            'archreactor_section_paypal',
            'PayPal Settings',
            array(__CLASS__, 'paypal_callback'),
            'archreactor-settings-paypal'
        );

        foreach ($dashboard_fields as $field) {
            $name = $field['name'];
            $title = $field['title'];
            $type = isset($field['type']) ? $field['type'] : 'text';

            register_setting('archreactor_options_group', $name);
            add_settings_field(
                $name,
                $title,
                array(__CLASS__, 'render_input'),
                'archreactor-settings-dashboard',
                'archreactor_section_dashboard',
                array('name' => $name, 'type' => $type)
            );
        }
        foreach ($paypal_fields as $field) {
            $name = $field['name'];
            $title = $field['title'];
            $type = isset($field['type']) ? $field['type'] : 'text';

            register_setting('archreactor_options_group', $name);
            add_settings_field(
                $name,
                $title,
                array(__CLASS__, 'render_input'),
                'archreactor-settings-paypal',
                'archreactor_section_paypal',
                array('name' => $name, 'type' => $type)
            );
        }
    }

    // Section description
    public static function dashboard_callback() {
        echo '<p>Enter values to appear on the Member Dashboard </p>';
    }

    // Section description
    public static function paypal_callback() {
        echo '<p>Enter PayPal API credentials </p>';
    }

    // Render HTML for Text Area 1
    public static function render_input($args) {
        $name = $args['name'];
        $type = $args['type'];
        $value = get_option($name, '');
        echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" size="30" />';
    }

    /**
     * 3. Render the HTML form markup
     */
    public static function settings() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('archreactor_options_group');
                do_settings_sections('archreactor-settings-dashboard');
                do_settings_sections('archreactor-settings-paypal');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

}
