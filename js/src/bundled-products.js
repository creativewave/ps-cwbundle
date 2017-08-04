
/* global window */
/* global bundledProducts */
/* global displayPrice */
/* global productPriceTaxIncluded */
/* global productPriceTaxExcluded */
/* global taxRate */

import flow from 'lodash/flow'

const init = () => {

    const getBundledProductsPriceTaxExcluded = flow(
        getSelectedProducts,
        getSelectedProductsIds,
        getProductsFromListById(bundledProducts),
        getProductsPrices,
        getTotal,
    )
    const handleSelectBundledProduct = flow(
        getBundledProductsPriceTaxExcluded,
        setPrices(productPriceTaxExcluded) // Impure
    )

    document.getElementById('bundle').addEventListener('change', handleSelectBundledProduct)
}

const getSelectedProducts = ({ currentTarget, target }) => {
    switch (target.type) {
        case 'checkbox':
            return [...currentTarget.querySelectorAll('[name="bundled_products_ids[]"]:checked')]
        case 'number':
            return [...currentTarget.querySelectorAll('[name="bundled_products_ids[]"]')]
                .filter($bundledProduct => 0 < $bundledProduct.nextElementSibling.value)
    }
}
const getSelectedProductsIds = elements => elements.map($element => $element.value)
const getProductsFromListById = list => ids => ids.map(id => list[id])
const getProductsPrices = products => products.map(product => +product.price)
const getTotal = prices => prices.reduce((sum, price) => sum += price, 0)

const setPrices = initialProductPriceTaxExcluded => bundledProductsPriceTaxExcluded => {

    const bundledProductsPriceTaxIncluded = bundledProductsPriceTaxExcluded * (1 + taxRate / 100)
    const $specificPrices = [...document.querySelectorAll('#quantityDiscount table tbody tr')]

    /* eslint-disable no-global-assign */
    productPriceTaxExcluded = initialProductPriceTaxExcluded + bundledProductsPriceTaxExcluded
    productPriceTaxIncluded = productPriceTaxExcluded * (1 + taxRate / 100)
    /* eslint-enable no-global-assign */

    if ($specificPrices.length) {
        $specificPrices
            .filter($el => 'none' !== $el.style.display)
            .forEach($row => $row.setAttribute(
                'real-discount-value',
                $row.getAttribute('real-discount-value') + (
                    1 === displayPrice
                        ? bundledProductsPriceTaxExcluded
                        : bundledProductsPriceTaxIncluded
                )
            ))
    } else {
        document.getElementById('our_price_display')
            .setAttribute('content', productPriceTaxIncluded)
        document.getElementById('quantity_wanted')
            .dispatchEvent(new Event('change', { 'bubbles': true, 'cancelable': true }))
    }
}

// Bundled products are not defined when product isn't available for order.
window.bundledProducts = window.bundledProducts || {}

if (0 < Object.keys(bundledProducts).length) {
    document.addEventListener('DOMContentLoaded', init)
}
