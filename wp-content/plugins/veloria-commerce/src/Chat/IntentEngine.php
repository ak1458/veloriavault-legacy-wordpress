<?php
/**
 * Shopping-assistant intent engine (port of src/app/api/chat/intent/route.ts).
 * Deterministic keyword matching; no external AI service.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Chat;

use Veloria\Commerce\Catalog\Catalog;
use Veloria\Commerce\Settings;

defined( 'ABSPATH' ) || exit;

final class IntentEngine {

	private const CATEGORY_KEYWORDS = array(
		'tote-bag'    => array( 'tote', 'totes', 'tote bag', 'tote bags', 'big bag', 'large bag', 'carry all' ),
		'satchel-bag' => array( 'satchel', 'satchels', 'satchel bag', 'office bag', 'work bag', 'laptop bag', 'professional' ),
		'sling-bag'   => array( 'sling', 'sling bag', 'sling bags', 'casual bag', 'small bag' ),
		'crossbody'   => array( 'crossbody', 'cross body', 'cross-body', 'crossbodies', 'shoulder bag' ),
		'clutch'      => array( 'clutch', 'clutches', 'evening bag', 'party bag', 'party clutch', 'envelope' ),
		'wallet'      => array( 'wallet', 'wallets', 'purse', 'card holder', 'billfold', 'money' ),
	);

	private const CATEGORY_LABELS = array(
		'tote-bag'    => 'Tote',
		'satchel-bag' => 'Satchel',
		'sling-bag'   => 'Sling',
		'crossbody'   => 'Crossbody',
		'clutch'      => 'Clutch',
		'wallet'      => 'Wallet',
	);

	private const INTENT = array(
		'greeting'    => array( 'hi', 'hello', 'hey', 'hii', 'hola', 'sup', 'good morning', 'good evening', 'good afternoon' ),
		'gift'        => array( 'gift', 'gifting', 'present', 'for her', 'for my sister', 'for my mom', 'for wife', 'for girlfriend', 'birthday', 'anniversary' ),
		'premium'     => array( 'premium', 'luxury', 'expensive', 'high end', 'best', 'top', 'finest', 'exclusive' ),
		'sale'        => array( 'sale', 'discount', 'offer', 'deal', 'bargain', 'clearance', 'on sale' ),
		'newArrivals' => array( 'new', 'latest', 'newest', 'recent', 'just arrived', 'fresh', 'trending' ),
		'help'        => array( 'help', 'support', 'contact', 'customer care', 'phone', 'call', 'email', 'return', 'refund', 'exchange', 'track', 'tracking', 'order status' ),
		'color'       => array( 'black', 'brown', 'tan', 'cherry', 'maroon', 'green', 'olive', 'beige', 'cream', 'pink', 'red', 'blue', 'navy', 'white', 'grey', 'gray' ),
		'thanks'      => array( 'thanks', 'thank you', 'thankyou', 'thx', 'ty', 'great', 'awesome', 'perfect', 'nice' ),
		'bye'         => array( 'bye', 'goodbye', 'good bye', 'see you', 'later', 'quit', 'exit' ),
	);

	private static function includes_any( string $haystack, array $needles ): bool {
		foreach ( $needles as $needle ) {
			if ( str_contains( $haystack, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/** @return array<string,mixed> */
	public static function parse( string $message ): array {
		$lower = mb_strtolower( trim( $message ) );
		$words = preg_split( '/\s+/', $lower ) ?: array();
		// Policy questions must not become product searches (including the
		// existing "Shipping info" quick reply). Tracking/returns keep their
		// dedicated support path rather than receiving a price-only answer.
		if ( preg_match( '/\b(?:shipping|delivery|dispatch)\b/u', $lower )
			&& ! self::includes_any( $lower, array( 'track', 'refund', 'return', 'exchange', 'order status' ) ) ) {
			return array( 'type' => 'shipping' );
		}

		foreach ( self::INTENT['greeting'] as $k ) {
			if ( str_starts_with( $lower, $k ) || $lower === $k ) {
				return array( 'type' => 'greeting' );
			}
		}
		if ( self::includes_any( $lower, self::INTENT['thanks'] ) ) {
			return array( 'type' => 'thanks' );
		}
		if ( self::includes_any( $lower, self::INTENT['bye'] ) ) {
			return array( 'type' => 'bye' );
		}
		if ( self::includes_any( $lower, self::INTENT['help'] ) ) {
			return array( 'type' => 'help' );
		}

		$intent = array( 'type' => 'product_search' );

		foreach ( self::CATEGORY_KEYWORDS as $slug => $keywords ) {
			if ( self::includes_any( $lower, $keywords ) ) {
				$intent['category'] = $slug;
				break;
			}
		}

		if ( preg_match( '/(?:under|below|within|upto|up to|less than|max|budget)\s*₹?\s*(\d+)/u', $lower, $m ) ) {
			$intent['maxPrice'] = (int) $m[1];
		}
		if ( preg_match( '/(?:around|about|approximately|~)\s*₹?\s*(\d+)/u', $lower, $m ) ) {
			$price              = (int) $m[1];
			$intent['minPrice'] = (int) floor( $price * 0.7 );
			$intent['maxPrice'] = (int) ceil( $price * 1.3 );
		}

		foreach ( self::INTENT['color'] as $color ) {
			if ( in_array( $color, $words, true ) ) {
				$intent['color'] = $color;
				break;
			}
		}

		if ( self::includes_any( $lower, self::INTENT['premium'] ) ) {
			$intent['isPremium'] = true;
		}
		if ( self::includes_any( $lower, self::INTENT['sale'] ) ) {
			$intent['isSale'] = true;
		}
		if ( self::includes_any( $lower, self::INTENT['newArrivals'] ) ) {
			$intent['isNewArrivals'] = true;
		}
		if ( self::includes_any( $lower, self::INTENT['gift'] ) ) {
			$intent['type'] = 'gift';
		}

		if ( empty( $intent['category'] ) && 'product_search' === $intent['type'] ) {
			$noise      = array( 'i', 'me', 'my', 'want', 'need', 'looking', 'for', 'a', 'an', 'the', 'show', 'find', 'get', 'buy', 'can', 'you', 'please', 'something', 'some', 'good', 'nice', 'bag', 'bags', 'in', 'with', 'and', 'or' );
			$meaningful = array_values( array_filter( $words, static fn( $w ) => ! in_array( $w, $noise, true ) && mb_strlen( $w ) > 2 ) );
			if ( $meaningful ) {
				$intent['searchTerms'] = implode( ' ', $meaningful );
			}
		}

		return $intent;
	}

	/** @return array<int,array<string,mixed>> */
	private static function fetch_products( array $intent ): array {
		$catalog = Catalog::instance();
		if ( ! empty( $intent['category'] ) ) {
			foreach ( $catalog->categories() as $category ) {
				if ( $category['slug'] === $intent['category'] ) {
					return array_values(
						array_filter(
							$catalog->products( array( 'category' => $category['id'], 'per_page' => 30 ) ),
							static fn( $p ) => in_array( $category['id'], array_column( $p['categories'], 'id' ), true )
						)
					);
				}
			}
			return array();
		}
		if ( ! empty( $intent['searchTerms'] ) ) {
			return $catalog->products( array( 'search' => $intent['searchTerms'], 'per_page' => 30 ) );
		}
		return $catalog->products( array( 'per_page' => 30 ) );
	}

	/** @return array<int,array<string,mixed>> */
	private static function rank( array $products, array $intent ): array {
		if ( ! empty( $intent['maxPrice'] ) ) {
			$products = array_filter( $products, static fn( $p ) => (float) $p['price'] <= $intent['maxPrice'] );
		}
		if ( ! empty( $intent['minPrice'] ) ) {
			$products = array_filter( $products, static fn( $p ) => (float) $p['price'] >= $intent['minPrice'] );
		}
		$products = array_values( $products );

		if ( ! empty( $intent['color'] ) ) {
			$color    = $intent['color'];
			$filtered = array_values(
				array_filter(
					$products,
					static function ( $p ) use ( $color ) {
						if ( str_contains( strtolower( $p['name'] ), $color ) ) {
							return true;
						}
						foreach ( $p['attributes'] as $a ) {
							if ( str_contains( strtolower( (string) $a['option'] ), $color ) ) {
								return true;
							}
							foreach ( (array) $a['options'] as $o ) {
								if ( str_contains( strtolower( (string) $o ), $color ) ) {
									return true;
								}
							}
						}
						return false;
					}
				)
			);
			if ( $filtered ) {
				$products = $filtered;
			}
		}

		if ( ! empty( $intent['isSale'] ) ) {
			$sale = array_values( array_filter( $products, static fn( $p ) => ! empty( $p['on_sale'] ) ) );
			if ( $sale ) {
				$products = $sale;
			}
		}

		if ( ! empty( $intent['isPremium'] ) || ! empty( $intent['maxPrice'] ) ) {
			usort( $products, static fn( $a, $b ) => (float) $b['price'] <=> (float) $a['price'] );
		} else {
			usort(
				$products,
				static function ( $a, $b ) {
					$diff = (float) $b['average_rating'] <=> (float) $a['average_rating'];
					return 0 !== $diff ? $diff : ( $b['rating_count'] <=> $a['rating_count'] );
				}
			);
		}
		return array_slice( $products, 0, 6 );
	}

	private static function greeting(): array {
		$hour = (int) wp_date( 'G' );
		$time = $hour < 12 ? 'Good morning' : ( $hour < 17 ? 'Good afternoon' : 'Good evening' );
		return array(
			'content'      => "{$time}! ✨ Welcome to Veloria Vault. I'm your personal shopping assistant.\n\nI can help you find the perfect leather bag or wallet. What are you looking for today?",
			'quickReplies' => array( '💼 Find an Office Bag', '👜 Show Tote Bags', '🎁 Gift Ideas', "🔥 What's on Sale?", '💰 Wallets & Clutches' ),
		);
	}

	/** Current checkout settings are the source of truth for shipping costs. */
	private static function shipping_response(): array {
		$cost      = max( 0.0, (float) Settings::get( 'standard_shipping_cost', 0 ) );
		$threshold = max( 0.0, (float) Settings::get( 'free_shipping_threshold', 0 ) );
		$cod_fee   = max( 0.0, (float) Settings::get( 'cod_fee', 0 ) );
		if ( ! Settings::get( 'override_shipping', true ) ) {
			$content = 'Your shipping charge is confirmed at checkout for your address.';
		} elseif ( 0.0 === $cost ) {
			$content = 'Shipping is free on all orders within India.';
		} elseif ( $threshold > 0 ) {
			$content = 'Shipping is free on orders of ₹' . veloria_format_number( $threshold ) . ' or more. Below that, standard shipping is ₹' . veloria_format_number( $cost ) . '.';
		} else {
			$content = 'Standard shipping within India is ₹' . veloria_format_number( $cost ) . '.';
		}
		if ( $cod_fee > 0 ) {
			$content .= "\n\nCash on Delivery has a separate ₹" . veloria_format_number( $cod_fee ) . ' COD fee.';
		}
		$content .= "\n\nDelivery timelines depend on your location. Shipping policy: " . home_url( '/shipping-policy' );
		return array( 'content' => $content, 'quickReplies' => array( 'Track my order', 'Return & Exchange', 'Browse Products' ) );
	}

	private static function product_response( array $products, array $intent ): array {
		if ( ! $products ) {
			return array(
				'content'      => "I couldn't find products matching your criteria. Let me suggest some alternatives:",
				'quickReplies' => array( 'Show all bags', 'Browse Totes', 'Browse Satchels', 'Browse Wallets', "What's on Sale?" ),
			);
		}
		if ( ! empty( $intent['category'] ) ) {
			$label = self::CATEGORY_LABELS[ $intent['category'] ] ?? $intent['category'];
			$intro = "Here are the best {$label} picks for you:";
		} elseif ( ! empty( $intent['maxPrice'] ) ) {
			$intro = 'Here are top picks under ₹' . veloria_format_number( (float) $intent['maxPrice'] ) . ':';
		} elseif ( ! empty( $intent['isPremium'] ) ) {
			$intro = "Here's our premium collection — the finest leather goods:";
		} elseif ( ! empty( $intent['isSale'] ) ) {
			$intro = "🔥 These are currently on sale — grab them before they're gone:";
		} else {
			$intro = 'Here are ' . count( $products ) . " picks I think you'll love:";
		}
		return array(
			'content'      => $intro,
			'quickReplies' => array( 'Show more options', 'Something different', 'Under ₹2000', 'Under ₹3000' ),
		);
	}

	/** @return array<string,mixed> Response payload, same shape as /api/chat/intent. */
	public static function respond( string $message, string $session_id = '' ): array {
		$intent   = self::parse( mb_substr( trim( $message ), 0, 500 ) );
		$products = array();
		$phone    = (string) Settings::get( 'support_phone' );
		$email    = Settings::support_email();

		switch ( $intent['type'] ) {
			case 'shipping':
				$out = self::shipping_response();
				break;
			case 'greeting':
				$out = self::greeting();
				break;
			case 'thanks':
				$options = array(
					"You're welcome! 😊 Let me know if you need anything else.",
					'Happy to help! ✨ Feel free to ask me anything.',
					"My pleasure! 🎀 I'm here whenever you need me.",
				);
				$out = array( 'content' => $options[ array_rand( $options ) ], 'quickReplies' => array() );
				break;
			case 'bye':
				$out = array( 'content' => "Goodbye! 👋 Thanks for visiting Veloria Vault. Come back anytime — I'll remember our chat! ✨", 'quickReplies' => array() );
				break;
			case 'help':
				$out = array(
					'content'      => "I'm here to help! 🤝\n\n📞 **Call us:** {$phone}\n📧 **Email:** {$email}\n🕐 **Hours:** Mon-Sat, 10 AM - 7 PM\n\nOr tell me what you need help with:",
					'quickReplies' => array( 'Track my order', 'Return & Exchange', 'Shipping info', 'Browse Products' ),
				);
				break;
			case 'gift':
				if ( ! empty( $intent['maxPrice'] ) || ! empty( $intent['category'] ) ) {
					$products = self::rank( self::fetch_products( $intent ), $intent );
					$resp     = self::product_response( $products, $intent );
					$out      = array( 'content' => "🎁 Great gift ideas!\n\n" . $resp['content'], 'quickReplies' => $resp['quickReplies'] );
				} else {
					$out = array(
						'content'      => "🎁 Great choice! Veloria bags make wonderful gifts. Let me help you find something special.\n\nWhat's the occasion, and do you have a budget in mind?",
						'quickReplies' => array( 'Under ₹2000', 'Under ₹3000', 'Under ₹5000', 'Premium Collection', 'Show me everything' ),
					);
				}
				break;
			default:
				$products = self::rank( self::fetch_products( $intent ), $intent );
				$out      = self::product_response( $products, $intent );
		}

		return array(
			'content'      => $out['content'],
			'products'     => array_map(
				static fn( $p ) => array(
					'id'               => $p['id'],
					'name'             => $p['name'],
					'slug'             => $p['slug'],
					'price'            => $p['price'],
					'regularPrice'     => $p['regular_price'],
					'salePrice'        => $p['sale_price'],
					'onSale'           => $p['on_sale'],
					'image'            => $p['images'][0]['src'] ?? '',
					'category'         => $p['categories'][0]['name'] ?? '',
					'shortDescription' => mb_substr( trim( (string) preg_replace( '/&[a-z]+;/i', ' ', wp_strip_all_tags( $p['short_description'] ) ) ), 0, 120 ),
					'stockStatus'      => $p['stock_status'],
					'type'             => $p['type'],
					'href'             => '/product/' . $p['slug'],
				),
				$products
			),
			'quickReplies' => $out['quickReplies'],
			'intent'       => $intent['type'],
			'sessionId'    => '' !== $session_id ? $session_id : null,
		);
	}
}
