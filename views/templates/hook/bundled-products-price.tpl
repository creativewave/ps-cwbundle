{if 0 == $errors|@count}
    {if !$priceDisplay or 2 == $priceDisplay}
        {assign var='productPrice' value=$product->getPrice(true, $smarty.const.NULL, 6)}
        {assign var='productPriceWithoutReduction' value=$product->getPriceWithoutReduct(false, $smarty.const.NULL)}
    {elseif 1 == $priceDisplay}
        {assign var='productPrice' value=$product->getPrice(false, $smarty.const.NULL, 6)}
        {assign var='productPriceWithoutReduction' value=$product->getPriceWithoutReduct(true, $smarty.const.NULL)}
    {/if}
{/if}

{if 0 <= $priceDisplay and $priceDisplay <= 2 }
<span class="bundled-product-price">{convertPrice price=$productPrice|floatval}</span>
{if $tax_enabled and isset($display_tax_label) and 1 == $display_tax_label}
<span class="bundled-product-tax-label">{if 1 == $priceDisplay}{l s='tax excl.' mod='cwbundle'}{else}{l s='tax incl.' mod='cwbundle'}{/if}</span>
{/if}
{/if}
{if 2 == $priceDisplay}
<span>{convertPrice price=$product->getPriceStatic($product.id, false)}</span> {l s='tax excl.' mod='cwbundle'}
{/if}

{if $product->specificPrice}
{if $product->specificPrice.reduction and $productPrice < $productPriceWithoutReduction}
{if 0 <= $priceDisplay and $priceDisplay <= 2 }
<span class="bundled-product-old-price">{convertPrice price=$productPriceWithoutReduction|floatval}</span>
{if $tax_enabled and isset($display_tax_label) and 1 == $display_tax_label}
<span class="bundled-product-old-price-tax-label">{if $priceDisplay == 1}{l s='tax excl.' mod='cwbundle'}{else}{l s='tax incl.' mod='cwbundle'}{/if}</span>
{/if}
{/if}
{/if}
{if isset($discount_percentage) and 2 > $discount_percentage and 0 < $productPriceWithoutReduction}
{if 'percentage' === $product->specificPrice.reduction_type}
<span class="bundled-product-sale-percentage">-{$product->specificPrice.reduction * 100}%</span>
{/if}
{if 'amount' === $product->specificPrice.reduction_type and 0 != $product->specificPrice.reduction|floatval}
<span class="bundled-product-sale-amount">-{convertPrice price=$productPriceWithoutReduction|floatval-$productPrice|floatval}</span>
{/if}
{/if}
{/if}

{if $packItems|@count and $productPrice < $product->getNoPackPrice()}
<span class="bundled-product-pack-price">{l s='Instead of' mod='cwbundle'} <span style="text-decoration: line-through;">{strip}
    {convertPrice price=$product->getNoPackPrice()}
{/strip}</span></span>
{/if}

{if 0 != $product->ecotax}
<span class="bundled-product-ecotax-label">{l s='Including' mod='cwbundle'} <span>{strip}
{if 2 == $priceDisplay}
    {$ecotax_tax_exc|convertAndFormatPrice}
{else}
    {$ecotax_tax_inc|convertAndFormatPrice}
{/if}
{/strip}</span> {l s='for green tax' mod='cwbundle'}
{if $product->specificPrice and $product->specificPrice.reduction}
<br />{l s='(not impacted by the discount)' mod='cwbundle'}
{/if}
<span>
{/if}

{if !empty($product->unity) and 0.000000 < $product->unit_price_ratio}
{math equation="pprice / punit_price" pprice=$productPrice punit_price=$product->unit_price_ratio assign=unit_price}
<span class="bundled-product-unit-price">{convertPrice price=$unit_price}</span> {l s='per' mod='cwbundle'} {$product->unity|escape:'html':'UTF-8'}
{/if}
