window.fbAsyncInit = function() {
	FB.init({
		appId : '34505942778',
		channelUrl : null,
		status : true,
		cookie : true,
		xfbml : true,
		logging : true
	});

	FB.api('/me/friends', function(response) {
		  log(response);
	});
	// Do other facebook related tasks here
};

$(document).ready(function() {
	var fbScript = $('<script>').attr({
		async : true, 
		src : document.location.protocol + '//connect.facebook.net/en_US/all.js'
	});
	$('body').append('<div id="fb-root">').append(fbScript);
});

signout = function() {
	FB.logout(
		function(response)
		{
			window.location.reload();
		}
	);
};

fbConnect = function()
{
	var perms = {perms:'read_stream,publish_stream,email'};
	FB.login(
		function(response)
		{
			if (response.session) {
				window.location.reload();
			}
			else
			{
				alert('login cancled');
			}
		},
		perms
	);
};
