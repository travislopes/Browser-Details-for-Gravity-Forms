<?php
	
GFForms::include_addon_framework();

class GFBrowserDetails extends GFAddOn {
	
	protected $_version = GF_BROWSERDETAILS_VERSION;
	protected $_min_gravityforms_version = '1.9.11.6';
	protected $_slug = 'browser-details-for-gravity-forms';
	protected $_path = 'browser-details-for-gravity-forms/browserdetails.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://travislop.es';
	protected $_title = 'Gravity Forms Browser Details';
	protected $_short_title = 'Browser Details';
	private static $_instance = null;

	/**
	 * Get instance of this class.
	 * 
	 * @access public
	 * @static
	 * @return $_instance
	 */
	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new self;

		return self::$_instance;
		
	}
	
	/**
	 * Register needed hooks.
	 * 
	 * @access public
	 * @return void
	 */
	public function init() {
		
		parent::init_frontend();

		add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
		
		add_action( 'gform_entry_created', array( $this, 'save_browser_details' ), 10, 2 );
		
		add_filter( 'gform_custom_merge_tags', array( $this, 'register_custom_merge_tag' ), 10, 4 );
		
		add_filter( 'gform_replace_merge_tags', array( $this, 'replace_merge_tag' ), 10, 7 );
		
		add_filter( 'gform_form_settings', array( $this, 'add_form_settings_fields' ), 10, 2 );
	
		add_filter( 'gform_pre_form_settings_save', array( $this, 'save_form_settings_fields' ), 10, 1 );
	
	}

	/**
	 * Register needed scripts.
	 * 
	 * @access public
	 * @return array $scripts
	 */
	public function scripts() {
		
		$scripts = array(
			array(
				'handle'    => 'gform_browserdetails_flashdetect',
				'src'       => $this->get_base_url() . '/js/flashdetect.min.js',
				'version'   => $this->_version,
			),
			array(
				'handle'    => 'gform_browserdetails_whichbrowser',
				'src'       => $this->get_base_url() . '/includes/whichbrowser/detect.php',
				'version'   => $this->_version,
			),
			array(
				'handle'    => 'gform_browserdetails',
				'src'       => $this->get_base_url() . '/js/frontend.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery', 'gform_browserdetails_flashdetect', 'gform_browserdetails_whichbrowser' ),
				'in_footer' => false,
				'enqueue'   => array(
					array( $this, 'has_browser_details_enabled' )
				)
			)
		);
		
		return array_merge( parent::scripts(), $scripts );
		
	}

	/**
	 * Prepare Browser Details Javascript for front-end form.
	 * 
	 * @access public
	 * @param array $form
	 * @param array $field_values
	 * @param bool $is_ajax
	 * @return void
	 */
	public function register_init_scripts( $form, $field_values, $is_ajax ) {

		if ( ! $this->has_browser_details_enabled( $form ) ) {
			return;
		}

		$args = array(
			'displayDetails' => rgar( $form, 'browserDetailsDisplay' ),
			'formId'         => rgar( $form, 'id' ),
			'ipAddress'      => rgar( $_SERVER, 'REMOTE_ADDR' ),
			'labels'         => $this->get_detail_labels()
		);

		$script = 'new GFBrowserDetails( ' . json_encode( $args ) . ' );';
		GFFormDisplay::add_init_script( $form['id'], 'browserdetails', GFFormDisplay::ON_PAGE_RENDER, $script );

	}

	/**
	 * Get browser details.
	 * 
	 * @access public
	 * @param int $entry_id
	 * @return array $browser_details
	 */
	public function get_browser_details( $entry_id ) {
		
		$browser_details = array();
		$details         = gform_get_meta( $entry_id, 'browser_details' );
		$labels          = $this->get_detail_labels();
		
		if ( ! $details ) {
			return array();
		}
		
		$details = json_decode( $details, true );
		
		foreach ( $details as $key => $value ) {
			
			$label = $labels[ $key ];
			
			if ( is_array( $value ) ) {
				$value = $value['string'];
			} else if ( is_bool( $value ) ) {
				$value = $value ? esc_html__( 'Enabled', 'browser-details-for-gravity-forms' ) : esc_html__( 'Disabled', 'browser-details-for-gravity-forms' );
			}
			
			$browser_details[ $label ] = $value;
			
		}
		
		return $browser_details;
		
	}
	
	/**
	 * Save browser details to entry.
	 * 
	 * @access public
	 * @param array $entry
	 * @param array $form
	 * @return void
	 */
	public function save_browser_details( $entry, $form ) {
		
		/* Get browser details. */
		$details = rgpost( 'gform_browserdetails' );
		$labels  = $this->get_detail_labels();
		
		/* Save browser details to entry meta. */
		gform_update_meta( $entry['id'], 'browser_details', $details );
		
		/* Decode the browser details. */
		$details = json_decode( $details, true );
		
		/* Prepare note. */
		$note = '';
		
		foreach ( $details as $detail => $value ) {
			
			$note .= $labels[ $detail ] . ': ';
			
			if ( is_array( $value ) ) {
				$note .= $value['string'];
			} else if ( is_bool( $value ) ) {
				$note .= $value ? esc_html__( 'Enabled', 'browser-details-for-gravity-forms' ) : esc_html__( 'Disabled', 'browser-details-for-gravity-forms' );
			} else {
				$note .= $value;
			}
			
			$note .= "\r\n";
			
		}
		
		/* Add note. */
		$this->add_note( $entry['id'], $note );
		
	}

	/**
	 * Add custom merge tag.
	 * 
	 * @access public
	 * @param array $merge_tags
	 * @param int $form_id
	 * @param array $fields
	 * @param int $element_id
	 * @return array $merge_tags
	 */
	public function register_custom_merge_tag( $merge_tags, $form_id, $fields, $element_id ) {
		
		$form = GFAPI::get_form( $form_id );
		
		if ( ! $this->has_browser_details_enabled( $form ) ) {
			return $merge_tags;
		}
		
		$merge_tags[] = array(
			'label' => esc_html__( 'Browser Details', 'browser-details-for-gravity-forms' ),
			'tag'   => '{browser_details}'
		);
		
		return $merge_tags;
		
	}

	/**
	 * Replace custom merge tag.
	 * 
	 * @access public
	 * @param string $text
	 * @param array $form
	 * @param array $entry
	 * @param bool $url_encode
	 * @param bool $esc_html
	 * @param bool $nl2br
	 * @param string $format
	 * @return void
	 */
	public function replace_merge_tag( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		
		/* If merge tag is not in text, return. */
		if ( strpos( $text, '{browser_details}' ) === false ) {
			return $text;
		}
		
		/* Get browser details. */
		$details = $this->get_browser_details( $entry['id'] );
		
		/* If there are no browser details for this entry, return text. */
		if ( empty( $details ) ) {
			return str_replace( '{browser_details}', '', $text );
		}
		
		/* Prepare replacement text. */
		$replace_text = '';
		
		if ( $format == 'html' ) {
			$replace_text  = '<table width="99%" border="0" cellpadding="1" cellspacing="0" bgcolor="#EAEAEA"><tr><td>';
			$replace_text .= '<table width="100%" border="0" cellpadding="5" cellspacing="0" bgcolor="#FFFFFF">';
		}
		
		foreach ( $details as $label => $value ) {
			
			if ( $format == 'html' ) {
				$replace_text .= sprintf(
					'<tr bgcolor="%3$s">
	                    <td colspan="2">
	                        <font style="font-family: sans-serif; font-size:12px;"><strong>%1$s</strong></font>
	                    </td>
	               </tr>
	               <tr bgcolor="%4$s">
	                    <td width="20">&nbsp;</td>
	                    <td>
	                        <font style="font-family: sans-serif; font-size:12px;">%2$s</font>
	                    </td>
	               </tr>
	               ', $label, $value, esc_attr( apply_filters( 'gform_email_background_color_label', '#EAF2FA', $field, $lead ) ), esc_attr( apply_filters( 'gform_email_background_color_data', '#FFFFFF', $field, $lead ) )
				);
			} else {
				$replace_text .= $label .': '. $value . "\r\n";
			}
			
		}
		
		if ( $format == 'html' ) {
			$field_data .= '</table></td></tr></table>';
		}
		
		return str_replace( '{browser_details}', $replace_text, $text );
		
	}

	/**
	 * Add form settings fields.
	 * 
	 * @access public
	 * @param array $settings
	 * @param array $form
	 * @return array $settings
	 */
	public function add_form_settings_fields( $settings, $form ) {
		
		$settings_key     = esc_html__( 'Browser Details', 'browser-details-for-gravity-forms' );
		$details_settings = array();
		
		$details_settings['browserDetailsEnable']  = '<tr>';
		$details_settings['browserDetailsEnable'] .= '<th><label for="browserDetailsEnable">' . esc_html__( 'Enable Browser Details', 'browser-details-for-gravity-forms' ) . '</label></th>';
		$details_settings['browserDetailsEnable'] .= '<td>';
		$details_settings['browserDetailsEnable'] .= '<input type="checkbox" id="browserDetailsEnable" name="browserDetailsEnable" value="1" ' . checked( '1', rgar( $form, 'browserDetailsEnable' ), false ) . ' />';
		$details_settings['browserDetailsEnable'] .= '<label for="browserDetailsEnable">' . esc_html__( 'Enable browser details for form', 'browser-details-for-gravity-forms' ) . '</label>';
		$details_settings['browserDetailsEnable'] .= '</td>';
		$details_settings['browserDetailsEnable'] .= '</tr>';

		$details_settings['browserDetailsDisplay']  = '<tr>';
		$details_settings['browserDetailsDisplay'] .= '<th><label for="browserDetailsDisplay">' . esc_html__( 'Display Browser Details', 'browser-details-for-gravity-forms' ) . '</label></th>';
		$details_settings['browserDetailsDisplay'] .= '<td>';
		$details_settings['browserDetailsDisplay'] .= '<input type="checkbox" id="browserDetailsDisplay" name="browserDetailsDisplay" value="1" ' . checked( '1', rgar( $form, 'browserDetailsDisplay' ), false ) . ' />';
		$details_settings['browserDetailsDisplay'] .= '<label for="browserDetailsDisplay">' . esc_html__( 'Display browser details below form fields', 'browser-details-for-gravity-forms' ) . '</label>';
		$details_settings['browserDetailsDisplay'] .= '</td>';
		$details_settings['browserDetailsDisplay'] .= '</tr>';
		
		$settings[ $settings_key ] = $details_settings;
		
		return $settings;
		
	}

	/**
	 * Save form settings fields.
	 * 
	 * @access public
	 * @param array $form
	 * @return array $form
	 */
	public function save_form_settings_fields( $form ) {
		
		$form['browserDetailsEnable']  = rgpost( 'browserDetailsEnable' );
		$form['browserDetailsDisplay'] = rgpost( 'browserDetailsDisplay' );
		
		return $form;
		
	}

	/**
	 * Checks if form has browser details enabled.
	 * 
	 * @access public
	 * @param array $form
	 * @return bool
	 */
	public function has_browser_details_enabled( $form ) {
		
		return $form && rgar( $form, 'browserDetailsEnable' );
		
	}

	/**
	 * Get labels for browser details.
	 * 
	 * @access public
	 * @return array $labels
	 */
	public function get_detail_labels() {
		
		return array(
			'operatingSystem'   => esc_html__( 'Operating System', 'browser-details-for-gravity-forms' ),
			'browser'           => esc_html__( 'Web Browser', 'browser-details-for-gravity-forms' ),
			'browserResolution' => esc_html__( 'Browser Resolution', 'browser-details-for-gravity-forms' ),
			'screenResolution'  => esc_html__( 'Screen Resolution', 'browser-details-for-gravity-forms' ),
			'colorDepth'        => esc_html__( 'Color Depth', 'browser-details-for-gravity-forms' ),
			'ip'                => esc_html__( 'IP Address', 'browser-details-for-gravity-forms' ),
			'cookies'           => esc_html__( 'Cookies', 'browser-details-for-gravity-forms' ),
			'flashVersion'      => esc_html__( 'Flash Version', 'browser-details-for-gravity-forms' ),
			'javascript'        => esc_html__( 'Javascript', 'browser-details-for-gravity-forms' )
		);
		
	}

}
