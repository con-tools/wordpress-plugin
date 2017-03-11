<?php
class ConTrollSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $api_key;
    private $api_secret;
    private $settings;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'ConTroll Settings',
            'ConTroll',
            'manage_options',
            'controll-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->api_key = get_option('controll-api-key');
        $this->api_secret = get_option('controll-api-secret');
        $this->settings = get_option('controll-plugin-settings');
        ?>
        <div class="wrap">
            <h2>Controll Settings</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields('controll_option_group');
                do_settings_sections('controll-settings');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'controll_option_group', // Option group
            'controll-api-key' // Option name
        );

    	register_setting(
            'controll_option_group', // Option group
            'controll-api-secret' // Option name
        );

        register_setting(
            'controll_option_group', // Option group
            'controll-plugin-settings' // Option name
        );

        add_settings_section(
            'controll_settings_section_api', // ID
            'ConTroll API Settings', // Title
            array( $this, 'print_section_info_api' ), // Callback
            'controll-settings' // Page
        );

        add_settings_field(
            'api-key', // ID
            'ConTroll API Key', // Title
            array( $this, 'api_key_callback' ), // Callback
            'controll-settings', // Page
            'controll_settings_section_api' // Section
        );

        add_settings_field(
            'api-secret',
            'ConTroll API Secret',
            array( $this, 'api_secret_callback' ),
            'controll-settings',
            'controll_settings_section_api'
        );
	
        add_settings_section(
        		'controll_settings_section_local', // ID
        		'Site Page Configuration', // Title
        		array( $this, 'print_section_info_local' ), // Callback
        		'controll-settings' // Page
        		);
        
        add_settings_field(
        		'shopping_cart_url',
        		"Local URL for shopping cart page",
        		array( $this, 'shopping_cart_url_callback' ),
        		'controll-settings',
        		'controll_settings_section_local'
        		);
        
        add_settings_field(
        		'my_page_url',
        		"Local URL for user's page",
        		array( $this, 'my_page_url_callback' ),
        		'controll-settings',
        		'controll_settings_section_local'
        		);
        
        add_settings_field(
            'event_page_url',
            "Local URL for event page",
            array( $this, 'event_page_url_callback' ),
            'controll-settings',
            'controll_settings_section_local'
        );
        
        add_settings_section(
        		'controll_settings_section_registration', // ID
        		'User Registration Settings', // Title
        		array( $this, 'print_section_registration' ), // Callback
        		'controll-settings' // Page
        		);
        
        add_settings_field(
            'registration_active',
            "Open for registration",
            array( $this, 'registration_active_callback' ),
            'controll-settings',
            'controll_settings_section_registration'
        );
    }

    /**
     * Print the Section text
     */
    public function print_section_info_api()
    {
        print 'Enter the API key and secret values you received when creating the convention:';
    }

    /**
     * Print the Section text
     */
    public function print_section_info_local()
    {
        print 'To integrate ConTroll into the Wordpress site, you should create a few pages using the ';
        print 'ConTroll templates, and then set the fields below to the URLs (slugs) where these new pages ';
        print 'can be found.';
    }
    
    public function print_section_registration() {
    }

	/**
     * Get the settings option array and print one of its values
     */
    public function api_key_callback()
    {
        printf(
            '<input type="text" id="api-key" name="controll-api-key" value="%s" style="width: 50em;"/>',
            isset( $this->api_key ) ? esc_attr($this->api_key) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function api_secret_callback()
    {
        printf(
            '<input type="text" id="api-secret" name="controll-api-secret" value="%s" style="width: 20em;"/>',
            isset( $this->api_secret ) ? esc_attr($this->api_secret) : ''
        );
    }
    
    public function shopping_cart_url_callback()
    {
    	printf(
    			'<input type="text" id="shopping_cart_url" name="controll-plugin-settings[shopping_cart_url]" value="%s" style="width: 20em;"/>',
    			isset( $this->settings['shopping_cart_url'] ) ? esc_attr($this->settings['shopping_cart_url']) : ''
    			);
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function my_page_url_callback()
    {
        printf(
            '<input type="text" id="my_page_url" name="controll-plugin-settings[my_page_url]" value="%s" style="width: 20em;"/>',
            isset( $this->settings['my_page_url'] ) ? esc_attr($this->settings['my_page_url']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function event_page_url_callback()
    {
        printf(
            '<input type="text" id="event_page_url" name="controll-plugin-settings[event_page_url]" value="%s" style="width: 20em;"/>',
            isset( $this->settings['event_page_url'] ) ? esc_attr($this->settings['event_page_url']) : ''
        );
    }
    
    public function registration_active_callback() {
        $checked = isset( $this->settings['registration_active'] ) and ($this->settings['registration_active']);
        ?>
        <input type="checkbox" value="1" id="registration_active" name="controll-plugin-settings[registration_active]"
        <?php if ($checked):?>checked=checked<?php endif; ?> />
        <?php
    }
    
    public static function get_shopping_cart_url() {
    	$settings = get_option('controll-plugin-settings');
    	$url = '';
    	 if (array_key_exists('shopping_cart_url', $settings))
    	 	$url = $settings['shopping_cart_url'];
    	if (array_key_exists('my_page_url', $settings))
    		$url =  $settings['my_page_url'];
    	if (!empty($url) && $url[0] == '/') // host-absolute path? Lets make sure we're using full URLs
    		$url = home_url() . $url; // if you're using relative paths, I feel for you...
    	return $url;
    }
    
    public static function get_my_page_url()
    {
    	$settings = get_option('controll-plugin-settings');
    	$url = '';
    	if (array_key_exists('my_page_url', $settings))
    		$url = $settings['my_page_url'];
    	elseif (array_key_exists('shopping_cart_url', $settings))
    		$url = $settings['shopping_cart_url'];
   		if (!empty($url) && $url[0] == '/') // host-absolute path? Lets make sure we're using full URLs
   			$url = home_url() . $url; // if you're using relative paths, I feel for you...
   		return $url;
    }
    
    public static function get_event_page_url()
    {
        $url = get_option('controll-plugin-settings')['event_page_url'];
        if (!empty($url) && $url[0] == '/') // host-absolute path? Lets make sure we're using full URLs
        	$url = home_url() . $url; // if you're using relative paths, I feel for you...
       	return $url;
    }
    
    public static function is_registration_active() {
    	if (@$_REQUEST['debug-registration'])
    		return true;
    	return get_option('controll-plugin-settings')['registration_active'] ? true : false;
    }
}

if(is_admin())
    $controll_settings_page = new ConTrollSettingsPage();
