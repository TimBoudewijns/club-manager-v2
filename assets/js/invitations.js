// Club Manager Invitation JavaScript
jQuery(document).ready(function($) {
    
    // Check email button click
    $('#cm-check-email').on('click', function(e) {
        e.preventDefault();
        
        var email = $(this).data('email');
        showLoading();
        
        // Check if email exists
        $.ajax({
            url: cm_invitation.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_check_email',
                email: email,
                nonce: cm_invitation.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    if (response.data.exists) {
                        // Show login form
                        showStep('login');
                    } else {
                        // Show registration form
                        showStep('register');
                    }
                }
            },
            error: function() {
                hideLoading();
                showError('Er is een fout opgetreden. Probeer het opnieuw.');
            }
        });
    });
    
    // Login form submission
    $('#cm-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var email = $('#cm-login-email').val();
        var password = $('#cm-login-password').val();
        
        showLoading();
        hideError();
        
        $.ajax({
            url: cm_invitation.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_login_trainer',
                email: email,
                password: password,
                token: cm_invitation.token,
                nonce: cm_invitation.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    
                    // Redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 2000);
                } else {
                    hideLoading();
                    showError(response.data);
                }
            },
            error: function() {
                hideLoading();
                showError('Er is een fout opgetreden. Probeer het opnieuw.');
            }
        });
    });
    
    // Registration form submission
    $('#cm-register-form').on('submit', function(e) {
        e.preventDefault();
        
        var firstname = $('#cm-register-firstname').val();
        var lastname = $('#cm-register-lastname').val();
        var email = $('#cm-register-email').val();
        var password = $('#cm-register-password').val();
        var passwordConfirm = $('#cm-register-password-confirm').val();
        
        // Validate passwords match
        if (password !== passwordConfirm) {
            showError('Wachtwoorden komen niet overeen.');
            return;
        }
        
        // Validate password length
        if (password.length < 8) {
            showError('Wachtwoord moet minimaal 8 karakters zijn.');
            return;
        }
        
        showLoading();
        hideError();
        
        $.ajax({
            url: cm_invitation.ajax_url,
            type: 'POST',
            data: {
                action: 'cm_register_trainer',
                firstname: firstname,
                lastname: lastname,
                email: email,
                password: password,
                token: cm_invitation.token,
                nonce: cm_invitation.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccess(response.data.message);
                    
                    // Redirect after 2 seconds
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 2000);
                } else {
                    hideLoading();
                    showError(response.data);
                }
            },
            error: function() {
                hideLoading();
                showError('Er is een fout opgetreden. Probeer het opnieuw.');
            }
        });
    });
    
    // Helper functions
    function showStep(step) {
        $('.cm-step').hide();
        $('#cm-step-' + step).fadeIn();
    }
    
    function showLoading() {
        $('#cm-invitation-content .cm-step').hide();
        $('#cm-loading').show();
    }
    
    function hideLoading() {
        $('#cm-loading').hide();
    }
    
    function showError(message) {
        $('#cm-error').html(message).show();
        $('html, body').animate({
            scrollTop: $('#cm-error').offset().top - 100
        }, 500);
    }
    
    function hideError() {
        $('#cm-error').hide();
    }
    
    function showSuccess(message) {
        hideLoading();
        $('.cm-step').hide();
        $('#cm-success').html('<h3>Gelukt!</h3><p>' + message + '</p>').show();
    }
    
    // Auto-focus on password field when login form is shown
    $(document).on('click', '#cm-check-email', function() {
        setTimeout(function() {
            $('#cm-login-password').focus();
        }, 500);
    });
});