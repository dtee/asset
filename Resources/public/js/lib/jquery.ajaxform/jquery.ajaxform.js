log = function(value) {
	if (console && console.log) {
		console.log(value);
	}
};

(function($) {
	var settings = {
		disable_session_lock: false,	// Allow only one submit at a time
		
		class_bad: 'bad',
		class_good: 'good',
		url : null,
		data : {},				// Additional data to upload
		timeout : 10000,		// 10 seconds time out
		ajax_upload : false,	// enable file upload?
		dataType : 'text',		// Always text
		type : 'POST',			// Always post
		
		buttons: [],			// Array of clickable jquery elements in the $this

		// Define functions
		custom_success : null,		// call back after ajax is done
		custom_failure : null		// call on failure
	};
	
	// Regular inline hidden error element
	settings.error_formatter = function(container, errors) {
		var errorElement = container.find('> .error');
		if (errorElement.length == 0)
		{
			errorElement = $('<div class="error" />');
			container.append(errorElement);
		}
		
		if (errors != null)
		{
			container
				.addClass(settings.class_bad)
				.removeClass(settings.class_good);
			
			errorElement
				.css('display', 'block')
				.html(errors.join('<br/>'));
		}
		else
		{
			container
				.addClass(settings.class_good)
				.removeClass(settings.class_bad);
			
			errorElement
				.css('display', 'none')
				.html('');
		}
	};
	
	var success = function(data, status, xhr) {
		var returnedJson = $.parseJSON(data);
		var errorList = returnedJson.error;
		
		// Reset all errors
		$this.find('.error').html('').css('display', 'none');
		
		for (var index in errorList)
		{
			if (index == '*')
			{
				continue;
			}
			
			var errors = errorList[index];
			var container = $this.find('#' + index + '-container');
			if (container.length == 0)
			{
				container = $this.find('#' + index).parent();
			}
			
			settings.error_formatter(container, errors);
		}
		
		if (settings.custom_success)
		{
			settings.custom_success(returnedJson);
		}
		
		if (returnedJson.href)
		{
			window.location.href = returnedJson.href;
		}
		
		// Prevents double clicking
		endSession();
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
		
		endSession();
	};

	var methods = {};
	var $this = null;
	var $buttons = null;
	
	/**
	 * Init the $this
	 * 
	 * @param options
	 * @returns
	 */
	methods.init = function(options) {
		return this.each(function() {
			// If the element is not $this element, contine
			$this = $(this);
			if (this.tagName != 'FORM') {
				return;
			}
			
			if (!options) { 
				options = {};
			}
			
			if (!options.buttons) {
				options.buttons = $this.find(':button, :submit');
			}
			
			if (!options.url) {
				options.url = $this.attr('action');
			}
			
			$this.submit(function() {
				// Do timer call back so that it doesn't submit when
				//	javascript error occur
				setTimeout(methods.submit, 10);
				
				return false;
			});
			
			if (!options.url) {
				options.url = $(this).attr('action');
				
				if (!options.url)
				{options
					options.url = window.location.href;
				}
			}
			
	        $.extend(settings, options);
	        $buttons = settings.buttons;
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
		if (hasSession) { // do nothing
			alert('An going session is happening, please wait it out.');
			return;
		}
		else {
			startSession();	// Start up a session
		}
		
		var data = {};
		
		if ( options ) { 
	        $.extend( this.settings, options );
	        data = options.data;
		}
		
		if (settings.onsubmit) {
			data = settings.onsubmit();
		}
		
		settings.data = methods.serialize(data);
		log(settings.data)
		settings.dataType = 'text';
		settings.type = 'POST';
		settings.success = success;
		settings.error = error;
		
		$.ajax(settings);
	};
	
	/**
	 * Serialize $this data + any custom data
	 * 
	 * @param data
	 * @returns
	 */
	methods.serialize = function(data)
	{
		var serializedData = $this.serializeArray();
		for (var key in data)
		{
			value = $.toJSON(data[key]);
			serializedData.push({name: key, value : value});
		}
		
		return $.param(serializedData, false);
	};

	$.fn.ajaxForm = function(method) {
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

	
	/** Handles session **/
	var hasSession = false;
	var sessionTimeoutHandle = null;
	var startSession = function() {
		if (settings.disable_session_lock) {
			return;
		}
		
		disableButtons();
		
		hasSession = true;
		sessionTimeoutHandle = setTimeout(function() {
			sessionTimeoutHandle = null;
			endSession();
		}, settings.timeout);
	};
	
	var endSession = function() {
		hasSession = false;
		
		// enable all submitable buttons and input elements
		enableFields();

		// kill the session timer call back
		if (sessionTimeoutHandle) {
			sessionTimeoutHandle = null;
			clearTimeout(sessionTimeoutHandle);
		}
	};
	
	var disableFields = function() {
		$this.find('input').attr('readonly', true)
	};
	
	var disableButtons = function() {
		$buttons.attr('disabled', true);
	}
	
	var enableFields = function() {
		$buttons.attr('disabled', false);
		$this.find('input').attr('readonly', false);
	};
})(jQuery);

