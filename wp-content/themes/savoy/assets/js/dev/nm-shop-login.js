(function($) {
	
	'use strict';
	
    $.nmReady(function() {
		
		/* Show register form */
		function showRegisterForm(animTimeout) {
			// Form wrapper elements
			var $loginWrap = $('#nm-login-wrap'),
				$registerWrap = $('#nm-register-wrap'),
                registerAnimTimeout = animTimeout;
			
            // Disable animations?
            if (animTimeout == 0) {
                $registerWrap.addClass('no-anim');
            } else {
                $registerWrap.removeClass('no-anim');
            }
            
			// Login/register form
			$loginWrap.removeClass('fade-in');
			setTimeout(function() {
				$registerWrap.addClass('inline fade-in slide-up');
				$loginWrap.removeClass('inline slide-up');
			}, animTimeout);
		};
        
		/* Show login form */
		function showLoginForm(animTimeout) {
			// Form wrapper elements
			var $loginWrap = $('#nm-login-wrap'),
				$registerWrap = $('#nm-register-wrap');
			
            // Disable animations?
            if (animTimeout == 0) {
                $loginWrap.addClass('no-anim');
            } else {
                $loginWrap.removeClass('no-anim');
            }
            
			// Login/register form
			$registerWrap.removeClass('fade-in');
			setTimeout(function() {
				$loginWrap.addClass('inline fade-in slide-up');
				$registerWrap.removeClass('inline slide-up');
			}, animTimeout);
		};
        
        // Show register form if "#register" is added to URL
        if (window.location.hash && window.location.hash == '#register') {
            showRegisterForm(0);
        } else {
            showLoginForm(0);
        }
		
		/* Bind: Show register form button */
		$('#nm-show-register-button').on('click', function(e) {
			showRegisterForm(250);
		});
		
		/* Bind: Show login form button */
		$('#nm-show-login-button').on('click', function(e) {
			showLoginForm(250);
		});
		
	});
})(jQuery);
