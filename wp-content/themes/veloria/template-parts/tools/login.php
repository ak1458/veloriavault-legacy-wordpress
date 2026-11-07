<?php
/**
 * Template Name: Login
 *
 * src/app/login/page.tsx — password sign-in (WordPress accounts) or a
 * one-time email code for guest orders.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;

$input = 'w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-[#b59a5c]';
$btn   = 'w-full bg-[#1a1a1a] text-white py-3 text-sm font-bold uppercase tracking-widest hover:bg-[#b59a5c] transition-colors disabled:opacity-50';


?>
<div class="min-h-screen bg-[#faf8f5] flex items-center justify-center px-4 pt-24 pb-16" x-data="login()">
	<div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 max-w-md w-full">
		<?php if ( empty( $args['hide_heading'] ) ) : ?><h1 class="text-2xl font-serif text-gray-900 mb-2 text-center">Welcome Back</h1><?php endif; ?>
		<p class="text-sm text-gray-500 text-center mb-6">Sign in to your Veloria Vault account</p>

		<div class="flex mb-6 bg-gray-50 rounded-lg p-1 border border-gray-100">
			<?php list( $a, $ab ) = veloria_toggle( "mode === 'password'", 'bg-white shadow-sm text-gray-900', 'text-gray-500', true ); ?>
			<?php list( $b, $bb ) = veloria_toggle( "mode === 'otp'", 'bg-white shadow-sm text-gray-900', 'text-gray-500', false ); ?>
			<button type="button" @click="setMode('password')" class="flex-1 py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-colors <?php echo esc_attr( $a ); ?>"<?php echo $ab; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>Password</button>
			<button type="button" @click="setMode('otp')" class="flex-1 py-2 text-xs font-bold uppercase tracking-wider rounded-md transition-colors <?php echo esc_attr( $b ); ?>"<?php echo $bb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>Email me a code</button>
		</div>

		<div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-600" x-text="error"></p></div>
		<div x-show="info" x-cloak class="mb-4 p-3 bg-[#fcf8e8] border border-[#f0e6c8] rounded-lg"><p class="text-sm text-[#8a6d3b]" x-text="info"></p></div>

		<form x-show="mode === 'password'" @submit.prevent="submitPassword()" class="space-y-4">
			<div>
				<label for="login-email" class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Address</label>
				<input id="login-email" type="email" x-model="email" class="<?php echo esc_attr( $input ); ?>" placeholder="email@address.com" autocomplete="email" required>
			</div>
			<div>
				<label for="login-password" class="block text-xs font-bold text-gray-500 uppercase mb-1">Password</label>
				<div class="relative">
					<input id="login-password" :type="showPassword ? 'text' : 'password'" x-model="password" class="<?php echo esc_attr( $input ); ?> pr-10" placeholder="••••••••" autocomplete="current-password" required>
					<button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" aria-label="Show password">
						<span x-show="showPassword" x-cloak><?php veloria_icon_e( 'eye-off', 18 ); ?></span><span x-show="!showPassword"><?php veloria_icon_e( 'eye', 18 ); ?></span>
					</button>
				</div>
			</div>
			<button type="submit" :disabled="loading" class="<?php echo esc_attr( $btn ); ?>">
				<span x-show="loading"><?php veloria_icon_e( 'loader-2', 24, 'w-5 h-5 animate-spin mx-auto' ); ?></span><span x-show="!loading">Sign In</span>
			</button>
		</form>

		<form x-show="mode === 'otp' && otpStep === 'email'" x-cloak @submit.prevent="requestCode()" class="space-y-4">
			<p class="text-xs text-gray-500">Placed an order as a guest? Enter the email you used at checkout and we&apos;ll email you a one-time code to view your orders.</p>
			<div>
				<label for="otp-email" class="block text-xs font-bold text-gray-500 uppercase mb-1">Email Address</label>
				<input id="otp-email" type="email" x-model="email" class="<?php echo esc_attr( $input ); ?>" placeholder="email@address.com" autocomplete="email" required>
			</div>
			<button type="submit" :disabled="loading" class="<?php echo esc_attr( $btn ); ?> flex items-center justify-center gap-2">
				<span x-show="loading"><?php veloria_icon_e( 'loader-2', 24, 'w-5 h-5 animate-spin' ); ?></span>
				<span x-show="!loading" class="flex items-center gap-2"><?php veloria_icon_e( 'mail', 16 ); ?> Send Code</span>
			</button>
		</form>

		<form x-show="mode === 'otp' && otpStep === 'code'" x-cloak @submit.prevent="verifyCode()" class="space-y-4">
			<div>
				<label for="otp-code" class="block text-xs font-bold text-gray-500 uppercase mb-1">6-Digit Code</label>
				<input id="otp-code" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" x-model="code" @input="code = code.replace(/\D/g, '').slice(0, 6)" class="<?php echo esc_attr( $input ); ?> tracking-[0.5em] text-center text-lg" placeholder="000000" autocomplete="one-time-code" required>
			</div>
			<button type="submit" :disabled="loading || code.length !== 6" class="<?php echo esc_attr( $btn ); ?>">
				<span x-show="loading"><?php veloria_icon_e( 'loader-2', 24, 'w-5 h-5 animate-spin mx-auto' ); ?></span><span x-show="!loading">Verify &amp; Sign In</span>
			</button>
			<button type="button" @click="differentEmail()" class="w-full text-xs text-gray-500 hover:text-gray-800">Use a different email</button>
		</form>

		<p class="mt-6 text-center text-sm text-gray-600">Don&apos;t have an account? <a href="<?php echo esc_url( home_url( '/register' ) ); ?>" class="text-[#b59a5c] hover:underline font-medium">Create one</a></p>
	</div>
</div>
<?php
