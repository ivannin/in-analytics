<?php
add_action( 'admin_menu', 'ina_add_admin_menu' );
add_action( 'admin_init', 'ina_settings_init' );


function ina_add_admin_menu(  ) { 

	add_menu_page( 'in-analytics', 'in-analytics', 'manage_options', 'in-analytics', 'ina_options_page' );

}


function ina_settings_init(  ) { 

	register_setting( 'pluginPage', 'ina_settings' );

	add_settings_section(
		'ina_pluginPage_section', 
		__( 'Your section description', 'in-analytics' ), 
		'ina_settings_section_callback', 
		'pluginPage'
	);

	add_settings_field( 
		'ina_checkbox_field_0', 
		__( 'Settings field description', 'in-analytics' ), 
		'ina_checkbox_field_0_render', 
		'pluginPage', 
		'ina_pluginPage_section' 
	);

	add_settings_field( 
		'ina_checkbox_field_1', 
		__( 'Settings field description', 'in-analytics' ), 
		'ina_checkbox_field_1_render', 
		'pluginPage', 
		'ina_pluginPage_section' 
	);

	add_settings_field( 
		'ina_checkbox_field_2', 
		__( 'Settings field description', 'in-analytics' ), 
		'ina_checkbox_field_2_render', 
		'pluginPage', 
		'ina_pluginPage_section' 
	);

	add_settings_field( 
		'ina_checkbox_field_3', 
		__( 'Settings field description', 'in-analytics' ), 
		'ina_checkbox_field_3_render', 
		'pluginPage', 
		'ina_pluginPage_section' 
	);


}


function ina_checkbox_field_0_render(  ) { 

	$options = get_option( 'ina_settings' );
	?>
	<input type='checkbox' name='ina_settings[ina_checkbox_field_0]' <?php checked( $options['ina_checkbox_field_0'], 1 ); ?> value='1'>
	<?php

}


function ina_checkbox_field_1_render(  ) { 

	$options = get_option( 'ina_settings' );
	?>
	<input type='checkbox' name='ina_settings[ina_checkbox_field_1]' <?php checked( $options['ina_checkbox_field_1'], 1 ); ?> value='1'>
	<?php

}


function ina_checkbox_field_2_render(  ) { 

	$options = get_option( 'ina_settings' );
	?>
	<input type='checkbox' name='ina_settings[ina_checkbox_field_2]' <?php checked( $options['ina_checkbox_field_2'], 1 ); ?> value='1'>
	<?php

}


function ina_checkbox_field_3_render(  ) { 

	$options = get_option( 'ina_settings' );
	?>
	<input type='checkbox' name='ina_settings[ina_checkbox_field_3]' <?php checked( $options['ina_checkbox_field_3'], 1 ); ?> value='1'>
	<?php

}


function ina_settings_section_callback(  ) { 

	echo __( 'This section description', 'in-analytics' );

}


function ina_options_page(  ) { 

	?>
	<form action='options.php' method='post'>

		<h2>in-analytics</h2>

		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	<?php

}

?>