<div id="password-reset-form" class="widecolumn">
<form id="reset-password-form" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" class="m-form m-form-standalone m-form-user m-form-reset-password">


		<input type="hidden" id="user_login" name="rp_login" value="<?php echo rawurlencode( $attributes['login'] ); ?>" autocomplete="off" />
		<input type="hidden" name="rp_key" value="<?php echo rawurlencode( $attributes['key'] ); ?>" />

		<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
			<?php foreach ( $attributes['errors'] as $error ) : ?>
				<p>
					<?php echo $error; ?>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>

		<fieldset>
			<div class="m-form-item m-form-password m-form-reset-password">
				<label for="pass1"><?php _e( 'New password', 'user-account-management' ); ?></label>
				<input type="password" name="new_password" id="new_password" value="" autocomplete="off" />
			</div>

			<p class="description"><?php echo wp_get_password_hint(); ?></p>

			<div class="m-form-actions">
			</div>
		</fieldset>
	</form>
</div>
			<input type="submit" name="submit" id="resetpass-button" value="<?php _e( 'Reset Password', 'user-account-management' ); ?>" class="btn btn-submit btn-reset-password">
