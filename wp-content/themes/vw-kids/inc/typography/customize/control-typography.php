<?php
/**
 * Typography control class.
 *
 * @since  1.0.0
 * @access public
 */

class VW_Kids_Control_Typography extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'typography';

	/**
	 * Array 
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $l10n = array();

	/**
	 * Set up our control.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @param  string  $id
	 * @param  array   $args
	 * @return void
	 */
	public function __construct( $manager, $id, $args = array() ) {

		// Let the parent class do its thing.
		parent::__construct( $manager, $id, $args );

		// Make sure we have labels.
		$this->l10n = wp_parse_args(
			$this->l10n,
			array(
				'color'       => esc_html__( 'Font Color', 'vw-kids' ),
				'family'      => esc_html__( 'Font Family', 'vw-kids' ),
				'size'        => esc_html__( 'Font Size',   'vw-kids' ),
				'weight'      => esc_html__( 'Font Weight', 'vw-kids' ),
				'style'       => esc_html__( 'Font Style',  'vw-kids' ),
				'line_height' => esc_html__( 'Line Height', 'vw-kids' ),
				'letter_spacing' => esc_html__( 'Letter Spacing', 'vw-kids' ),
			)
		);
	}

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script( 'vw-kids-ctypo-customize-controls' );
		wp_enqueue_style(  'vw-kids-ctypo-customize-controls' );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function to_json() {
		parent::to_json();

		// Loop through each of the settings and set up the data for it.
		foreach ( $this->settings as $setting_key => $setting_id ) {

			$this->json[ $setting_key ] = array(
				'link'  => $this->get_link( $setting_key ),
				'value' => $this->value( $setting_key ),
				'label' => isset( $this->l10n[ $setting_key ] ) ? $this->l10n[ $setting_key ] : ''
			);

			if ( 'family' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_families();

			elseif ( 'weight' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_weight_choices();

			elseif ( 'style' === $setting_key )
				$this->json[ $setting_key ]['choices'] = $this->get_font_style_choices();
		}
	}

	/**
	 * Underscore JS template to handle the control's output.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function content_template() { ?>

		<# if ( data.label ) { #>
			<span class="customize-control-title">{{ data.label }}</span>
		<# } #>

		<# if ( data.description ) { #>
			<span class="description customize-control-description">{{{ data.description }}}</span>
		<# } #>

		<ul>

		<# if ( data.family && data.family.choices ) { #>

			<li class="typography-font-family">

				<# if ( data.family.label ) { #>
					<span class="customize-control-title">{{ data.family.label }}</span>
				<# } #>

				<select {{{ data.family.link }}}>

					<# _.each( data.family.choices, function( label, choice ) { #>
						<option value="{{ choice }}" <# if ( choice === data.family.value ) { #> selected="selected" <# } #>>{{ label }}</option>
					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.weight && data.weight.choices ) { #>

			<li class="typography-font-weight">

				<# if ( data.weight.label ) { #>
					<span class="customize-control-title">{{ data.weight.label }}</span>
				<# } #>

				<select {{{ data.weight.link }}}>

					<# _.each( data.weight.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.weight.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.style && data.style.choices ) { #>

			<li class="typography-font-style">

				<# if ( data.style.label ) { #>
					<span class="customize-control-title">{{ data.style.label }}</span>
				<# } #>

				<select {{{ data.style.link }}}>

					<# _.each( data.style.choices, function( label, choice ) { #>

						<option value="{{ choice }}" <# if ( choice === data.style.value ) { #> selected="selected" <# } #>>{{ label }}</option>

					<# } ) #>

				</select>
			</li>
		<# } #>

		<# if ( data.size ) { #>

			<li class="typography-font-size">

				<# if ( data.size.label ) { #>
					<span class="customize-control-title">{{ data.size.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.size.link }}} value="{{ data.size.value }}" />

			</li>
		<# } #>

		<# if ( data.line_height ) { #>

			<li class="typography-line-height">

				<# if ( data.line_height.label ) { #>
					<span class="customize-control-title">{{ data.line_height.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.line_height.link }}} value="{{ data.line_height.value }}" />

			</li>
		<# } #>

		<# if ( data.letter_spacing ) { #>

			<li class="typography-letter-spacing">

				<# if ( data.letter_spacing.label ) { #>
					<span class="customize-control-title">{{ data.letter_spacing.label }} (px)</span>
				<# } #>

				<input type="number" min="1" {{{ data.letter_spacing.link }}} value="{{ data.letter_spacing.value }}" />

			</li>
		<# } #>

		</ul>
	<?php }

	/**
	 * Returns the available fonts.  Fonts should have available weights, styles, and subsets.
	 *
	 * @todo Integrate with Google fonts.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_fonts() { return array(); }

	/**
	 * Returns the available font families.
	 *
	 * @todo Pull families from `get_fonts()`.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	function get_font_families() {

		return array(
			'' => __( 'No Fonts', 'vw-kids' ),
        'Abril Fatface' => __( 'Abril Fatface', 'vw-kids' ),
        'Acme' => __( 'Acme', 'vw-kids' ),
        'Anton' => __( 'Anton', 'vw-kids' ),
        'Architects Daughter' => __( 'Architects Daughter', 'vw-kids' ),
        'Arimo' => __( 'Arimo', 'vw-kids' ),
        'Arsenal' => __( 'Arsenal', 'vw-kids' ),
        'Arvo' => __( 'Arvo', 'vw-kids' ),
        'Alegreya' => __( 'Alegreya', 'vw-kids' ),
        'Alfa Slab One' => __( 'Alfa Slab One', 'vw-kids' ),
        'Averia Serif Libre' => __( 'Averia Serif Libre', 'vw-kids' ),
        'Bangers' => __( 'Bangers', 'vw-kids' ),
        'Boogaloo' => __( 'Boogaloo', 'vw-kids' ),
        'Bad Script' => __( 'Bad Script', 'vw-kids' ),
        'Bitter' => __( 'Bitter', 'vw-kids' ),
        'Bree Serif' => __( 'Bree Serif', 'vw-kids' ),
        'BenchNine' => __( 'BenchNine', 'vw-kids' ),
        'Cabin' => __( 'Cabin', 'vw-kids' ),
        'Cardo' => __( 'Cardo', 'vw-kids' ),
        'Courgette' => __( 'Courgette', 'vw-kids' ),
        'Cherry Swash' => __( 'Cherry Swash', 'vw-kids' ),
        'Cormorant Garamond' => __( 'Cormorant Garamond', 'vw-kids' ),
        'Crimson Text' => __( 'Crimson Text', 'vw-kids' ),
        'Cuprum' => __( 'Cuprum', 'vw-kids' ),
        'Cookie' => __( 'Cookie', 'vw-kids' ),
        'Chewy' => __( 'Chewy', 'vw-kids' ),
        'Days One' => __( 'Days One', 'vw-kids' ),
        'Dosis' => __( 'Dosis', 'vw-kids' ),
        'Droid Sans' => __( 'Droid Sans', 'vw-kids' ),
        'Economica' => __( 'Economica', 'vw-kids' ),
        'Fredoka One' => __( 'Fredoka One', 'vw-kids' ),
        'Fjalla One' => __( 'Fjalla One', 'vw-kids' ),
        'Francois One' => __( 'Francois One', 'vw-kids' ),
        'Frank Ruhl Libre' => __( 'Frank Ruhl Libre', 'vw-kids' ),
        'Gloria Hallelujah' => __( 'Gloria Hallelujah', 'vw-kids' ),
        'Great Vibes' => __( 'Great Vibes', 'vw-kids' ),
        'Handlee' => __( 'Handlee', 'vw-kids' ),
        'Hammersmith One' => __( 'Hammersmith One', 'vw-kids' ),
        'Inconsolata' => __( 'Inconsolata', 'vw-kids' ),
        'Indie Flower' => __( 'Indie Flower', 'vw-kids' ),
        'IM Fell English SC' => __( 'IM Fell English SC', 'vw-kids' ),
        'Julius Sans One' => __( 'Julius Sans One', 'vw-kids' ),
        'Josefin Slab' => __( 'Josefin Slab', 'vw-kids' ),
        'Josefin Sans' => __( 'Josefin Sans', 'vw-kids' ),
        'Kanit' => __( 'Kanit', 'vw-kids' ),
        'Lobster' => __( 'Lobster', 'vw-kids' ),
        'Lato' => __( 'Lato', 'vw-kids' ),
        'Lora' => __( 'Lora', 'vw-kids' ),
        'Libre Baskerville' => __( 'Libre Baskerville', 'vw-kids' ),
        'Lobster Two' => __( 'Lobster Two', 'vw-kids' ),
        'Merriweather' => __( 'Merriweather', 'vw-kids' ),
        'Monda' => __( 'Monda', 'vw-kids' ),
        'Montserrat' => __( 'Montserrat', 'vw-kids' ),
        'Muli' => __( 'Muli', 'vw-kids' ),
        'Marck Script' => __( 'Marck Script', 'vw-kids' ),
        'Noto Serif' => __( 'Noto Serif', 'vw-kids' ),
        'Open Sans' => __( 'Open Sans', 'vw-kids' ),
        'Overpass' => __( 'Overpass', 'vw-kids' ),
        'Overpass Mono' => __( 'Overpass Mono', 'vw-kids' ),
        'Oxygen' => __( 'Oxygen', 'vw-kids' ),
        'Orbitron' => __( 'Orbitron', 'vw-kids' ),
        'Patua One' => __( 'Patua One', 'vw-kids' ),
        'Pacifico' => __( 'Pacifico', 'vw-kids' ),
        'Padauk' => __( 'Padauk', 'vw-kids' ),
        'Playball' => __( 'Playball', 'vw-kids' ),
        'Playfair Display' => __( 'Playfair Display', 'vw-kids' ),
        'PT Sans' => __( 'PT Sans', 'vw-kids' ),
        'Philosopher' => __( 'Philosopher', 'vw-kids' ),
        'Permanent Marker' => __( 'Permanent Marker', 'vw-kids' ),
        'Poiret One' => __( 'Poiret One', 'vw-kids' ),
        'Quicksand' => __( 'Quicksand', 'vw-kids' ),
        'Quattrocento Sans' => __( 'Quattrocento Sans', 'vw-kids' ),
        'Raleway' => __( 'Raleway', 'vw-kids' ),
        'Rubik' => __( 'Rubik', 'vw-kids' ),
        'Rokkitt' => __( 'Rokkitt', 'vw-kids' ),
        'Russo One' => __( 'Russo One', 'vw-kids' ),
        'Righteous' => __( 'Righteous', 'vw-kids' ),
        'Slabo' => __( 'Slabo', 'vw-kids' ),
        'Source Sans Pro' => __( 'Source Sans Pro', 'vw-kids' ),
        'Shadows Into Light Two' => __( 'Shadows Into Light Two', 'vw-kids'),
        'Shadows Into Light' => __( 'Shadows Into Light', 'vw-kids' ),
        'Sacramento' => __( 'Sacramento', 'vw-kids' ),
        'Shrikhand' => __( 'Shrikhand', 'vw-kids' ),
        'Tangerine' => __( 'Tangerine', 'vw-kids' ),
        'Ubuntu' => __( 'Ubuntu', 'vw-kids' ),
        'VT323' => __( 'VT323', 'vw-kids' ),
        'Varela Round' => __( 'Varela Round', 'vw-kids' ),
        'Vampiro One' => __( 'Vampiro One', 'vw-kids' ),
        'Vollkorn' => __( 'Vollkorn', 'vw-kids' ),
        'Volkhov' => __( 'Volkhov', 'vw-kids' ),
        'Yanone Kaffeesatz' => __( 'Yanone Kaffeesatz', 'vw-kids' )
		);
	}

	/**
	 * Returns the available font weights.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_weight_choices() {

		return array(
			'' => esc_html__( 'No Fonts weight', 'vw-kids' ),
			'100' => esc_html__( 'Thin',       'vw-kids' ),
			'300' => esc_html__( 'Light',      'vw-kids' ),
			'400' => esc_html__( 'Normal',     'vw-kids' ),
			'500' => esc_html__( 'Medium',     'vw-kids' ),
			'700' => esc_html__( 'Bold',       'vw-kids' ),
			'900' => esc_html__( 'Ultra Bold', 'vw-kids' ),
		);
	}

	/**
	 * Returns the available font styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function get_font_style_choices() {

		return array(
			'' => esc_html__( 'No Fonts Style', 'vw-kids' ),
			'normal'  => esc_html__( 'Normal', 'vw-kids' ),
			'italic'  => esc_html__( 'Italic', 'vw-kids' ),
			'oblique' => esc_html__( 'Oblique', 'vw-kids' )
		);
	}
}
