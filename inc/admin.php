<?php
class smartArchivesAdmin extends smartArchives {
	var $options = array(
		'format' => 'both',
		'catID' => '',
		'interval' => 'daily'
	);

	var $nonce = 'smart-archives-options';

	function __construct() {
		add_action('admin_menu', array(&$this, 'page_init'));
	}

	function activate() {
		add_option('smart-archives', $this->options);

		wp_schedule_event(time(), $this->options['interval'], 'smart_archives_update');
	}

	function deactivate() {
		wp_clear_scheduled_hook('smart_archives_update');
	}

// Options page methods

	function page_init() {
		if ( current_user_can('manage_options') )
			add_options_page('Smart Archives', 'Smart Archives', 8, 'smart-archives', array(&$this, 'page'));
	}

	function update_options() {
		if ( 'Save' != $_POST['action'] )
			return;

		check_admin_referer($this->nonce);

		foreach ( $this->options as $name => $value )
			$newoptions[$name] = $_POST[$name];

		if ( $newoptions == $this->options )
			return;

		update_option('smart-archives', $newoptions);	// First update, then generate

		if ( $newoptions['interval'] != $this->options['interval'] )
			wp_reschedule_event(time(), $newoptions['interval'], 'smart_archives_update');

		if ( ($newoptions['format'] != $this->options['format']) || ($newoptions['catID'] != $this->options['catID']) )
			$this->generate(); // rebuild the archive with changed settings

		$this->options = $newoptions;

		echo '<div class="updated"><p>Options <strong>saved</strong>.</p></div>';
	}

	function page() {
		$this->options = get_option('smart-archives');
		$this->update_options();
	?>
<div class="wrap">

<h2>Smart Archives Options</h2>
<form name="sar-options" method="post" action="">
	<table class="form-table">
		<tr>
			<th scope="row" valign="top">Format</th>
			<td>
			<?php foreach (array('block', 'list', 'both') as $value) { ?>
				<input type="radio"<?php if ($value == $this->options['format']) echo ' checked="checked"'; ?> name="format" value="<?php echo $value; ?>" />
				<label><?php echo $value; ?></label>
				<br class="clear" />
			<?php } ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Exclude Categories by ID</th>
			<td>
				<input type="text" name="catID" value="<?php echo $this->options['catID']; ?>" style="width: 250px" />
				<label>(space separated)</label>

				<p>A list of category IDs  that you want to exclude from the list archives.</p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Cache Update</th>
			<td>
			<?php foreach (array('hourly', 'twicedaily', 'daily') as $value) { ?>
				<input type="radio"<?php if ($value == $this->options['interval']) echo ' checked="checked"'; ?> name="interval" value="<?php echo $value; ?>" />
				<label><?php echo str_replace('twicedaily', 'twice daily', $value); ?></label>

				<br class="clear" />
			<?php } ?>

				<p>Set how often you want the cache to be updated.</p>
			</td>
		</tr>
		<tr>
	</table>

	<?php wp_nonce_field($this->nonce); ?>

	<p class="submit">
		<input type="submit" name="action" value="Save" />
	</p>
</form>

</div>
	<?php
	}
}

