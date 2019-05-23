<form id="lost-password-form" method="post" action="<?php echo $attributes['action']; ?>" class="m-form m-form-standalone m-form-user m-form-lost-password">

	<?php include_once( plugin_dir_path( __FILE__ ) . '../template-parts/honeypots.php' ); ?>

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
			<input type="email" name="user_login" id="user_login" required>
		</div>
		<div class="m-form-actions">
			<input type="submit" name="submit" value="<?php _e( 'Reset Password', 'user-account-management' ); ?>" class="btn btn-submit btn-submit-lostpassword">
		</div>
	</fieldset>
</form>
