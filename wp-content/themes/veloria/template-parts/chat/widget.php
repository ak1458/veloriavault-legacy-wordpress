<?php
/**
 * Shopping assistant (AgenticChatWidget.tsx). Styling: .chat-* classes in globals.css.
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?>
<div x-data="chatWidget()">
	<button type="button" x-show="!$store.chat.isOpen"
		x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-0" x-transition:enter-end="opacity-100 scale-100"
		@click="$store.chat.isOpen = true" class="chat-fab" aria-label="Open shopping assistant" id="chat-widget-trigger">
		<div class="chat-fab-inner"><?php veloria_icon_e( 'message-circle', 24 ); ?></div>
		<span class="chat-fab-pulse"></span>
	</button>

	<div x-show="$store.chat.isOpen" x-cloak
		x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10 scale-[0.92]" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
		x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-10 scale-[0.92]"
		class="chat-panel" role="dialog" aria-label="Shopping assistant chat" id="chat-widget-panel">
		<div class="chat-header">
			<div class="chat-header-info">
				<div class="chat-header-avatar"><?php veloria_icon_e( 'sparkles', 16 ); ?></div>
				<div>
					<h3 class="chat-header-title">Veloria Assistant</h3>
					<p class="chat-header-subtitle"><span class="chat-online-dot"></span>Online · Instant replies</p>
				</div>
			</div>
			<button type="button" @click="$store.chat.isOpen = false" class="chat-close-btn" aria-label="Close chat"><?php veloria_icon_e( 'x', 18 ); ?></button>
		</div>

		<div class="chat-messages">
			<template x-for="msg in $store.chat.messages" :key="msg.id">
				<div class="chat-bubble-row animate-scaleIn" :class="msg.role === 'user' ? 'chat-bubble-row-user' : 'chat-bubble-row-bot'">
					<template x-if="msg.role !== 'user'">
						<div class="chat-avatar"><?php veloria_icon_e( 'sparkles', 14 ); ?></div>
					</template>
					<div class="chat-bubble" :class="msg.role === 'user' ? 'chat-bubble-user' : 'chat-bubble-bot'">
						<template x-if="msg.isTyping">
							<div class="chat-typing">
								<div class="chat-typing-dot" style="animation-delay:0ms"></div>
								<div class="chat-typing-dot" style="animation-delay:150ms"></div>
								<div class="chat-typing-dot" style="animation-delay:300ms"></div>
							</div>
						</template>
						<template x-if="!msg.isTyping">
							<div>
								<div class="chat-bubble-text" x-html="formatMessage(msg.content)"></div>
								<template x-if="msg.products && msg.products.length">
									<div class="chat-carousel-wrap" x-data="chatCarousel()">
										<button type="button" x-show="canLeft" @click="scroll('left')" class="chat-carousel-arrow chat-carousel-arrow-left" aria-label="Scroll left"><?php veloria_icon_e( 'chevron-left', 16 ); ?></button>
										<div class="chat-carousel scrollbar-hide" x-ref="track" @scroll.passive="check()">
											<template x-for="product in msg.products" :key="product.id">
												<div class="chat-product-card">
													<div class="chat-product-image-wrap">
														<a :href="product.href">
															<template x-if="product.image"><img :src="product.image" :alt="product.name" width="200" height="200" class="chat-product-image" loading="lazy"></template>
															<template x-if="!product.image"><div class="chat-product-image-placeholder"><?php veloria_icon_e( 'shopping-bag', 32 ); ?></div></template>
														</a>
														<span x-show="discount(product) > 0" class="chat-product-badge" x-text="'-' + discount(product) + '%'"></span>
													</div>
													<div class="chat-product-info">
														<p class="chat-product-category" x-text="product.category"></p>
														<h4 class="chat-product-name" x-text="product.name"></h4>
														<div class="chat-product-pricing">
															<span class="chat-product-price" x-text="$inr(parseFloat(product.price))"></span>
															<span x-show="discount(product) > 0" class="chat-product-original-price" x-text="$inr(parseFloat(product.regularPrice))"></span>
														</div>
													</div>
													<div class="chat-product-actions">
														<button type="button" @click="buy(product)" class="chat-btn-buy" :aria-label="'Add ' + product.name + ' to cart'"><?php veloria_icon_e( 'shopping-bag', 14 ); ?><span>Add to Cart</span></button>
														<a :href="product.href" class="chat-btn-details"><span>View</span><?php veloria_icon_e( 'arrow-right', 14 ); ?></a>
													</div>
												</div>
											</template>
										</div>
										<button type="button" x-show="canRight" @click="scroll('right')" class="chat-carousel-arrow chat-carousel-arrow-right" aria-label="Scroll right"><?php veloria_icon_e( 'chevron-right', 16 ); ?></button>
									</div>
								</template>
								<template x-if="msg.quickReplies && msg.quickReplies.length">
									<div class="chat-quick-replies">
										<template x-for="reply in msg.quickReplies" :key="reply">
											<button type="button" @click="send(reply)" class="chat-quick-reply-chip" x-text="reply"></button>
										</template>
									</div>
								</template>
							</div>
						</template>
					</div>
				</div>
			</template>
			<div x-ref="end"></div>
		</div>

		<form @submit.prevent="send(input)" class="chat-input-bar">
			<input x-ref="input" type="text" x-model="input" placeholder="Ask me anything..." class="chat-input" :disabled="$store.chat.isLoading" maxlength="500" id="chat-input-field">
			<button type="submit" :disabled="$store.chat.isLoading || !input.trim()" class="chat-send-btn" aria-label="Send message">
				<span x-show="$store.chat.isLoading"><?php veloria_icon_e( 'loader-2', 18, 'animate-spin' ); ?></span>
				<span x-show="!$store.chat.isLoading"><?php veloria_icon_e( 'send', 18 ); ?></span>
			</button>
		</form>
	</div>
</div>
