<?php echo form_open(ee('CP/URL', 'addons/settings/transcribe/templates'), array('id' => 'filterMenu')); ?>
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

<?php echo form_open(ee('CP/URL', 'addons/settings/transcribe/postTemplates')); ?>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th width="350"><?php echo lang('transcribe_col_label_template_group'); ?></th>
			<th><?php echo lang('transcribe_col_label_translation'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $count = 0; ?>
		<?php foreach( $template_groups as $key1 => $template_group ) : ?>
			<tr class="<?php echo ($count%2) ? 'even' : 'odd'; ?>">
				<td style="font-weight:bold;">
					<?php echo $template_group->group_name; ?>
				</td>
				<td>
					<?php echo form_input("templates[groups][$key1][content]", $template_group->content); ?>
					<?php echo form_hidden("templates[groups][$key1][group_id]", $template_group->group_id); ?>
				</td>
			</tr>

			<?php foreach( $templates as $key2 => $template ) : ?>
				<?php if( $template->group_id == $template_group->group_id ) : $count++; ?>
					<tr class="<?php echo ($count%2) ? 'even' : 'odd'; ?>">
						<td style="padding-left:22px;">
							–&nbsp;&nbsp;&nbsp;<?php echo $template->template_name; ?>
						</td>
						<td style="padding-left:22px;">
							<?php echo form_input("templates[templates][$key2][content]", $template->content); ?>
							<?php echo form_hidden("templates[templates][$key2][template_id]", $template->template_id); ?>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php $count++; endforeach; ?>
	</tbody>
</table>

<?php
echo form_hidden('language', $selected_language_id);
echo form_hidden('site', $selected_site_id);
echo form_submit('submit', lang('transcribe_button_save'), 'class="submit"');
echo form_close();
?>

