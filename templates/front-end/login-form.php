<!-- Show logged out message if user just logged out -->
<?php if ( $attributes['logged_out'] ) : ?>
	<p class="login-info">
		<?php _e( 'You have logged out. Would you like to log in again?', 'user-account-management' ); ?>
	</p>
<?php endif; ?>

<!-- Show errors if there are any -->
<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
	<?php foreach ( $attributes['errors'] as $error ) : ?>
		<p class="login-error">
			<?php echo $error; ?>
		</p>
	<?php endforeach; ?>
<?php endif; ?>
<form id="login-form" method="post" action="<?php echo $attributes['action']; ?>" class="m-form m-form-standalone m-form-login">

	<?php if ( isset( $_GET['redirect_to'] ) ) : ?>
	<input type="hidden" name="redirect_to" value="<?php echo $_GET['redirect_to']; ?>">
	<?php endif; ?>

	<p class="a-form-instructions">No account yet? <a href="">Register now</a>.</p>

	<fieldset>
		<div class="m-form-item m-form-email m-form-login-email">
			<label for="user_login"><?php _e( 'Email Address:', 'user-account-management' ); ?> <span class="form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="email" name="log" id="user_login" required>
		</div>
		<div class="m-form-item m-form-password m-form-login-password">
			<label for="user_pass"><?php _e( 'Password:', 'user-account-management' ); ?> <span class="form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="password" name="pwd" id="user_pass" required>
		</div>
		<p class="a-form-instructions"><a href="">Need help or forgot password?</a>
		<div class="m-form-actions">
			<input type="submit" value="<?php _e( 'Log In', 'user-account-management' ); ?>">
		</div>
		<p class="a-form-instructions"><small>By proceeding, you agree to MinnPost's <a href="">Terms of Use</a> and <a href="">Privacy Policy</a>.</small></p>
	</fieldset>
</form>
