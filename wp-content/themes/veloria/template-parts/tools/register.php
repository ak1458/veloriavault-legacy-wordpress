<?php
/**
 * Template Name: Register
 *
 * src/app/register/page.tsx
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$input = 'w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-[#b59a5c]';


?>
<div class="min-h-screen bg-[#faf8f5] flex items-center justify-center px-4" x-data="register()">
	<div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 max-w-md w-full">
		<?php if ( empty( $args['hide_heading'] ) ) : ?><h1 class="text-2xl font-serif text-gray-900 mb-2 text-center">Create Account</h1><?php endif; ?>
		<p class="text-sm text-gray-500 text-center mb-8">Join Veloria Vault today</p>
		<div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-600" x-text="error"></p></div>
		<form @submit.prevent="submit()" class="space-y-4">
			<div class="grid grid-cols-2 gap-4">
				<div><label for="reg-first" class="block text-xs font-bold text-gray-500 uppercase mb-1">First Name</label><input id="reg-first" type="text" x-model="form.firstName" class="<?php echo esc_attr( $input ); ?>" placeholder="John" autocomplete="given-name" required></div>
				<div><label for="reg-last" class="block text-xs font-bold text-gray-500 uppercase mb-1">Last Name</label><input id="reg-last" type="text" x-model="form.lastName" class="<?php echo esc_attr( $input ); ?>" placeholder="Doe" autocomplete="family-name" required></div>
			</div>
			<div><label for="reg-email" class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Address</label><input id="reg-email" type="email" x-model="form.email" class="<?php echo esc_attr( $input ); ?>" placeholder="email@address.com" autocomplete="email" required></div>
			<div>
				<label for="reg-password" class="block text-xs font-bold text-gray-500 uppercase mb-1">Password</label>
				<div class="relative">
					<input id="reg-password" :type="showPassword ? 'text' : 'password'" x-model="form.password" class="<?php echo esc_attr( $input ); ?> pr-10" placeholder="••••••••" autocomplete="new-password" required>
					<button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" aria-label="Show password">
						<span x-show="showPassword" x-cloak><?php veloria_icon_e( 'eye-off', 18 ); ?></span><span x-show="!showPassword"><?php veloria_icon_e( 'eye', 18 ); ?></span>
					</button>
				</div>
			</div>
			<button type="submit" :disabled="loading" class="w-full bg-[#1a1a1a] text-white py-3 text-sm font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors disabled:opacity-50">
				<span x-show="loading"><?php veloria_icon_e( 'loader-2', 24, 'w-5 h-5 animate-spin mx-auto' ); ?></span><span x-show="!loading">Create Account</span>
			</button>
		</form>
		<p class="mt-6 text-center text-sm text-gray-600">Already have an account? <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="text-[#b59a5c] hover:underline font-medium">Sign in</a></p>
	</div>
</div>
<?php
