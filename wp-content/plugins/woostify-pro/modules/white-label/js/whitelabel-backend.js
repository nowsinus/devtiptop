/**
 * White Label admin
 *
 * @package Woostify Pro
 */

'use strict';
var file_frame;
var woostifyData = woostify_white_label_admin;
jQuery(document).ready(function($) {
	// Xử lý sự kiện khi người dùng chọn tập tin đính kèm
	var select_logo_btn = $('#woostify_white_label_logo_select');
	select_logo_btn.length && select_logo_btn.click(function(e) {
		e.preventDefault();
		!file_frame && ( file_frame = wp.media.frames.file_frame = wp.media({
			title   : woostifyData.i18n.mediaTitle,
			button  : {
				text: woostifyData.i18n.mediaButton
			},
			multiple: false
		}) );
		file_frame.on('select', function() {
			var attachment = file_frame.state().get('selection').first().toJSON();
			var imageUrl = (attachment.sizes.thumbnail||0) ? attachment.sizes.thumbnail.url : attachment.sizes.full.url;
			$('#woostify_white_label_logo_attachment').val(attachment.id);
			$('#woostify_white_label_logo_attachment').parent().children('.while-label-logo-image').show().find('img').attr('src', imageUrl ).siblings('.remove').show();
		});
		file_frame.open();
		});

  // Xử lý sự kiện khi người dùng xóa hình ảnh
  $('.while-label-logo-image').on('click', '.remove', function(e) {
    e.preventDefault();
    $('#woostify_white_label_logo_attachment').val('');
	$('#woostify_white_label_logo_attachment').parent().find('.while-label-logo-image img').attr('src', woostifyData.defaultScr).siblings('.remove').hide();
  });
});
		