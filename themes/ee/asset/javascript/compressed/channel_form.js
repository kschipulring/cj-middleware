/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
//Dynamically set the textarea name
function setFieldName(e){e!=selField&&(selField=e,clear_state(),tagarray=new Array,usedarray=new Array,running=0)}
// Insert tag
function taginsert(item,tagOpen,tagClose){
// Determine which tag we are dealing with
var which=eval("item.name");if(!selField)return $.ee_notice(no_cursor),!1;var theSelection=!1,result=!1,theField=document.getElementById("entryform")[selField];
// Is this a Windows user?
// If so, add tags around selection
if("guided"==selMode&&(data=prompt(enter_text,""),null!=data&&""!=data&&(result=tagOpen+data+tagClose)),document.selection)return theSelection=document.selection.createRange().text,theField.focus(),theSelection?document.selection.createRange().text=0==result?tagOpen+theSelection+tagClose:result:document.selection.createRange().text=0==result?tagOpen+tagClose:result,theSelection="",theField.blur(),void theField.focus();if(!isNaN(theField.selectionEnd)){var newStart,scrollPos=theField.scrollTop,selLength=theField.textLength,selStart=theField.selectionStart,selEnd=theField.selectionEnd;2>=selEnd&&"undefined"!=typeof selLength&&(selEnd=selLength);var s1=theField.value.substring(0,selStart),s2=theField.value.substring(selStart,selEnd).s3=theField.value.substring(selEnd,selLength);return 0==result?(newStart=selStart+tagOpen.length+s2.length+tagClose.length,theField.value=0==result?s1+tagOpen+s2+tagClose+s3:result):(newStart=selStart+result.length,theField.value=s1+result+s3),theField.focus(),theField.selectionStart=newStart,theField.selectionEnd=newStart,void(theField.scrollTop=scrollPos)}if("guided"==selMode)return curField=document.submit_post[selfField],curField.value+=result,curField.blur(),void curField.focus();
// Add single open tags
if("other"==item)eval("document.getElementById('entryform')."+selField+".value += tagOpen");else if(0==eval(which)){var result=tagOpen;eval("document.getElementById('entryform')."+selField+".value += result"),eval(which+" = 1"),arraypush(tagarray,tagClose),arraypush(usedarray,which),running++,styleswap(which)}else{for(
// Close tags
n=0,i=0;i<tagarray.length;i++)if(tagarray[i]==tagClose){for(n=i,running--;tagarray[n];)closeTag=arraypop(tagarray),eval("document.getElementById('entryform')."+selField+".value += closeTag");for(;usedarray[n];)clearState=arraypop(usedarray),eval(clearState+" = 0"),document.getElementById(clearState).className="htmlButtonA"}running<=0&&"htmlButtonB"==document.getElementById("close_all").className&&(document.getElementById("close_all").className="htmlButtonA")}curField=eval("document.getElementById('entryform')."+selField),curField.blur(),curField.focus()}var selField=!1,selMode="normal";$(document).ready(function(){$(".js_show").show(),$(".js_hide").hide(),void 0!==EE.publish.markitup&&void 0!==EE.publish.markitup.fields&&$.each(EE.publish.markitup.fields,function(e,t){$("#"+e).markItUp(mySettings)}),EE.publish.smileys===!0&&($("a.glossary_link").click(function(){return $(this).parent().siblings(".glossary_content").slideToggle("fast"),$(this).parent().siblings(".smileyContent .spellcheck_content").hide(),!1}),$("a.smiley_link").toggle(function(){which=$(this).attr("id").substr(12),$("#smiley_table_"+which).slideDown("fast",function(){$(this).css("display","")})},function(){$("#smiley_table_"+which).slideUp("fast")}),$(this).parent().siblings(".glossary_content, .spellcheck_content").hide(),$(".glossary_content a").click(function(){return $.markItUp({replaceWith:$(this).attr("title")}),!1})),$(".btn_plus a").click(function(){return confirm(EE.lang.confirm_exit,"")}),
// inject the collapse button into the formatting buttons list
$(".markItUpHeader ul").prepend('<li class="close_formatting_buttons"><a href="#"><img width="10" height="10" src="'+EE.THEME_URL+'images/publish_minus.gif" alt="Close Formatting Buttons"/></a></li>'),$(".close_formatting_buttons a").toggle(function(){$(this).parent().parent().children(":not(.close_formatting_buttons)").hide(),$(this).parent().parent().css("height","13px"),$(this).children("img").attr("src",EE.THEME_URL+"images/publish_plus.png")},function(){$(this).parent().parent().children().show(),$(this).parent().parent().css("height","auto"),$(this).children("img").attr("src",EE.THEME_URL+"images/publish_minus.gif")});var e="";EE.publish.show_write_mode===!0&&$("#write_mode_textarea").markItUp(myWritemodeSettings),$(".write_mode_trigger").click(function(){
// put contents from other page into here
return e=$(this).attr("id").match(/^id_\d+$/)?"field_"+$(this).attr("id"):$(this).attr("id").replace(/id_/,""),$("#write_mode_textarea").val($("#"+e).val()),$("#write_mode_textarea").focus(),!1})});