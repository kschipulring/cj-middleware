<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>

<script type="text/javascript">
// The following JS thanks to Low Schutte
// Transcribe Delete Var
var TDV  = new Object();


TDV.DeleteVar = function(){

    // EE's modal
    var $modal = $('.modal-log-replace .box');

    // Set max-height
    $modal.css({
        maxHeight: ($(window).height() - 80 - 55) + 'px',
        overflow: 'auto',
        padding: '10px 10px 0',
    });

    // Links that trigger the modal
    $('.delete-var').on('click', function(event){

        $modal.html('<p style="text-align:center">Loading</p>');
        // console.log(this.href);
        $modal.load(this.href);
    });

};

$(TDV.DeleteVar);
</script>
<div class="box">

<?php echo form_open(ee('CP/URL', 'addons/settings/transcribe/index'), array('id' => 'filterMenu')); ?>
    <fieldset>
		<legend><?php echo lang('transcribe_label_filters'); ?></legend>

        <?php if( !empty($languages_dropdown) ) : ?>

			<?php echo form_dropdown('language', array($languages_dropdown), $selected_language_id, 'style="margin-right:5px;'); ?>
			<?php echo form_dropdown('site', array($sites_dropdown), $selected_site_id, 'style="margin-right:5px;'); ?>
			<input type="submit" name="submit" value="<?php echo lang('transcribe_button_filter_submit'); ?>" class="submit" />
			<a href="<?php echo $base_url.AMP.'method=create'; ?>" style="margin-left:4px;"><?php echo lang('transcribe_create_new_language'); ?></a>

        <?php endif; ?>

    </fieldset>
<?php echo form_close(); ?>

<?php echo form_open(ee('CP/URL', 'addons/settings/transcribe/index')); ?>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th width="50"><?php echo lang('transcribe_col_label_id'); ?></th>
			<th width="350"><?php echo lang('transcribe_col_label_variable'); ?></th>
			<th colspan="2"><?php echo lang('transcribe_col_label_translation'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach( $selected_language_variables as $key => $variable ) : ?>

			<tr class="<?php echo ($key%2) ? 'even' : 'odd'; ?>">
				<td><?php echo $variable->variable_id; ?></td>
				<td valign="top">
					<?php echo form_dropdown("translation[$key][variable][select]", $unused_variables_dropdown, NULL, 'style="float:left; margin:3px 10px 3px 0; width:85px;"'); ?>
					<?php echo form_input("translation[$key][variable][name]", $variable->name, 'style="float: left; width: 200px;"'); ?>
					<?php echo form_hidden("translation[$key][variable][id]", $variable->variable_id); ?>
				</td>
				<td>
					<?php echo form_textarea(array('name'=>"translation[$key][translation][content]", 'rows' => '3', 'cols' => '20'), $variable->content); ?>
					<?php echo form_hidden("translation[$key][translation][id]", $variable->translation_id); ?>
				</td>
				<td>
					<?php
					$delete_url = ee('CP/URL', 'addons/settings/transcribe/delete_var_confirm/' . $variable->variable_id.'/'.$variable->translation_id);
					echo  '<a href="' . $delete_url . '" class="m-link delete-var" rel="modal-log-replace" title="remove">'.lang('delete').'</a>';
					?>


				</td>
			</tr>
		<?php endforeach; ?>

		<?php $row_id = count($selected_language_variables); ?>
		<tr class="<?php echo ($row_id%1) ? 'odd' : 'even'; ?>">
			<td><?php echo lang('transcribe_label_newrow'); ?></td>
			<td valign="top">
				<?php echo form_dropdown("translation[$row_id][variable][select]", $unused_variables_dropdown, NULL, 'style="float:left; margin:3px 10px 3px 0; width:85px;"'); ?>
				<?php echo form_input("translation[$row_id][variable][name]", '', 'style="float: left; width: 200px;"'); ?>
				<?php echo form_hidden("translation[$row_id][variable][id]"); ?>
			</td>
			<td>
				<?php echo form_textarea("translation[$row_id][translation][content]"); ?>
				<?php echo form_hidden("translation[$row_id][translation][id]"); ?>
			</td>
			<td></td>
		</tr>
	</tbody>
</table>

<?php

echo form_hidden('language', $selected_language_id);
echo form_hidden('site', $selected_site_id);
echo form_submit('submit', lang('transcribe_button_save'), 'class="submit"');
echo '<a id="transcribe-add-row" href="#" style="margin-left:5px;">' . lang('transcribe_button_addrow') . '</a>';
echo form_close();
echo "<br>";
if ( ! empty($pagination)) echo $pagination; ?>
</div>
<script type="text/javascript">
var new_row = $('.mainTable').find('tbody').find('tr').last().clone();

$('#transcribe-add-row').click(function() {
	var row_count = $('.mainTable').find('tbody').find('tr').length;
	var id = row_count;
	var row_class = ( id % 2 ) ? 'even' : 'odd';

	var row = new_row.clone();
	row.attr('class', row_class);

	row.find('td').eq(1).find('select').attr('name', 'translation[' + id + '][variable][select]');
	row.find('td').eq(1).find('input[type=text]').attr('name', 'translation[' + id + '][variable][name]');
	row.find('td').eq(1).find('input[type=hidden]').attr('name', 'translation[' + id + '][variable][id]');

	row.find('td').eq(2).find('textarea').attr('name', 'translation[' + id + '][translation][content]');
	row.find('td').eq(2).find('input[type=hidden]').attr('name', 'translation[' + id + '][translation][id]');

	$('.mainTable').find('tbody').append(row);
	return false;
});

$('.mainTable').find('tbody').find('select').on('change', function(event) {
	if( this.selectedIndex == 0 ) {
		$(this).parent().find('input[type=text]').removeAttr('disabled');
	} else {
		$(this).parent().find('input[type=text]').attr('disabled', 'disabled').attr('value','');
		$(this).parent().find('input[type=hidden]').attr('value','');
	}
});
</script>
