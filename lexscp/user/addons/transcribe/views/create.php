<?php
echo form_open(ee('CP/URL', 'addons/settings/transcribe/create'));


$this->table->clear();
$cp_table_template['heading_cell_start'] = '<th style="width:50%">';

$this->table->set_template($cp_table_template);
$this->table->set_heading(array(
	lang('transcribe_col_label_param'),
	lang('transcribe_col_label_value'),
));

$this->table->add_row(array(
	lang('transcribe_label_language'),
	form_input('language'),
));

$this->table->add_row(array(
	lang('transcribe_label_abbreveation'),
	form_input('abbreviation'),
));

if( !$have_languages )
{
	$this->table->add_row(array(
		lang('transcribe_label_assign_entries'),
		form_checkbox('assign_entries', 'yes'),
	));
}

echo $this->table->generate();
echo form_submit(array( 'name'=>'submit', 'value'=>lang('transcribe_button_create'), 'class'=>'submit' ));
echo form_close();
