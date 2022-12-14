{include file="common/subheader.tpl" title=__("only_one_product") target="#only_one_product"}
<div id="only_one_product" >
    <div class="control-group">
        <label class="control-label" for="only_one_product">{__("allow_to_buy_this_product_only_once")}</label>
        <div class="controls">
         <input type="hidden" name="product_data[only_one_product]" value="N"/>  
        <input type="checkbox" 
            id="only_one_product" 
            name="product_data[only_one_product]"
            value="Y"  
            {if $product_data.only_one_product === "Y"}
                checked="checked"
            {/if}/> 
        </div>
    </div>
</div>
