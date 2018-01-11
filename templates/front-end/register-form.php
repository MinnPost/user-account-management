<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
	<?php foreach ( $attributes['errors'] as $error ) : ?>
		<p>
			<?php echo $error; ?>
		</p>
	<?php endforeach; ?>
<?php endif; ?>
 
<form id="register-form" action="<?php echo $attributes['action']; ?>" method="post" class="m-form m-form-standalone m-form-register">

	<input type="hidden" name="city" value="">
	<input type="hidden" name="state" value="">

	<p class="a-form-instructions">Already have an account? <a href="">Log in now</a>. Do you need <a href="">account help</a>?</p>

	<fieldset>

		<div class="m-form-item m-form-email m-form-register-email">
			<label for="email"><?php _e( 'Email Address:', 'user-account-management' ); ?> <strong>*</strong></label>
			<input type="email" name="email" id="email" required>
		</div>

		<div class="m-form-item m-form-password m-form-register-password">
			<label for="password"><?php _e( 'Password:', 'user-account-management' ); ?> <strong>*</strong></label>
			<input type="password" name="password" id="password" required>
		</div>

		<div class="m-form-item m-form-first-name m-form-register-first-name">
			<label for="first_name"><?php _e( 'First Name:', 'user-account-management' ); ?></label>
			<input type="text" name="first_name" id="first-name" required>
		</div>

		<div class="m-form-item m-form-last-name m-form-register-last-name">
			<label for="last_name"><?php _e( 'Last Name:', 'user-account-management' ); ?></label>
			<input type="text" name="last_name" id="last-name" required>
		</div>

		<div class="m-form-item m-form-zip-code m-form-register-zip-code">
			<label for="zip_code"><?php _e( 'Zip Code:', 'user-account-management' ); ?></label>
			<input type="tel" name="zip_code" id="zip-code" required>
		</div>

		<div class="m-form-item m-form-country m-form-register-country">
			<label for="country"><?php _e( 'Country:', 'user-account-management' ); ?></label>
			<input type="text" name="country" id="country">
		</div>

		<div class="m-form-actions">
			<input type="submit" name="submit" class="register-button"
				   value="<?php _e( 'Register', 'user-account-management' ); ?>"/>
		</div>
	</fieldset>
</form>
