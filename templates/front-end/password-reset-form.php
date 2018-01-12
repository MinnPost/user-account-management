<div id="password-reset-form" class="widecolumn">
	<?php if ( $attributes['show_title'] ) : ?>
		<h3><?php _e( 'Pick a New Password', 'user-account-management' ); ?></h3>
	<?php endif; ?>

	<form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
		<input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off" />
		<input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>" />

		<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
			<?php foreach ( $attributes['errors'] as $error ) : ?>
				<p>
					<?php echo $error; ?>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>

		<fieldset>
			<div class="m-form-item m-form-password m-form-reset-password">
				<label for="pass1"><?php _e( 'New password', 'user-account-management' ) ?></label>
				<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
			</div>
			<p>
				<label for="pass2"><?php _e( 'Repeat new password', 'user-account-management' ) ?></label>
				<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
			</p>

			<p class="description"><?php echo wp_get_password_hint(); ?></p>

			<div class="m-form-actions">
				<input type="submit" name="submit" id="resetpass-button"
					   class="button" value="<?php _e( 'Reset Password', 'user-account-management' ); ?>" />
			</div>
		</fieldset>
	</form>
</div>
