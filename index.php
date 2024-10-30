<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://hardweb.it/
 * @since             1.0.0
 * @package           hw-wp-status-manager
 *
 * @wordpress-plugin
 * Plugin Name:       WP Custom Status Manager
 * Description:       With this lighwave plugin you can add custom post statuses to your WP post type.
 * Version:           1.0.5
 * Author:            Hardweb.it
 * Author URI:        https://hardweb.it/
 * Donate link: https://www.paypal.com/donate?hosted_button_id=DEFQGNU2RNQ4Y
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hw-wp-status-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('HW_WP_STATUS_MANAGER_VER', '1.0.4');

/**
 * Load plugin textdomain.
 */
add_action( 'plugins_loaded', 'hw_wp_status_manager_load_textdomain' );
function hw_wp_status_manager_load_textdomain() {
  load_plugin_textdomain( 'hw-wp-status-manager', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}

/* Return the plugin's table name */
function hw_wpsm_table_name() {
global $wpdb;
$table_name = $wpdb->prefix.'hw_wpsm';
return $table_name;
}

/* PLUGIN ACTIVATION */
register_activation_hook( __FILE__, 'hw_wp_status_manager_activation' );
function hw_wp_status_manager_activation() {
//create table
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();
$table_name = hw_wpsm_table_name();
	//check if plugin table exists, if not create it
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE $table_name (
		  id int(3) NOT NULL AUTO_INCREMENT,
		  post_type_slug varchar(64) NOT NULL,
		  statuses text NOT NULL,
		  options varchar(512) NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
/* CONVERT DATA TO NEW VERSION */
	if (version_compare(get_option('hw_wp_status_manager_ver'), '1.0.4', '<')) {
		$statuses = $wpdb->get_results("SELECT id, statuses, options FROM $table_name", ARRAY_A);
		foreach ($statuses as $status) {
			$id = $status['id'];
			$statuses_field = serialize(json_decode($status['statuses'], true));
			$options = serialize(json_decode($status['options'], true));
			print_r($statuses_field);
			$update_options = $wpdb->query("UPDATE $table_name SET statuses = '$statuses_field', options = '$options' WHERE id = '$id'");
		}
	}
	
/* SET NEW PLUGIN VERSION */
add_option('hw_wp_status_manager_ver', HW_WP_STATUS_MANAGER_VER); 
update_option('hw_wp_status_manager_ver', HW_WP_STATUS_MANAGER_VER);
}

/* Add to the Admin menu */
add_action('admin_menu', 'hw_wp_status_manager_menu');
function hw_wp_status_manager_menu(){
        add_menu_page( 'WP Custom Status Manager', 'WP Custom Status Manager', 'manage_options', 'hw-wp-status-manager', 'hw_wp_status_manager_admin_page', 'dashicons-flag');
}

/* The plugin's options page */
function hw_wp_status_manager_admin_page(){
/* enqueue scripts required by modal, only in this page */
wp_enqueue_script( 'jquery-ui-dialog' );
wp_enqueue_style( 'wp-jquery-ui-dialog' );

	$ajax_nonce = wp_create_nonce( "hw-wp-status-manager" );
	echo "<style>a{cursor:pointer;} .hidden-cpt{min-height: 30px;}</style>";
	echo "<div class='wrap'>";
	echo "<h1>".__('WP Custom Status Manager', 'hw-wp-status-manager')."</h1><h4>".__('Click the Post Type to add a new status','hw-wp-status-manager')."</h4>";
	echo "<div class='notice notice-info is-dismissable'>
			<p>". __( 'Hi, here you can create your custom post status for any post type.', 'hw-wp-status-manager' ) . "</p>
		</div>
	</div>";	
	echo "
	<table class='wp-list-table widefat fixed striped pages'>
		<thead>
			<tr>
				<th scope='col' id='hw-wpsm-cpt' class='manage-column column-title column-primary'><span>". __('Post Type List', 'hw-wp-status-manager') . "</span></th>
				<th scope='col' id='hw-wpsm-hcs' class='manage-column column-title column-primary'><span>". __('Hidden Core Status', 'hw-wp-status-manager') . "</span></th>
				<th scope='col' id='hw-wpsm-cs' class='manage-column column-title column-primary'><span>". __('Registered Custom Status(es)', 'hw-wp-status-manager') . "</span></th>
			</tr>
		</thead>
	<tbody id='the-list' class='ui-sortable'>";
	$post_types = hw_wp_status_manager_get_post_types();
	foreach ($post_types as $post_type) {
		echo "
		<tr>
			<td><a href='#' id='$post_type' style='text-transform:capitalize;' class='open-options' data-slug='$post_type'>$post_type</a></td>";
		//get current post type options
		$options = hw_wp_status_manager_get_cpt_options($post_type);
		$hidden_core_status = $options['hide_core_statuses'];
		$yes = __('Yes', 'hw-wp-status-manager');
		$no = __('No', 'hw-wp-status-manager');
		$hidden_core_status_text = ($hidden_core_status == 0) ? $no : $yes;
		echo "
			<td><a class='set-hide-core-status' data-cpt='$post_type' data-value='$hidden_core_status'>$hidden_core_status_text</td>
			<td>
				<ul class='list-statuses-$post_type'>";
	$statuses_list = hw_wp_status_manager_get_custom_statuses($post_type);
	$count_statuses = 0;
	if (is_array($statuses_list)) {
		foreach($statuses_list as $status_slug=>$status_options) {
			echo "<li><a class='edit-status' data-slug='$status_slug' data-cpt='$post_type'>".$status_options['label_singular']."</a></li>";
			$count_statuses++;
		}
	}
	if ($count_statuses == 0) {
		echo "<li>".__('No custom Status yet', 'hw-wp-status-manager')."</li>";
	}
		echo "	</ul>
			</td>
		</tr>";
	}
	echo "
	</tbody>
	</table>
	<div style='display:block;width:100%;height:40px'></div>
	<h4>".__('That\'s all! All options are already saved. That\'s why there isn\'t the "save" button here', 'hw-wp-status-manager')."</h4>
	<h3>".__('Need to clean all?', 'hw-wp-status-manager')."</h3>
	<h4>".__('Do a backup before reset! Be careful and use this option only to clean your WordPress installation. Deletion process is not reversable and will perform:', 'hw-wp-status-manager')."</h4>
	<ul>
		<li>- ".__('Deletion of plugin options', 'hw-wp-status-manager')."</li>
		<li>- ".__('Deletion of "hw_wpsm" table from your database and its contents (all custom statuses and their relative options)', 'hw-wp-status-manager')."</li>
		<li>- ".__('Deactivation of plugin and redirect to your Plugins admin page', 'hw-wp-status-manager')."</li>
	</ul>
	<h4>".__('Once you have perform the reset, all post previously set with a custom status will not be deleted but you\'ll not be longer to show them inside the posts type list.', 'hw-wp-status-manager')."</h4>
	<button id='hw-wpsm-reset' class='button button-primary button-large'>".__('I understand, Reset all options and custom statuses!', 'hw-wp-status-manager')."</button>
	<div id='options' class='hidden' style='max-width:90%;' data-slug=''>
	<h4>".__('Status name', 'hw-wp-status-manager')."</h4>
	
	<label for='hw-wpsm-singular'>".__('Label singular', 'hw-wp-status-manager').":</label> <input type='text' id='hw-wpsm-singular' placeholder='".__('example: Box', 'hw-wp-status-manager')."' /><br>
	<label for='hw-wpsm-plural'>".__('Label plural', 'hw-wp-status-manager').":</label> <input type='text' id='hw-wpsm-plural' placeholder='".__('example: Boxes', 'hw-wp-status-manager')."' /><br>
	<h5>Status Options<br>
	<a href='https://developer.wordpress.org/reference/functions/register_post_status/' target='_blank' title='".__('Learn more about options', 'hw-wp-status-manager')."'>".__('Learn more about options', 'hw-wp-status-manager')."</a> (".__('Leave default if you don\'t know how to set these options. They will work fine on the most part of cases.','hw-wp-status-manager').")</h5>
	<input type='checkbox' id='hw-wpsm-public' checked /><label for='hw-wpsm-public'>".__('Public', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-admin-all-list' checked /><label for='hw-wpsm-admin-all-list'>".__('Admin all list', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-admin-status-list' checked /><label for='hw-wpsm-admin-status-list'>".__('Admin Status list', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-exclude-from-search' /><label for='hw-wpsm-exclude-from-search'>".__('Exclude from search', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-builtin' /><label for='hw-wpsm-builtin'>".__('Builtin', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-internal' /><label for='hw-wpsm-internal'>".__('Internal', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-protected' /><label for='hw-wpsm-protected'>".__('Protected', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-private' /><label for='hw-wpsm-private'>".__('Private', 'hw-wp-status-manager')."</label><br>
	<input type='checkbox' id='hw-wpsm-publicly-queryable' /><label for='hw-wpsm-publicly-queryable'>".__('Publicly queryable', 'hw-wp-status-manager')."</label><br>
	<br>
	<input type='submit' id='hw-wpsm-save' value='".__('Save', 'hw-wp-status-manager')."' class='button button-primary' />
	<input type='submit' id='hw-wpsm-delete' value='".__('Delete this Status', 'hw-wp-status-manager')."' class='button' style='visibility:hidden;background-color:#dc3333;color:#fff;' data-slug='' data-cpt='' />
	</div>";
	
$echo_debug_js = (WP_DEBUG) ? 1 : 0;	
echo "
	<script>
	jQuery.noConflict()(function($){
		\"use strict\";
	  // initalise the dialog
	  
	  var debug = $echo_debug_js;
	  
	  $('#options').dialog({
		title: '',
		dialogClass: 'wp-dialog',
		autoOpen: false,
		draggable: false,
		width: '80%',
		modal: true,
		resizable: true,
		closeOnEscape: false,
		position: {
		  my: \"center\",
		  at: \"center\",
		  of: window
		},
		open: function () {
		  // close dialog by clicking the overlay behind it
		  $('.ui-widget-overlay').bind('click', function(){
			$('#options').dialog('close');
		  })
		},
		create: function () {
		  // style fix for WordPress admin
		  $('.ui-dialog-titlebar-close').addClass('ui-button');
		},
	  });
	  // bind a button or a link to open the dialog
	  $('a.open-options').click(function(e) {
		e.preventDefault();
		var selected_cpt = $(this).data('slug');
		$('#options').dialog('open');
		$.fn.set_dialog_title(selected_cpt);
		$.fn.refresh_data(selected_cpt);
	  });
	  
	$.fn.set_dialog_title = function(selected_cpt) {
		var title = '".addslashes(__('Status for the post type:','hw-wp-status-manager'))." ' + selected_cpt;
		$('#options').data('slug', selected_cpt);
		$('.ui-dialog-title').text(title);			
	};
	
	$.fn.refresh_data = function(selected_cpt) {
		$('#hw-wpsm-delete').css('visibility', 'hidden');
		$('#hw-wpsm-singular').prop('disabled', false);
		$('.list-statuses-'+selected_cpt).empty();
		$('#hw-wpsm-singular').val('');
		$('#hw-wpsm-plural').val('');
		$('#hw-wpsm-public').prop('checked', true);
		$('#hw-wpsm-admin-all-list').prop('checked', true);
		$('#hw-wpsm-admin-status-list').prop('checked', true);
		$('#hw-wpsm-exclude-from-search').prop('checked', false);
		$('#hw-wpsm-builtin').prop('checked', false);
		$('#hw-wpsm-internal').prop('checked', false);
		$('#hw-wpsm-protected').prop('checked', false);
		$('#hw-wpsm-private').prop('checked', false);
		$('#hw-wpsm-publicly-queryable').prop('checked', false);
		$('.list-statuses-'+selected_cpt).html('<li>".addslashes(__('Loading...', 'hw-wp-status-manager'))."</li>');
		$.ajax({
			url:ajaxurl,
			type: 'post',
			data: {
				action:'hw_wpsm_get_statuses_list',
				security: '$ajax_nonce',
				'cpt_slug': selected_cpt
				}, success: function( response ) {
					if (debug) { console.log( response ); }
					var response_obj = jQuery.parseJSON( response );
					$('.list-statuses-'+selected_cpt).html(response_obj.html);
				},
				error: function(errorThrown){
					if (debug) { console.log( errorThrown ); }
					return false;
				}
		});
	};
	
	$('.ui-dialog-titlebar-close').click( function() {
		var selected_cpt = $('#options').data('slug');
		$.fn.refresh_data(selected_cpt);
	});
	
	$('#hw-wpsm-delete').click( function(){
		var status_slug = $(this).data('slug');
		var selected_cpt = $(this).data('cpt');
		var delete_text = '". addslashes(__('Delete the status:', 'hw-wp-status-manager')) ." ' + status_slug + '?';
		if (confirm(delete_text)) {
			$.ajax({
				url:ajaxurl,
				type: 'post',
				data: {
					action:'hw_wpsm_delete_data',
					security: '$ajax_nonce',
					'cpt_slug': selected_cpt,
					'status_slug': status_slug
					}, success: function( response ) {
						if (debug) { console.log( 'delete '+status_slug+' ok' ); }
						$.fn.refresh_data(selected_cpt);
						$('.ui-dialog-titlebar-close').trigger('click');
					},
					error: function(errorThrown){
						if (debug) { console.log( errorThrown ); }
						return false;
					}
			});
		}
	});

	$('body').on('click', '.edit-status', function() {
		var status_slug = $(this).data('slug');
		if (debug) { console.log('edit status: ' + status_slug); }
		var selected_cpt = $(this).data('cpt');
		$('#hw-wpsm-delete').css('visibility', 'visible');
		$('#hw-wpsm-delete').data('slug', status_slug);
		$('#hw-wpsm-delete').data('cpt', selected_cpt);
		$('#options').data('slug', selected_cpt);
		$('#options').dialog('open');
		var title = '".addslashes(__('Loading options for the status:','hw-wp-status-manager'))." ' + status_slug;
		$('.ui-dialog-title').text(title);
		$.ajax({
			url:ajaxurl,
			type: 'post',
			data: {
				action:'hw_wpsm_get_status_data',
				security: '$ajax_nonce',
				'cpt_slug': selected_cpt,
				'status_slug': status_slug
				}, success: function( response ) {
					if (debug) { console.log( response ); }
					title = '".addslashes(__('Edit the status:','hw-wp-status-manager'))." ' + status_slug;
					$('.ui-dialog-title').text(title);
					var response_obj = jQuery.parseJSON( response );
					$('#hw-wpsm-singular').val(response_obj.label_singular);
					$('#hw-wpsm-singular').attr('title', '".addslashes(__('You cannot modify the singular label, since WP would create a new status-slug', 'hw-wp-status-manager'))."');
					$('#hw-wpsm-singular').prop('disabled', true);
					$('#hw-wpsm-plural').val(response_obj.label_plural);
					$('input[type=\"checkbox\"]').each( function() {
						$(this).prop('checked', false);
					});
					if (response_obj.opt_public == 1) { $('#hw-wpsm-public').prop('checked', true); }
					if (response_obj.opt_show_in_admin_all_list == 1) { $('#hw-wpsm-admin-all-list').prop('checked', true); }
					if (response_obj.opt_show_in_admin_status_list == 1) { $('#hw-wpsm-admin-status-list').prop('checked', true); }
					if (response_obj.opt_exclude_from_search == 1) { $('#hw-wpsm-exclude-from-search').prop('checked', true); }
					if (response_obj.opt_builtin == 1) { $('#hw-wpsm-builtin').prop('checked', true); }
					if (response_obj.opt_internal == 1) { $('#hw-wpsm-internal').prop('checked', true); }
					if (response_obj.opt_protected == 1) { $('#hw-wpsm-protected').prop('checked', true); }
					if (response_obj.opt_private == 1) { $('#hw-wpsm-private').prop('checked', true); }
					if (response_obj.opt_publicly_queryable == 1) { $('#hw-wpsm-publicly-queryable').prop('checked', true); }
				},
				error: function(errorThrown){
					if (debug) { console.log( errorThrown ); }
					return false;
				}
		});
	});
	
	$('.set-hide-core-status').click( function() {
		var selected_object = $(this);
		var selected_cpt = $(this).data('cpt');
		if (debug) { console.log('change options for cpt: ' + selected_cpt); }
		var value = $(this).data('value');
		var new_text;
		var new_value;
		//reverse the status
		var yes = '".addslashes(__('Yes', 'hw-wp-status-manager'))."';
		var no = '".addslashes(__('No', 'hw-wp-status-manager'))."';
		if (value == 0) {
			new_text = yes;
			new_value = 1;
		} else {
			new_text = no;
			new_value = 0;
		}
		$(selected_object).text('".addslashes(__('Loading...', 'hw-wp-status-manager'))."');
		$.ajax({
			url:ajaxurl,
			type: 'post',
			data: {
				action:'hw_wpsm_save_data_options',
				security: '$ajax_nonce',
				'cpt_slug': selected_cpt,
				'hide_core_statuses': value
				}, success: function( response ) {
					if (debug) { console.log( response ); }
					var response_obj = jQuery.parseJSON( response );
					$(selected_object).text(new_text);
					$(selected_object).data('value', new_value);
				},
				error: function(errorThrown){
					if (debug) { console.log( errorThrown ); }
					return false;
				}
		});
	});
	
	$('#hw-wpsm-save').on('click', function() {
		var hw_wpsm_singular = $('#hw-wpsm-singular').val();
		var hw_wpsm_plural = $('#hw-wpsm-plural').val();
		var hw_wpsm_public = 0; if ($('#hw-wpsm-public').is(':checked')){hw_wpsm_public=1;}
		var hw_wpsm_admin_all_list = 0; if ($('#hw-wpsm-admin-all-list').is(':checked')){hw_wpsm_admin_all_list=1;}
		var hw_wpsm_admin_status_list = 0; if ($('#hw-wpsm-admin-status-list').is(':checked')){hw_wpsm_admin_status_list=1;}
		var hw_wpsm_exclude_from_search = 0; if ($('#hw-wpsm-exclude-from-search').is(':checked')){hw_wpsm_exclude_from_search=1;}
		var hw_wpsm_builtin = 0; if ($('#hw-wpsm-builtin').is(':checked')){hw_wpsm_builtin=1;}
		var hw_wpsm_internal = 0; if ($('#hw-wpsm-internal').is(':checked')){hw_wpsm_internal=1;}
		var hw_wpsm_protected = 0; if ($('#hw-wpsm-protected').is(':checked')){hw_wpsm_protected=1;}
		var hw_wpsm_private = 0; if ($('#hw-wpsm-private').is(':checked')){hw_wpsm_private=1;}
		var hw_wpsm_publicly_queryable = 0; if ($('#hw-wpsm-publicly-queryable').is(':checked')){hw_wpsm_publicly_queryable=1;}
		var cpt_slug = $('#options').data('slug');
		$(this).attr('disabled', true);
		var saving_text = '".addslashes(__('Saving...', 'hw-wp-status-manager'))."';
		$(this).val(saving_text);
		$.ajax({
			url:ajaxurl,
			type: 'post',
			data: {
				action:'hw_wpsm_save_data',
				security: '$ajax_nonce',
				'cpt_slug': cpt_slug,
				'hw_wpsm_singular': hw_wpsm_singular,
				'hw_wpsm_plural': hw_wpsm_plural,
				'hw_wpsm_public': hw_wpsm_public,
				'hw_wpsm_admin_all_list': hw_wpsm_admin_all_list,
				'hw_wpsm_admin_status_list': hw_wpsm_admin_status_list,
				'hw_wpsm_exclude_from_search': hw_wpsm_exclude_from_search,
				'hw_wpsm_builtin': hw_wpsm_builtin,
				'hw_wpsm_internal': hw_wpsm_internal,
				'hw_wpsm_protected': hw_wpsm_protected,
				'hw_wpsm_private': hw_wpsm_private,
				'hw_wpsm_publicly_queryable': hw_wpsm_publicly_queryable					
				}, success: function( response ) {
					if (debug) { console.log( response ); }
					var selected_cpt = $('#options').data('slug');
					$.fn.refresh_data(selected_cpt);
					$('#hw-wpsm-save').attr('disabled', false);
					var save_text = '".addslashes(__('Save', 'hw-wp-status-manager'))."';
					$('#hw-wpsm-save').val(save_text);
					$('.ui-dialog-titlebar-close').trigger('click');
				},
				error: function(errorThrown){
					if (debug) { console.log( errorThrown ); }
					return false;
				}
		});
	});
	
	$('#hw-wpsm-reset').click( function(e){
		e.preventDefault();
		var reset_text = '".addslashes(__('Reset all plugin data? Type "DELETE" to confirm this action', 'hw-wp-status-manager'))."';
		var response = prompt(reset_text, '');
		if (response == 'DELETE') {			
			$.ajax({
				url:ajaxurl,
				type: 'post',
				data: {
					action:'hw_wpsm_reset_plugin_data',
					security: '$ajax_nonce'
					}, success: function( response ) {
						if (debug) { console.log(response); }
						var response_obj = jQuery.parseJSON( response );
						window.location.replace(response_obj.redirect_url);
					},
					error: function(errorThrown){
						if (debug) { console.log( errorThrown ); }
						return false;
					}
			});
		}
	});
	
	  
	});
	</script>";
}

/* REGISTER CUSTOM POST STATUSES */
add_action('admin_init', 'hw_wp_status_manager_init');
function hw_wp_status_manager_init() {
	$post_types = hw_wp_status_manager_get_post_types();
	foreach ($post_types as $post_type_slug) { //for each post type
		$statuses_list = hw_wp_status_manager_get_custom_statuses($post_type_slug);
		if (is_array($statuses_list)) {	
			foreach($statuses_list as $status_slug=>$status_options) {
				$opt_public = ($status_options['opt_public'] == 1) ? true : false;
				$opt_show_in_admin_all_list = ($status_options['opt_show_in_admin_all_list'] == 1) ? true : false;
				$opt_show_in_admin_status_list = ($status_options['opt_show_in_admin_status_list'] == 1) ? true : false;
				$opt_exclude_from_search = ($status_options['opt_exclude_from_search'] == 1) ? true : false;
				$opt_builtin = ($status_options['opt_builtin'] == 1) ? true : false;
				$opt_internal = ($status_options['opt_internal'] == 1) ? true : false;
				$opt_protected = ($status_options['opt_protected'] == 1) ? true : false;
				$opt_private = ($status_options['opt_private'] == 1) ? true : false;
				$opt_publicly_queryable = ($status_options['opt_publicly_queryable'] == 1) ? true : false;
				register_post_status( $status_slug, array(
					'label'                     =>	_x($status_options['label_singular'], 'hw-wp-status-manager'),
					'public'                    =>	$opt_public,
					'label_count'               =>	_n_noop( $status_options['label_singular'].' <span class="count">(%s)</span>', $status_options['label_plural'].' <span class="count">(%s)</span>', 'hw-wp-status-manager' ),
					'show_in_admin_all_list'    =>	$opt_show_in_admin_all_list,
					'show_in_admin_status_list' =>	$opt_show_in_admin_status_list,
					'exclude_from_search'		=>	$opt_exclude_from_search,
					'_builtin'					=>	$opt_builtin,
					'internal'					=>	$opt_internal,
					'protected'					=>	$opt_protected,
					'private'					=>	$opt_private,
					'publicly_queryable'		=>	$opt_publicly_queryable
				) );
			}
		}
	}
}

/* GET LIST OF POST TYPES ACTUALLY REGISTERED IN WP */
function hw_wp_status_manager_get_post_types() {
$post_types = get_post_types( '', 'names' );
return $post_types;
}

/* MANIPULATE THE DOM WITH JS TO APPEND CUSTOM STATUSES */
function hw_wp_status_manager_append_edit_status() {
		$current_post_type = get_post_type();
		$statuses_list = hw_wp_status_manager_get_custom_statuses($current_post_type);
		if (is_array($statuses_list)) {
			$options = hw_wp_status_manager_get_cpt_options($current_post_type);
			$hidden_core_status = ($options['hide_core_statuses'] == 1) ? true : false;
			/* JS SCRIPT START */
			echo "<script>jQuery(document).ready( function() {";
			if ($hidden_core_status) {
				echo "
				jQuery( 'select[name=\"_status\"]' ).empty();
				jQuery( 'select[name=\"post_status\"]' ).empty();
				";
			}
			foreach($statuses_list as $status_slug=>$status_options) {
				$selected = (get_post_status() == $status_slug) ? "selected" : "";
				if (get_post_status() == $status_slug) {
					$selected = "selected";
					echo "jQuery( '#post-status-display' ).text('".$status_options['label_singular']."');";
				} else { $selected = ""; }
				echo "jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"$status_slug\" $selected>".$status_options['label_singular']."</option>' );";
				echo "jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"$status_slug\" $selected>".$status_options['label_singular']."</option>' );";
			}
			echo "});</script>";
			/* JS SCRIPT END */
		}
}
add_action('admin_footer-edit.php','hw_wp_status_manager_append_edit_status');
add_action('admin_footer-post.php', 'hw_wp_status_manager_append_edit_status');
add_action('admin_footer-post-new.php', 'hw_wp_status_manager_append_edit_status');

function hw_wp_status_manager_filter_publish_button() {
$current_post_type = get_post_type();
$options = hw_wp_status_manager_get_cpt_options($current_post_type);
$hidden_core_status = ($options['hide_core_statuses'] == 1) ? true : false;
	if ($hidden_core_status) {
		echo "<style>#publishing-action{visibility:hidden;}</style>
		<script>jQuery(document).ready( function() {";
		echo "
		jQuery( 'input[name=\"publish\"' ).val('".addslashes(__('Update', 'hw-wp-status-manager'))."');
		jQuery( 'input[name=\"publish\"' ).attr({name:'save', id:'save-post', name:'save', });
		jQuery('#publishing-action').css('visibility', 'visible');
		";
		echo "});</script>";
	}
}
add_action( 'admin_head', 'hw_wp_status_manager_filter_publish_button' );

function hw_wp_status_manager_get_custom_statuses($post_type_slug=false) {
	global $wpdb;
	$hw_wpsm_table = hw_wpsm_table_name();
		if ($post_type_slug != false) {
			$statuses = $wpdb->get_var("SELECT statuses FROM $hw_wpsm_table WHERE post_type_slug = '$post_type_slug'");
			if ($statuses != null) {
				return unserialize($statuses);
			} else {
				return array();
			}
		} else {
			//get all
			//$statuses = $wpdb->get_results("SELECT statuses FROM $hw_wpsm_table", ARRAY_A);
			return array();
		}
}

function hw_wp_status_manager_get_cpt_options($post_type_slug=false) {
	global $wpdb;
	$hw_wpsm_table = hw_wpsm_table_name();
	$options = null;
		if ($post_type_slug != false) {
			$options = unserialize($wpdb->get_var("SELECT options FROM $hw_wpsm_table WHERE post_type_slug = '$post_type_slug'"));
		}
		if (!is_array($options)) {
			$options = array('hide_core_statuses'=>'0');
		}
	return $options;
}

function hw_wp_status_manager_update_statuses($post_type_slug, $new_status, $force_update=false) {
	global $wpdb;
	$hw_wpsm_table = hw_wpsm_table_name();
	//check if $post_type_row exists
	$pte = $wpdb->get_var( "SELECT COUNT(*) FROM $hw_wpsm_table WHERE post_type_slug = '$post_type_slug'" );
	if ($pte > 0) {
		//get data and merge it
		$old_statuses = hw_wp_status_manager_get_custom_statuses($post_type_slug);
		if (is_array($old_statuses) && !$force_update) { 
			$statuses = serialize(array_merge($old_statuses, $new_status));
		} else {
			//table field is corrupted
			$statuses = serialize($new_status);
		}
		//update
		$update_options = $wpdb->query("UPDATE $hw_wpsm_table SET statuses = '$statuses' WHERE post_type_slug = '$post_type_slug'");
	} else {
		//insert new
		$update_options = $wpdb->insert( $hw_wpsm_table, array( 'post_type_slug' => $post_type_slug, 'statuses' => $new_status) );
	}
	return $update_options;
}

function hw_wp_status_manager_update_options($post_type_slug, $new_options) {
	global $wpdb;
	$hw_wpsm_table = hw_wpsm_table_name();
	//check if $post_type_row exists
	$pte = $wpdb->get_var( "SELECT COUNT(*) FROM $hw_wpsm_table WHERE post_type_slug = '$post_type_slug'" );
	if ($pte > 0) {
		//get data and merge it
		$old_options = hw_wp_status_manager_get_cpt_options($post_type_slug);
		if (is_array($old_options)) { 
			$options = serialize(array_merge($old_options, $new_options));
		} else {
			//table field is corrupted
			$options = serialize($new_options);
		}
		//update
		$update_options = $wpdb->query("UPDATE $hw_wpsm_table SET options = '$options' WHERE post_type_slug = '$post_type_slug'");
	} else {
		//insert new
		$update_options = $wpdb->insert( $hw_wpsm_table, array( 'post_type_slug' => $post_type_slug, 'options' => serialize($new_options)) );
	}
	return $update_options;
}

/***** AJAX CALLS *****/
/* DELETE STATUS */
add_action( 'wp_ajax_hw_wpsm_delete_data', 'hw_wp_status_manager_delete_ajax' );
function hw_wp_status_manager_delete_ajax() {
check_ajax_referer( 'hw-wp-status-manager', 'security' );
	if (isset($_POST['cpt_slug']) && isset($_POST['status_slug'])) {
		$post_type_slug = sanitize_text_field($_POST['cpt_slug']);
		$status_slug = sanitize_text_field($_POST['status_slug']);
		$new_status_options = hw_wp_status_manager_get_custom_statuses($post_type_slug);
		unset($new_status_options[$status_slug]);
		$update_options = hw_wp_status_manager_update_statuses($post_type_slug, $new_status_options, true);
		//$debug_data = filter_input_array($_POST, FILTER_SANITIZE_STRING);
		$debug_data = "";
		echo json_encode(array('success'=>'true', 'data'=> $debug_data));
	} else {
		echo json_encode(array('error'=>'one of cpt_slug or status_slug is missing', 'data'=>$debug_data));
	}
wp_die();
}



add_action( 'wp_ajax_hw_wpsm_save_data', 'hw_wp_status_manager_save_ajax' );
function hw_wp_status_manager_save_ajax() {
check_ajax_referer( 'hw-wp-status-manager', 'security' );	
	if (isset($_POST['cpt_slug'])) {
		$post_type_slug = sanitize_text_field($_POST['cpt_slug']);
		$singular_form = sanitize_text_field($_POST['hw_wpsm_singular']);
		$the_status_slug = strtolower(str_replace(' ', '-', $singular_form));
		$plural_form = (!isset($_POST['hw_wpsm_plural']) || empty($_POST['hw_wpsm_plural'])) ? $singular_form : sanitize_text_field($_POST['hw_wpsm_plural']);
		$new_status_options  = 	array($the_status_slug=>
									array(
									'label_singular'=>$singular_form,
									'label_plural'=>$plural_form,
									'opt_public'=>sanitize_text_field($_POST['hw_wpsm_public']),
									'opt_show_in_admin_all_list'=>sanitize_text_field($_POST['hw_wpsm_admin_all_list']),
									'opt_show_in_admin_status_list'=>sanitize_text_field($_POST['hw_wpsm_admin_status_list']),
									'opt_exclude_from_search'=>sanitize_text_field($_POST['hw_wpsm_exclude_from_search']),
									'opt_builtin'=>sanitize_text_field($_POST['hw_wpsm_builtin']),
									'opt_internal'=>sanitize_text_field($_POST['hw_wpsm_internal']),
									'opt_protected'=>sanitize_text_field($_POST['hw_wpsm_protected']),
									'opt_private'=>sanitize_text_field($_POST['hw_wpsm_private']),
									'opt_publicly_queryable'=>sanitize_text_field($_POST['hw_wpsm_publicly_queryable'])
									)									
								);
		$update_options = hw_wp_status_manager_update_statuses($post_type_slug, $new_status_options);
		$success = ($update_options == true) ? 'true' : 'false';
		//$debug_data = filter_input_array($_POST, FILTER_SANITIZE_STRING);
		$debug_data = "";		
		echo json_encode(array('success'=>$success, 'data'=>$debug_data));
	} else {
		echo json_encode(array('error'=>'cpt_slug is missing', 'data'=>$debug_data));
	}
wp_die();
}

add_action( 'wp_ajax_hw_wpsm_save_data_options', 'hw_wp_status_manager_save_options_ajax' );
function hw_wp_status_manager_save_options_ajax() {
check_ajax_referer( 'hw-wp-status-manager', 'security' );
	if (isset($_POST['cpt_slug'])) {
		$post_type_slug = sanitize_text_field($_POST['cpt_slug']);
		$hide_core_statuses = (sanitize_text_field($_POST['hide_core_statuses'] == 0)) ? 1 : 0;
		$new_options = array( 'hide_core_statuses'=>$hide_core_statuses );
		$update_options = hw_wp_status_manager_update_options($post_type_slug, $new_options);
		$success = ($update_options == true) ? 'true' : 'false';
		echo json_encode(array('success'=>$success, 'data'=>filter_input_array($_POST, FILTER_SANITIZE_STRING)));
	} else {
		echo json_encode(array('error'=>'cpt_slug is missing', 'data'=>filter_input_array($_POST, FILTER_SANITIZE_STRING)));
	}
wp_die();
}

add_action( 'wp_ajax_hw_wpsm_get_statuses_list', 'hw_wp_status_manager_get_statuses_list_ajax' );
function hw_wp_status_manager_get_statuses_list_ajax() {
check_ajax_referer( 'hw-wp-status-manager', 'security' );	
	if (isset($_POST['cpt_slug'])) {
		$html = "";
		$post_type_slug = sanitize_text_field($_POST['cpt_slug']);
		$statuses_list = hw_wp_status_manager_get_custom_statuses($post_type_slug);
		$count_status = 0;
		if (is_array($statuses_list)) {
			foreach($statuses_list as $status_slug=>$status_options) {
				$count_status++;
				$html .="<li><a class='edit-status' data-slug='$status_slug' data-cpt='$post_type_slug'>".$status_options['label_singular']."</a></li>";
				
			}
			$success = 'true';
		} else {
			$success = 'list from db is not array';
		}
		if ($count_status == 0) {
			$html = "<li>".__('No custom Status yet', 'hw-wp-status-manager')."</li>";
		}

		echo json_encode(array('success'=>$success, 'cpt_slug'=>$post_type_slug, 'html'=>$html, 'data_raw'=>$statuses_list));
	} else {
		echo json_encode(array('error'=>'cpt_slug is missing', 'data'=>filter_input_array($_POST, FILTER_SANITIZE_STRING)));
	}
	
wp_die();
}

add_action( 'wp_ajax_hw_wpsm_get_status_data', 'hw_wp_status_manager_get_status_data_ajax' );
function hw_wp_status_manager_get_status_data_ajax() {
check_ajax_referer( 'hw-wp-status-manager', 'security' );
	if (isset($_POST['cpt_slug']) && isset($_POST['status_slug'])) {
		$post_type_slug = sanitize_text_field($_POST['cpt_slug']);
		$status_slug = sanitize_text_field($_POST['status_slug']);
		$list_statuses = hw_wp_status_manager_get_custom_statuses($post_type_slug);
		if (is_array($list_statuses)) {
			echo json_encode($list_statuses[$status_slug]);
		} else {
			echo json_encode(array('error'=>'list_statuses is not array'));
		}
	} else {
		echo json_encode(array('error'=>'one of cpt_slug or status_slug is missing'));
	}
wp_die();
}

add_action( 'wp_ajax_hw_wpsm_reset_plugin_data', 'hw_wp_status_manager_reset_data_ajax' );
function hw_wp_status_manager_reset_data_ajax() {
global $wpdb;
check_ajax_referer( 'hw-wp-status-manager', 'security' );
	delete_option('hw_wp_status_manager_ver');
	$hw_wpsm_table = hw_wpsm_table_name();		
	$wpdb->query("DROP TABLE IF EXISTS $hw_wpsm_table");
	deactivate_plugins( plugin_basename( __FILE__ ) );
	$redirect_url = get_admin_url() . "/plugins.php";
	echo json_encode(array('message'=>'reset executed', 'redirect_url'=>$redirect_url));
wp_die();
}
?>