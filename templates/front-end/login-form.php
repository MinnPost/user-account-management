<!-- Show logged out message if user just logged out -->
<?php if ( $attributes['logged_out'] ) : ?>
	<p class="login-info">
		<?php _e( 'You have logged out. Would you like to log in again?', 'user-account-management' ); ?>
	</p>
<?php endif; ?>

<?php if ( $attributes['lost_password_sent'] ) : ?>
	<p class="login-info">
		<?php _e( 'Check your email for a link to reset your password.', 'user-account-management' ); ?>
	</p>
<?php endif; ?>

<form id="login-form" method="post" action="<?php echo $attributes['action']; ?>" class="m-form m-form-standalone m-form-login">

	<?php if ( isset( $_GET['redirect_to'] ) ) : ?>
	<input type="hidden" name="redirect_to" value="<?php echo $_GET['redirect_to']; ?>">
	<?php endif; ?>

	<?php if ( ! empty( $attributes['instructions'] ) ) : ?>
	<?php echo $attributes['instructions']; ?>
	<?php endif; ?>

	<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
		<div class="m-form-message m-form-message-error">
			<?php if ( count( $attributes['errors'] ) > 1 ) : ?>
				<ul>
					<?php foreach ( $attributes['errors'] as $error ) : ?>
						<li><?php echo $error; ?></li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php echo $attributes['errors'][0]; ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<fieldset>
		<div class="m-form-item m-form-email m-form-login-email">
			<label for="user_login"><?php _e( 'Email Address:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="email" name="log" id="user_login" required>
		</div>
		<div class="m-form-item m-form-password m-form-login-password">
			<label for="user_pass"><?php _e( 'Password:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="password" name="pwd" id="user_pass" required>
		</div>
		<div class="m-form-actions">
			<input type="submit" value="<?php _e( 'Log In', 'user-account-management' ); ?>">
		</div>
		<?php if ( ! empty( $attributes['password_help'] ) ) : ?>
		<?php echo $attributes['password_help']; ?>
		<?php endif; ?>
	</fieldset>
</form>
