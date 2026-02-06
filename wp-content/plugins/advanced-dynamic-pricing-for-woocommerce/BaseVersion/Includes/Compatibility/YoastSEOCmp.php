<?php

namespace ADP\BaseVersion\Includes\Compatibility;

defined('ABSPATH') or exit;

/**
 * Plugin Name: Yoast SEO
 * Author: Team Yoast
 *
 * @see https://yoast.com/#utm_term=team-yoast&utm_content=plugin-info
 */
class YoastSEOCmp
{
    static function isNewPriceSpecification($priceSpecification = array()) {
        if(is_array($priceSpecification) && array_key_exists('@type', $priceSpecification) && $priceSpecification['@type'] === 'PriceSpecification') {
            return false;
        }

        return defined( 'WPSEO_WOO_VERSION' ) && version_compare( WPSEO_WOO_VERSION, '16.5' ) >= 0;
    }

    public function applyCompatibility()
    {
        add_action("adp_schema_data_ready", function($data, $processedProduct, $decimals){

            add_filter( 'wpseo_schema_product', function($wpseo_data) use ($data) {
                if (isset($wpseo_data['hasVariant']) || !isset($wpseo_data['offers'])) {
                    return $wpseo_data;
                }

                $isNewPriceSpecification = $this::isNewPriceSpecification($wpseo_data['offers'][0]['priceSpecification']);
                
                if ($isNewPriceSpecification) {
                    $priceSpecification = $wpseo_data['offers'][0]['priceSpecification'][0];
                } else {
                    $priceSpecification = $wpseo_data['offers'][0]['priceSpecification'];
                }

                if ( isset( $priceSpecification['price']) && isset($data['price']) ) {
                    $priceSpecification['price'] = $data['price'];

                    if ($isNewPriceSpecification) {
                        $wpseo_data['offers'][0]['priceSpecification'] = [ $priceSpecification ];
                    } else {
                        $wpseo_data['offers'][0]['priceSpecification'] = $priceSpecification;
                    }
                }

                return $wpseo_data;
            });

            add_filter('wpseo_schema_offer', function($offer) use ($processedProduct, $decimals) {
                $childPrices = YoastSEOCmp::getChildPrices($processedProduct, $decimals);
                
                if(!isset($childPrices) || count($childPrices) === 0) {
                    return $offer;
                }

                $newPriceSpecification = $offer['priceSpecification'];
                $originalPriceSpecification = $newPriceSpecification;

                $isNewPriceSpecification = $this::isNewPriceSpecification($offer['priceSpecification']);

                if ($isNewPriceSpecification) {
                    $originalPriceSpecification = $newPriceSpecification[0];
                }

                foreach($childPrices as $child) {
                    $specPrices = $this::getSpecificationPrices($newPriceSpecification);

                    if(
                        isset($child['priceOriginal'], $child['price'], $child['url'])
                        && $child['priceOriginal'] !== $child['price']
                        && $offer['url'] === $child['url']
                        && !in_array(floatval($child['price']), $specPrices)
                    ) {
                        $newSpecUnit = array(
                            '@type' => "UnitPriceSpecification",
                            "price" => $child['price'],
                            "priceCurrency" => $originalPriceSpecification['priceCurrency']
                        );

                        if ($isNewPriceSpecification) {
                            $newPriceSpecification[] = $newSpecUnit;
                        } else {
                            $newPriceSpecification = $newSpecUnit;
                        }
                    }
                }

                if ($isNewPriceSpecification && count($newPriceSpecification) > 1) {
                    $maxPriceSpec = $newPriceSpecification[0];
                    $minPriceSpec = $newPriceSpecification[0];

                    foreach($newPriceSpecification as $priceSpec) {
                        if (floatval($priceSpec['price']) >= floatval($maxPriceSpec['price'])) {
                            $maxPriceSpec = $priceSpec;
                        } else {
                            $minPriceSpec = $priceSpec;
                        }
                    }

                    if ($maxPriceSpec['price'] == $minPriceSpec['price']) {
                        unset($minPriceSpec['priceType']);
                        $newPriceSpecification = [ $minPriceSpec ];
                    } else {
                        $newPriceSpecification = [
                            array_merge($maxPriceSpec, [
                                'priceType' => 'https://schema.org/ListPrice'
                            ]),
                            array_merge($minPriceSpec, [
                                'priceType' => 'https://schema.org/SalePrice'
                            ]),
                        ];
                    }
                }

                $offer['priceSpecification'] = $newPriceSpecification;
                return $offer;
            });
        }, 10, 3);
    }

    /**
     * @param $processedProduct
     * @param $decimals
     *
     * @return array
     */
    private static function getChildPrices($processedProduct, $decimals) {
        $childPrices = array();
        foreach ($processedProduct->getChildren() as $child) {
            $price = $child->getPrice();
            $priceOriginal = $child->getOriginalPrice();
            $childProduct = $child->getProduct();
            $childPrices[] = [
                'price' => wc_format_decimal($price, $decimals),
                'priceOriginal' => wc_format_decimal($priceOriginal, $decimals),
                'url' => $childProduct->get_permalink(),
            ];
        }
        return $childPrices;
    }

    /**
     * @param $priceSpecifications
     *
     * @return array
     */
    private static function getSpecificationPrices($priceSpecifications) {
        if (!isset($priceSpecifications[0])) {
            return [ $priceSpecifications['price'] ];
        }
        return array_map(function ($spec) {
            return floatval($spec['price']);
        }, $priceSpecifications);
    }

    public function isActive()
    {
        return defined('WPSEO_WOO_VERSION') && defined('WPSEO_BASENAME');
    }
}
