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
<div class="login-form-container">
	<form method="post" action="<?php echo $attributes['action']; ?>">

		<?php if ( isset( $_GET['redirect_to'] ) ) : ?>
		<input type="hidden" name="redirect_to" value="<?php echo $_GET['redirect_to']; ?>">
		<?php endif; ?>

		<p class="login-username">
			<label for="user_login"><?php _e( 'Email', 'user-account-management' ); ?></label>
			<input type="text" name="log" id="user_login">
		</p>
		<p class="login-password">
			<label for="user_pass"><?php _e( 'Password', 'user-account-management' ); ?></label>
			<input type="password" name="pwd" id="user_pass">
		</p>
		<p class="login-submit">
			<input type="submit" value="<?php _e( 'Log In', 'user-account-management' ); ?>">
		</p>
	</form>
</div>
