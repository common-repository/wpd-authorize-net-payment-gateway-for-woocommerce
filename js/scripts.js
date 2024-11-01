/*
 * wpdauth_admin_script
 *
 * @requires jquery
 * @package WPD Authorize.net
 * @author wpplugindesign.com
 * @since 0.5
 */

var skCubeGrid = '<div class="processing_spinner"></div>'; // sk-cube
var wpdRefunds = false;

// ajax
wpdauth_admin_ajax = {

	doing_ajax: false,
	ajax_post_values: [],

	ajaxCall: function(ajaxData) {

		var self = this;

		// console.log('ajaxCall');
		// console.log(ajaxData);
				
		self.doing_ajax = true;

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: ajaxData,
			complete: function(data) {
				self.ajaxComp(data, ajaxData.action);
			}
		}).fail(function() {
			// console.log('ajax fail');
			self.doing_ajax = false;
		});

	},

	ajaxComp: function(responseData, actIon) {

		var self = this;

		// console.log('ajaxComp');

		if (responseData.status === 200 && responseData.responseText !== '-1' && responseData.responseText !== null) {

			if ('authnet_get_transaction_status' == actIon) {

				var rt = JSON.parse(responseData.responseText);

				// console.log(rt);

				if ('fail' != rt['result']) {

					var staTus = rt['trans_stat'];
					var transId = rt['trans_id'];
					var authAm = rt['auth_amount'];
					var setAm = rt['settle_amount'];

					// prepare status text
					var printStatus = staTus.replace(/([A-Z])/g, ' $1').replace(/^./, function(str) { 
						return str.toUpperCase(); 
					});
					// find correct table row
					var transRow = jQuery('#wpd-authnet-transactions #the-list tr#trans-'+transId);
					// enter value in Current Status column
					transRow.children('.column-cur_stat').html('<span data-status="'+staTus+'">'+printStatus+'</span>');

				} else {
					// fail
				}
	
			}
		} else {
		
		}
		self.doing_ajax = false;
	}
} // wpdauth_admin_ajax

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

jQuery(document).ready(function($) {

	var is_single_shop_order = jQuery('body').hasClass('wpd_single_shop_order');

	if (is_single_shop_order) {

		// store order id
		var ordId = $('#wpd-authnet-transactions .wp-list-table').attr('data-oid');
		// get table rows
		var tableTransactions = $('#wpd-authnet-transactions #the-list tr');
		
		// there is an ajax call for each row in the table
		// there will usually be only one row... could be consolidated into single call
		tableTransactions.each(function(index, element) {
			if (!$(this).hasClass('no-items')) {
				// get row transaction id
				var rowId = $(this).attr('id');
				var rowIdSplit = rowId.split('-');
				var transId = rowIdSplit[1];

				// console.log(transId);

				ajaxData = { 
					action: 'authnet_get_transaction_status',
					trans_id: transId,
					order_id: ordId
				};
				wpdauth_admin_ajax.ajaxCall(ajaxData);
			}
		});

	} // is_single_shop_order

})
