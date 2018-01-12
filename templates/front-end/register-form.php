<form id="register-form" action="<?php echo $attributes['action']; ?>" method="post" class="m-form m-form-standalone m-form-user m-form-register">

	<input type="hidden" name="city" value="">
	<input type="hidden" name="state" value="">

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

		<div class="m-form-item m-form-email m-form-register-email">
			<label for="email"><?php _e( 'Email Address:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="email" name="email" id="email" required>
		</div>

		<div class="m-form-item m-form-password m-form-register-password">
			<label for="password"><?php _e( 'Password:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="password" name="password" id="password" required>
		</div>

		<div class="m-form-item m-form-first-name m-form-register-first-name">
			<label for="first_name"><?php _e( 'First Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="first_name" id="first-name" required>
		</div>

		<div class="m-form-item m-form-last-name m-form-register-last-name">
			<label for="last_name"><?php _e( 'Last Name:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="last_name" id="last-name" required>
		</div>

		<div class="m-form-item m-form-zip-code m-form-register-zip-code">
			<label for="zip_code"><?php _e( 'Zip Code:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="tel" name="zip_code" id="zip-code" required>
		</div>

		<div class="m-form-item m-form-country m-form-register-country">
			<label for="country"><?php _e( 'Country:', 'user-account-management' ); ?> <span class="a-form-item-required" title="<?php _e( 'This field is required.', 'user-account-management' ); ?>">*</span></label>
			<input type="text" name="country" id="country">
		</div>

		<div class="m-form-actions">
			<input type="submit" name="submit" class="register-button" value="<?php _e( 'Create new account', 'user-account-management' ); ?>" class="btn btn-submit btn-submit-register">
		</div>
	</fieldset>

	<?php if ( ! empty( $attributes['privacy_terms'] ) ) : ?>
	<?php echo $attributes['privacy_terms']; ?>
	<?php endif; ?>

</form>
