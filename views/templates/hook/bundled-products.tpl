{addJsDef bundledProducts=$products}
{if !empty($products)}
<div id="bundle" class="bundle clearfix">
    <div class="bundle-headline">{$headline}</div>
    <ul class="bundled-products">
    {foreach $products as $product}
    {if $product->available_for_order and $allow_oosp or 0 < $product->quantity}
    {if !isset($restricted_country_mode) or !$restricted_country_mode}
    <li class="bundled-product">
        {if $options.display_quantity}
        <input type="hidden" name="bundled_products_ids[]" value="{$product->id}">
        <input type="number" name="bundled_product_{$product->id}_qty" value="{if 1 < $product->minimal_quantity}{$product->minimal_quantity}{else}0{/if}" min="0"{if !$allow_oosp} max="{$product->quantity}"{/if} data-on-change="updatePrice" class="bundled-product-qty">
        <a href="{$link->getProductLink($product->id)}" title="{l s='More' mod='cwbundle'}" target="_blank" class="bundled-product-name">
        {else}
        <label for="bundled-product-{$product->id}" class="bundled-product-name">
            <input type="checkbox" name="bundled_products_ids[]" value="{$product->id}" id="bundled-product-{$product->id}" data-on-change="updatePrice">
        {/if}
            {if $options.display_image and $product->cover}
            <img src="{$link->getImageLink($product->link_rewrite, $product->cover.id_image, 'small_default')|escape:'html':'UTF-8'}" alt="{$product->name}">
            {/if}
            {$product->name}{if $options.display_price and $product->show_price} - {include file="./bundled-products-price.tpl"}{/if}
        {if $options.display_quantity}</a>{else}</label>{/if}
        {if 1 < $product->minimal_quantity}
        <p>{l s='The minimum purchase order quantity for the product is' mod='cwbundle'} <strong>{$product->minimal_quantity}</strong></p>
        {/if}
    </li>
    {/if}
    {/if}
    {/foreach}
    </ul>
</div>
{/if}
