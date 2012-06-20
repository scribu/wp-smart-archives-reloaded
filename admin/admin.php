<?php

class SAR_Settings extends scbAdminPage {

	function setup() {
		$this->textdomain = 'smart-archives-reloaded';

		$this->args = array(
			'page_title' => __( 'Smart Archives Settings', $this->textdomain ),
			'menu_title' => __( 'Smart Archives', $this->textdomain ),
			'page_slug' => 'smart-archives'
		);
	}

	// Page methods
	function page_head() {
		$js_dev = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';

		wp_enqueue_script( 'sar-admin', $this->plugin_url . "admin/admin$js_dev.js", array( 'jquery' ), '1.9', true );
		echo $this->css_wrap( 'h3 {margin-bottom: 0 !important}' );
	}

	function validate( $args ) {
		return SAR_Core::validate_args( $args );
	}

	function page_content() {
		$tags = '';
		foreach ( SAR_Core::get_available_tags() as $tag )
			$tags .= "\n\t" . html( 'li', html( 'em', $tag ) );

		$default_date = __( 'Default', $this->textdomain ) . ': ' . get_option( 'date_format' ) . ' ( ' . date_i18n( get_option( 'date_format' ) ) . ' )';

		$data = array();

		$formdata = $this->options->get();
		$formdata['exclude_cat'] = implode( ', ', (array) $formdata['exclude_cat'] );

		$data['sections'][] = array(
			'title' => __( 'General settings', $this->textdomain ),
			'id' => 'general',
			'rows' => scbForms::table( array(
				array(
					'title' => __( 'Exclude Categories by ID', $this->textdomain ),
					'desc' => __( '(comma separated)', $this->textdomain ),
					'type' => 'text',
					'name' => 'exclude_cat',
				),

				array(
					'title' => __( 'Format', $this->textdomain ),
					'type' => 'radio',
					'name' => 'format',
					'value' => array( 'block', 'list', 'both', 'fancy' ),
					'desc' => array(
						__( 'block', $this->textdomain ),
						__( 'list', $this->textdomain ),
						__( 'both', $this->textdomain ),
						__( 'fancy', $this->textdomain ),
					)
				),
			), $formdata )
		);

		$data['sections'][] = array(
			'title' => __( 'Specific settings', $this->textdomain ),
			'id' => 'specific',
			'rows' => scbForms::table( array(
				array(
					'title' => __( 'List format', $this->textdomain ),
					'desc' => html( 'p', __( 'Available substitution tags', $this->textdomain ) )
					.html( 'ul', $tags ),
						'type' => 'text',
						'name' => 'list_format',
					),

					array(
						'title' => sprintf( __( '%s format', $this->textdomain ), '%date%' ),
						'desc' => html( 'p', $default_date )
						.html( 'p',
							html( 'em', __( 'See available date formatting characters <a href="http://php.net/date" target="_blank">here</a>.'
							, $this->textdomain ) )
						),
						'type' => 'text',
						'name' => 'date_format',
					),

					array(
						'title' => __( 'Month names', $this->textdomain ),
						'type' => 'radio',
						'name' => 'month_format',
						'value' => array( 'numeric', 'short', 'long' ),
						'desc' => array(
							__( 'numeric', $this->textdomain ),
							__( 'short', $this->textdomain ),
							__( 'long', $this->textdomain ),
						)
					),

					array(
						'title' => __( 'Use anchor links in block', $this->textdomain ),
						'desc' => __( 'The month links in the block will point to the month links in the list', $this->textdomain ),
						'type' => 'checkbox',
						'name' => 'anchors',
					),
				), $formdata )
			);

		$output = SAR_Core::mustache_render( dirname( __FILE__ ) . '/admin.html', $data );

		echo $this->form_wrap( $output );
	}
}

