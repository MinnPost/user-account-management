<form id="change-password-form" action="<?php echo $attributes['current_url'] ?>" method="post" class="m-form m-form-standalone m-form-user m-form-change-password">

	<input type="hidden" name="user_account_management_action" value="reset-password"/>
	<input type="hidden" name="user_account_management_redirect" value="<?php echo $attributes['redirect']; ?>"/>
	<input type="hidden" name="user_account_management_password_nonce" value="<?php echo wp_create_nonce('uam-password-nonce'); ?>"/>

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

	<?php if ( ! empty( $_GET['password-reset'] ) && 'true' === esc_attr( $_GET['password-reset'] ) ) : ?>
		<div class="m-form-message m-form-message-info">
			<p class="login-info">
				<?php _e( 'Your password was successfully changed.', 'user-account-management' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<fieldset>
		<div class="m-form-item m-form-password m-form-change-password">
			<label for="new_password"><?php _e( 'New password:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="password" name="new_password" id="new_password" value="" class="password-show password-help password-help-not-shown password-strength-check">
		</div>

		<div class="m-form-actions">
			<input type="submit" name="submit" id="change-button" value="<?php _e( 'Change Password', 'user-account-management' ); ?>" class="btn btn-submit btn-change-password">
		</div>
	</fieldset>
</form>
