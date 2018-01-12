<form id="reset-password-form" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" class="m-form m-form-standalone m-form-user m-form-reset-password">

	<input type="hidden" id="user_login" name="rp_login" value="<?php echo rawurlencode( $attributes['login'] ); ?>" autocomplete="off" />
	<input type="hidden" name="rp_key" value="<?php echo rawurlencode( $attributes['key'] ); ?>" />

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
		<div class="m-form-item m-form-password m-form-reset-password">
			<label for="new_password"><?php _e( 'New password:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="password" name="new_password" id="new_password" value="" class="password-show password-not-shown password-help password-help-not-shown" required>
		</div>

		<p class="description"><?php echo wp_get_password_hint(); ?></p>

		<div class="m-form-actions">
			<input type="submit" name="submit" id="resetpass-button" value="<?php _e( 'Reset Password', 'user-account-management' ); ?>" class="btn btn-submit btn-reset-password">
		</div>
	</fieldset>
</form>
