<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
	<?php foreach ( $attributes['errors'] as $error ) : ?>
		<p>
			<?php echo $error; ?>
		</p>
	<?php endforeach; ?>
<?php endif; ?>


<div id="register-form" class="widecolumn">
	<?php if ( $attributes['show_title'] ) : ?>
		<h3><?php _e( 'Register', 'user-account-management' ); ?></h3>
	<?php endif; ?>
 
	<form id="signupform" action="<?php echo $attributes['action']; ?>" method="post">

		<p class="form-row">
			<label for="email"><?php _e( 'Email', 'user-account-management' ); ?> <strong>*</strong></label>
			<input type="email" name="email" id="email">
		</p>

		<p class="form-row">
			<label for="password"><?php _e( 'Password', 'user-account-management' ); ?> <strong>*</strong></label>
			<input type="password" name="password" id="password">
		</p>
 
		<p class="form-row">
			<label for="first_name"><?php _e( 'First name', 'user-account-management' ); ?></label>
			<input type="text" name="first_name" id="first-name">
		</p>
 
		<p class="form-row">
			<label for="last_name"><?php _e( 'Last name', 'user-account-management' ); ?></label>
			<input type="text" name="last_name" id="last-name">
		</p>

		<p class="form-row">
			<label for="zip_code"><?php _e( 'Zip Code', 'user-account-management' ); ?></label>
			<input type="tel" name="zip_code" id="zip-code">
		</p>
 
		<p class="signup-submit">
			<input type="submit" name="submit" class="register-button"
				   value="<?php _e( 'Register', 'user-account-management' ); ?>"/>
		</p>
	</form>
</div>
