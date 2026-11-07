<?php
/**
 * Full-screen image preview (ProductDetails.tsx).
 *
 * @package Veloria
 */

defined( 'ABSPATH' ) || exit;
?>
<template x-if="zoomOpen && currentImage">
	<div class="fixed inset-0 z-[70] bg-[rgba(18,15,11,0.94)] px-4 py-6 sm:px-6 lg:px-10" @keydown.escape.window="zoomOpen = false">
		<div class="flex h-full flex-col">
			<div class="mb-4 flex items-center justify-end">
				<button type="button" @click="zoomOpen = false" class="flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/18" aria-label="Close preview"><?php veloria_icon_e( 'x', 20 ); ?></button>
			</div>
			<div class="relative flex-1">
				<img :src="currentImage.src" :alt="currentImage.alt" class="absolute inset-0 h-full w-full object-contain">
				<template x-if="gallery.length > 1">
					<div>
						<button type="button" @click="prevImage()" class="absolute left-0 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/18" aria-label="Previous image"><?php veloria_icon_e( 'chevron-left', 22 ); ?></button>
						<button type="button" @click="nextImage()" class="absolute right-0 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/18" aria-label="Next image"><?php veloria_icon_e( 'chevron-right', 22 ); ?></button>
					</div>
				</template>
			</div>
			<div x-show="gallery.length > 1" class="mt-6 flex gap-3 overflow-x-auto pb-1 scrollbar-hide">
				<template x-for="(image, index) in gallery" :key="image.src + '-zoom-' + index">
					<button type="button" @click="selectedImage = index" class="relative h-24 w-24 shrink-0 overflow-hidden border transition" :class="activeIndex === index ? 'border-[#b7924e] opacity-100' : 'border-white/20 opacity-45'" :aria-label="'Preview image ' + (index + 1)">
						<img :src="image.src" :alt="image.alt" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
					</button>
				</template>
			</div>
		</div>
	</div>
</template>
