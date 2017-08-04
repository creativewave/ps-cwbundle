
/* global window */
/* global $ */
/* global display_multishop_checkboxes */
/* global hideOtherLanguage */
/* global id_language */
/* global id_product */
/* global languages */
/* global ProductMultishop */
/* global tabs_manager */

import flow from 'lodash/flow'

const init = () => {

    const $input  = $('#bundled-products')
    const $output = document.getElementById('bundled-products-output')

    const updateInput  = hasUpdate => hasUpdate && $input.setOptions({
        extraParams: { excludeIds: getProductsIdsToExclude(id_product, $output) },
    }) && $input.val('')
    const updateOutput = content => {

        if (!content) {
            return false
        }

        const $product = document.createElement('div')

        $product.className = 'form-control-static'
        $product.innerHTML = content
        $output.appendChild($product)

        return true
    }

    const handleRemoveProduct = flow(removeProduct, updateInput)
    const handleSelectProduct = flow(addProduct, updateOutput, updateInput)

    $output.addEventListener('click', handleRemoveProduct)
    $input
        .autocomplete('ajax_products_list.php', {
            autoFill: true,
            cacheLength: 0,
            max: 10,
            formatItem: ([name, id]) => `${id} - ${name}`,
        })
        .setOptions({ extraParams: { excludeIds: getProductsIdsToExclude(id_product, $output) } })
        .result(handleSelectProduct)

    if (display_multishop_checkboxes) { // eslint-disable-line camelcase

        const getProductsIds = () => getElementsIds('[id^=remove-bundled-product]').concat('bundled-products')
        const getHeadlineIds = () => getElementsIds('[id^=headline]')
        const toggleProducts = toggleFields(getProductsIds, ProductMultishop.checkField)
        const toggleHeadline = toggleFields(getHeadlineIds, ProductMultishop.checkField)

        const $checkboxProducts = document
            .querySelector('[name="multishop_check[bundled-products]"]')
        const $checkboxesHeadlines = languages
            .map(l => document.querySelector(`[name="multishop_check[headline][${l.id_lang}]"]`))

        const checkAllFields = () => {
            toggleProducts({ target: $checkboxProducts })
            $checkboxesHeadlines.map($chechbox => toggleHeadline({ target: $chechbox }))
        }

        $checkboxProducts.addEventListener('change', toggleProducts)
        $checkboxesHeadlines.map($chechbox => $chechbox.addEventListener('change', toggleHeadline))
        ProductMultishop.checkAllModuleCwbundle = checkAllFields
        ProductMultishop.checkAllModuleCwbundle()
    }

    if (tabs_manager.allow_hide_other_languages) { // eslint-disable-line camelcase
        hideOtherLanguage(id_language)
    }
}

const getProductsIdsToExclude = (initial, $output) =>
    [...$output.querySelectorAll('[name="bundled_products_ids[]"]')]
        .map($input => $input.value)
        .concat(initial)
        .join(',')
const removeProduct = ({ currentTarget, target }) => {
    switch (target.tagName) {
        case 'I':
            return removeProduct({ currentTarget, target: target.parentNode })
        case 'BUTTON':
            return currentTarget.removeChild(target.parentNode)
        default:
            return false
    }
}
const addProduct = (e, [name, id]) => id && name && `
    <input type="hidden" name="bundled_products_ids[]" value="${id}">
        <button type="button" id="remove-bundled-product-${id}" class="btn btn-default">
        <i class="icon-remove text-danger"></i>
    </button> ${name}
`
const getElementsIds = selector => [...document.querySelectorAll(selector)].map($el => $el.id)
const toggleFields = (getFieldsIds, checkField) => e =>
    getFieldsIds().map(id => checkField(e.target.checked, id))

window.CW = window.CW || {}
window.CW.Bundle = { init }
