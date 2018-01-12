<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
	<?php foreach ( $attributes['errors'] as $error ) : ?>
		<p>
			<?php echo $error; ?>
		</p>
	<?php endforeach; ?>
<?php endif; ?>

<div id="password-lost-form" class="widecolumn">
	<p>
		<?php
			_e(
				"Enter your email address and we'll send you a link you can use to pick a new password.",
				'user-account-management'
			);
		?>
	</p>

	<form id="lost-password-form" method="post" action="<?php echo $attributes['action']; ?>" class="m-form m-form-standalone m-form-user m-form-lost-password">
		<fieldset>
			<div class="m-form-item m-form-email m-form-login-email">
				<label for="user_login"><?php _e( 'Email Address:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
				<input type="email" name="user_login" id="user_login" required>
			</div>

			<div class="m-form-actions">
				<input type="submit" name="submit" value="<?php _e( 'Reset Password', 'user-account-management' ); ?>" class="btn btn-submit btn-submit-lostpassword">
			</div>
		</fieldset>
	</form>
</div>
