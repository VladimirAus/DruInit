<?php

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Allows the profile to alter the site configuration form.
 */
function faultstart_form_install_configure_form_alter(&$form, $form_state) {
	
	faultstart_dpm($form);

	// Pre-populate the site name with the server name.
	$form['site_information']['site_name']['#default_value'] = $_SERVER['SERVER_NAME'];
	//$form['site_information']['site_email']['#default_value'] = '@ifactory.com.au';
	$form['admin_account']['account']['name']['#default_value'] = 'ifadmin';
	$form['server_settings']['site_default_country']['#default_value'] = 'AU';
	$form['server_settings']['date_default_timezone']['#default_value'] = 'Australia/Brisbane';
	  
}


function faultstart_form_install_settings_form_alter(&$form, $form_state) {
	// doesnt work
	$form['mysql']['database']['#default_value'] = 'ifd7demo_d7_installprof';
	$form['mysql']['username']['#default_value'] = 'ifd7demo_sqlu';

}

function faultstart_form_alter(&$form, &$form_state, $form_id) {

	//faultstart_dpm($form_id);
}

function faultstart_install_tasks_alter(&$tasks, $install_state) {
}

function faultstart_dpm($var) {
	print '<pre>';
	var_dump($var);
	print '</pre>';
}


function faultstart_install_settings_form($form, &$form_state, &$install_state) {
	faultstart_dpm($form);
}