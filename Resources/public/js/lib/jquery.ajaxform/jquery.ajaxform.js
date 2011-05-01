(function($) {
	this.settings = {
		selector_error: 'div.error',
		class_bad: 'bad',
		class_good: 'good',
		url : null,
		data : {},
		timeout : 30000,
		ajax_upload : false,
		dataType : 'text',		// Always text
		type : 'POST',			// Always post

		// Define functions
		custom_success : null,		// call back after ajax is done
		custom_failure : null		// call on failure
	};
	
	// Regular inline hidden error element
	this.settings.error_formatter = function(container, errors) {
		var errorElement = container.find(settings.selector_error);
		if (errors != null)
		{
			container
				.addClass(settings.class_bad)
				.removeClass(settings.class_good);
			errorElement.html(errors[0]);
		}
		else
		{
			container
				.addClass(settings.class_good)
				.removeClass(settings.class_bad);
			
			errorElement.html('');
		}
	};
	
	var form;
	
	var success = function(data, status, xhr) {
		var returnedJson = $.parseJSON(data);
		var errorList = returnedJson.error;
		
		for (var index in errorList)
		{
			if (index == '*')
			{
				continue;
			}
			
			var errors = errorList[index];
			var container = form.find('#' + index + '-container');
			settings.error_formatter(container, errors);
		}
		
		if (settings.custom_success)
		{
			settings.custom_success(returnedJson);
		}
	};
	
	var error = function(data, status, xhr) 
	{
		if (settings.custom_failure)
		{
			settings.custom_failure(data, status, xhr);
		}
		else
		{
			alert('error...');
		}
	};

	var methods = {};

	/**
	 * Init the form
	 * 
	 * @param options
	 * @returns
	 */
	methods.init = function(options) {
		return this.each(function() {
			if ( options ) { 
		        $.extend( settings, options );
			}
		});
	};
	
	/**
	 * Takes standard $.ajax() options
	 * 
	 * @param options
	 * @returns
	 */
	methods.submit = function (options)
	{
		console.log('in submit:');
		console.log(settings);
		var hasSession = this.data('session.ajaxForm');
		var data = {};
		if ( options ) { 
	        $.extend( this.settings, options );
	        data = options.data;
		}
		
		if (!hasSession) {
			settings.data = methods.serialize.apply(this, [data]);
			settings.dataType = 'text';
			settings.type = 'POST';
			settings.success = success;
			settings.error = error;
			
			if (!settings.url)
			{
				settings.url = this.attr('action');
			}
			
			form = this;
			$.ajax(settings);
		}
	};
	
	/**
	 * Serialize form data + any custom data
	 * 
	 * @param data
	 * @returns
	 */
	methods.serialize = function(data)
	{
		var serializedData = this.serializeArray();
		for (var key in data)
		{
			serializedData.push({name: key, value : data[key]});
		}
		
		return $.param(serializedData);
	};

	/**
	 * Takes array of error elements and render the 
	 * 	form's error elements
	 * 
	 * @param errors
	 * @returns
	 */
	methods.error = function(errors) {

	};

	methods.disable = function() {

	};

	$.fn.ajaxForm = function(method) {
		if (!method) {
			method = 'submit';
		}
		
		// Method calling logic
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(
					arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.tooltip');
		}
	};
})(jQuery);

