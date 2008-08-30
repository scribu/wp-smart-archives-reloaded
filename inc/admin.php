<?php
class smartArchivesAdmin extends smartArchives {
	var $defaults = array(
		'format' => 'both',
		'catID' => '',
		'interval' => 'daily'
	);

	function __construct() {
		$this->cachefile = dirname(dirname(__FILE__)) . '/cache.txt';

		add_action('admin_menu', array(&$this, 'page_init'));
	}

	function activate() {
		add_option('smart-archives', $this->defaults) or extract(get_option('smart-archives'));

		if ( !$interval )
			$interval = $this->defaults['interval'];

		wp_schedule_event(time(), $interval, 'smart_archives_update');
	}

	function deactivate() {
		wp_clear_scheduled_hook('smart_archives_update');
	}

	// Options Page
	function page_init() {
		if ( current_user_can('manage_options') )
			add_options_page('Smart Archives', 'Smart Archives', 8, 'smart-archives', array(&$this, 'page'));
	}

	function page() {
		$options = get_option('smart-archives'); // load options

		// Update options
		if ( $_POST['submit-options'] ) {
			foreach ($this->defaults as $name => $value)
				$newoptions[$name] = $_POST[$name];

			if ( $newoptions != $options ) {
				update_option('smart-archives', $newoptions);

				if ( $newoptions['interval'] != $options['interval'] )
					wp_reschedule_event(time(), $newoptions['interval'], 'smart_archives_update');

				if ( $newoptions['format'] != $options['format'] || $newoptions['catID'] != $options['catID'])
					$this->generate(); // rebuild the archive with changed settings

				$options = $newoptions;
			}

			echo '<div class="updated"><p>Options <strong>saved</strong>.</p></div>';
		}
	?>
<div class="wrap">
<h2>Smart Archives Options</h2>
<form name="sar-options" method="post" action="<?= str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<table class="form-table">
		<tr>
			<th scope="row" valign="top">Format</th>
			<td>
			<?php foreach (array('block', 'list', 'both') as $value) { ?>
				<input type="radio"<?php if ($value === $options['format']) echo ' checked="checked"'; ?> name="format" value="<?php echo $value; ?>" />
				<label><?php echo $value; ?></label>
				<br class="clear" />
			<?php } ?>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Exclude Categories by ID</th>
			<td>
				<input type="text" name="catID" value="<?php echo $options['catID']; ?>" style="width: 250px" />
				<label>(space separated)</label>

				<p>A list of category IDs  that you want to exclude from the list archives.</p>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top">Cache Update</th>
			<td>
			<?php foreach (array('hourly', 'twicedaily', 'daily') as $value) { ?>
				<input type="radio"<?php if ($value === $options['interval']) echo ' checked="checked"'; ?> name="interval" value="<?php echo $value; ?>" />
				<label><?php echo str_replace('twicedaily', 'twice daily', $value); ?></label>

				<br class="clear" />
			<?php } ?>

				<p>Set how often you want the cache to be updated.</p>
			</td>
		</tr>
		<tr>
	</table>

	<p class="submit">
	<input type="submit" name="submit-options" value="Save Options" />
	</p>
</form>

</div>
	<?php
	}
}

$smartArchivesAdmin = new smartArchivesAdmin();
?>
