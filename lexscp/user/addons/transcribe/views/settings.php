<div class="box">
<?php echo form_open(ee('CP/URL', 'addons/settings/transcribe/postSettings'));?>

		<h1><?=lang('transcribe_page_title_settings')?></h1>
		<?php $this->embed('ee:_shared/table', $table); ?>

<div style="width:100%; text-align:center; padding-bottom:10px; ">
<?php echo form_submit(array( 'name'=>'submit', 'value'=>lang('transcribe_button_save_settings'), 'class'=>'btn ', 'data-work-text'=> 'Saving...', 'data-submit-text' => 'Save Settings'));

echo form_close();?>
</div>
</div>