<?php $ispagelogin = true; include 'inc/config.php'; ?>
<?php include 'inc/template_start.php'; ?>

<!-- Login Full Background -->
<!-- For best results use an image with a resolution of 1280x1280 pixels (prefer a blurred image for smaller file size) -->
<img src="img/placeholders/backgrounds/login_full_bg.jpg" alt="Login" class="full-bg animation-pulseSlow">
<!-- END Login Full Background -->

<!-- Login Container -->
<div id="login-container" class="animation-fadeIn">
    <!-- Login Title -->
    <div class="login-title text-center">
        <h1><i class="gi gi-flash"></i> <strong>Secure connect</strong><br><small>Please <strong>Login</strong></small></h1>
    </div>
    <!-- END Login Title -->

    <!-- Login Block -->
    <div class="block push-bit">
        <!-- Login Form -->
        <form action="crmajaxlogin.php" method="post" id="form-login" class="form-horizontal form-bordered form-control-borderless">
			<input type="hidden" name="action" id="action" value="login" />
            <div class="form-group">
                <div class="col-xs-12">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="gi gi-envelope"></i></span>
                        <input type="text" id="email" name="email" class="form-control input-lg" placeholder="Email">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="gi gi-asterisk"></i></span>
                        <input type="password" id="psw" name="psw" class="form-control input-lg" placeholder="Password">
                    </div>
                </div>
            </div>
            <div class="form-group form-actions">
                <div class="col-xs-4">
                    <label class="switch switch-primary" data-toggle="tooltip" title="Remember Me?">
                        <input type="checkbox" id="login-remember-me" name="login-remember-me" checked>
                        <span></span>
                    </label>
                </div>
                <div class="col-xs-8 text-right">
                    <button type="submit" class="btn btn-sm btn-primary" id="btlogin"><i class="fa fa-angle-right"></i> Login to Dashboard</button>
                </div>
            </div>
            <div class="form-group">
                <div class="col-xs-12 text-center">
                    <!-- <a href="javascript:void(0)" id="link-reminder-login"><small>Forgot password?</small></a> -
                    <a href="javascript:void(0)" id="link-register-login"><small>Create a new account</small></a> -->
                </div>
            </div>
        </form>
        <!-- END Login Form -->
    </div>
    <!-- END Login Block -->
</div>
<!-- END Login Container -->

<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script>
    $(function(){
        $('#form-login').validate({
            errorClass: 'help-block animation-slideDown', // You can change the animation class for a different entrance animation - check animations page
            errorElement: 'div',
            errorPlacement: function(error, e) {
                e.parents('.form-group > div').append(error);
            },
            highlight: function(e) {
                $(e).closest('.form-group').removeClass('has-success has-error').addClass('has-error');
                $(e).closest('.help-block').remove();
            },
            success: function(e) {
                e.closest('.form-group').removeClass('has-success has-error');
                e.closest('.help-block').remove();
            },
            rules: {
                'login-email': {
                    required: true,
                    email: true
                },
                'login-password': {
                    required: true,
                    minlength: 5
                }
            },
            messages: {
                'login-email': 'Please enter your account\'s email',
                'login-password': {
                    required: 'Please provide your password',
                    minlength: 'Your password must be at least 5 characters long'
                }
            }
        });

        $('#form-login').submit(function() {
            $('#btlogin').prop( "disabled", true );
            jQuery(this).ajaxSubmit({
                dataType:'json',
                success : function (resp) {
                    if (resp.code == 'SUCCESS') {
                        location.href = 'index.php';
                    }
                    else
                    if (resp.code == 'ERROR')
                        alert(resp.message);
                    $('#btlogin').prop( "disabled", false);
                },
                error : function() {
                    console.log('NO');
                    $('#btlogin').prop( "disabled", false);
                }
            });
            return false;
        });

    });
</script>

<?php include 'inc/template_end.php'; ?>
