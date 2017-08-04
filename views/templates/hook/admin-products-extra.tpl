<div id="product-bundle" class="panel product-tab">
    <input type="hidden" name="submitted_tabs[]" value="{$tab_name}">
    <h3>{l s='Bundled Products' mod='cwbundle'}</h3>
    {include file="controllers/products/multishop/check_fields.tpl" product_tab=$tab_name}
    <div class="form-group">
        <div class="col-lg-1">
            <span class="pull-right">
                {include
                    file="controllers/products/multishop/checkbox.tpl"
                    field="headline"
                    type="default"
                    multilang="true"
                }
            </span>
        </div>
        <label class="control-label col-lg-2" for="headline_{$id_lang}">
            <span class="label-tooltip" data-toggle="tooltip" title="{l s='The public headline for this bundle.' mod='cwbundle'}">
                {l s='Headline' mod='cwbundle'}
            </span>
        </label>
        <div class="col-lg-5">
            {include
                file='controllers/products/input_text_lang.tpl'
                languages=$languages
                input_value=$headline
                input_name='headline'
            }
            {if $is_multistore}
            <p class="help-block">{strip}
                {l s='This represents the values associated to the default shop for the current multistore context: ' mod='cwbundle'}
                <strong>{$shop->name}.</strong>
            {/strip}</p>
            {/if}
        </div>
    </div>
    <div class="form-group">
        <div class="col-lg-1">
            <span class="pull-right">
                {include
                    file="controllers/products/multishop/checkbox.tpl"
                    field="bundled-products"
                    type="default"
                }
            </span>
        </div>
        <label class="control-label col-lg-2" for="bundled-products">
            <span class="label-tooltip" data-toggle="tooltip" data-original-title="{l s='You can indicate existing products as bundled with this product.' mod='cwbundle'} {l s='Start by typing the first letters of the product\'s name, then select the product from the drop-down list.' mod='cwbundle'} {l s='Do not forget to save the product afterwards!' mod='cwbundle'}">
                {l s='Bundled Products' mod='cwbundle'}
            </span>
        </label>
        <div class="col-lg-5">
            <div id="ajax_choose_product">
                <div class="input-group">
                    <input type="text" name="bundled_products" id="bundled-products">
                    <span class="input-group-addon"><i class="icon-search"></i></span>
                </div>
            </div>
            <div id="bundled-products-output">
                {foreach from=$products item=product}
                <div class="form-control-static">
                    <input type="hidden" name="bundled_products_ids[]" value="{$product->id}">
                    <button type="button" id="remove-bundled-product-{$product->id}" class="btn btn-default">
                        <i class="icon-remove text-danger"></i>
                    </button>
                    {$product->name[$id_lang]|escape:'html':'UTF-8'}{if !empty($product->reference)} {l s='(ref: %s)' sprintf=$product->reference mod='cwbundle'}{/if}
                </div>
                {/foreach}
            </div>
            {if $is_multistore}
            <p class="help-block">{strip}
                {l s='This represents the values associated to the default shop for the current multistore context: ' mod='cwbundle'}
                <strong>{$shop->name}.</strong>
            {/strip}</p>
            {/if}
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='cwbundle'}</a>
        <button name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save' mod='cwbundle'}</button>
        <button name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled"><i class="process-icon-loading"></i> {l s='Save and stay' mod='cwbundle'}</button>
    </div>
</div>

{* @see https://github.com/PrestaShop/PrestaShop/pull/8139 *}
<script>CW.Bundle.init()</script>
