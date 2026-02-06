import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from "@wordpress/element";
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';


const addEditLink = () => {
    const cartItems = useSelect( (select) => select( storeKey ).getCartData().items );
    useEffect( () => {
        setTimeout( function() {

            if ( cartItems.length > 0 ) {

                // Change product thumbnail.
                const cartHtml = document.querySelector('.wc-block-cart .wc-block-cart__main') ||
                    document.querySelector('.wc-block-cart .wc-block-components-order-summary__content');

                const cartElements = {
                    itemsRow: '.wc-block-cart-items__row',
                    productMetadata: '.wc-block-components-product-metadata',
                    itemLink: '.yith-wccl-cart-container'
                };

                var elements = null;
                var elementHtml = null;

                if (cartHtml !== null) {
                    elementHtml = cartHtml;
                    elements = cartElements;
                }

                if (elements !== null && elementHtml !== null) {
                    const itemsRow = elementHtml.querySelectorAll( elements.itemsRow );

                    itemsRow.forEach( (itemRow, indexRow) => {
                        const editLink = cartItems[indexRow].extensions?.yith_wccl_wc_cart_item_manager.edit_link;
                        const itemLink = itemRow.querySelector(elements.itemLink);
                        if ( editLink && !itemLink ) {

                            const productMetadataDiv = itemRow.querySelector(elements.productMetadata);

                            productMetadataDiv.innerHTML += editLink;
                        }
                    } );
                }
            }

        }, 500 )

    }, [cartItems])

}

registerPlugin( 'yith-wccl-edit-link', {
    render: addEditLink,
    scope: 'woocommerce-checkout',
} );
