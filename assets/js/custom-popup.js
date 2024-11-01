jQuery(document).ready(function (){
    jQuery(".wizp-popup-open").click(function (){
        jQuery(".wizp-pop-outer").fadeIn("slow");
    });
    jQuery(".wizp-popup-close").click(function (){
        jQuery(".wizp-pop-outer").fadeOut("slow");
    });

    wizpay_v_product_pricing_watcher();
});

jQuery(document.body).on('removed_from_cart updated_cart_totals', function () {
    jQuery(".wizp-popup-open").click(function (){
        jQuery(".wizp-pop-outer").fadeIn("slow");
    });
    jQuery(".wizp-popup-close").click(function (){
        jQuery(".wizp-pop-outer").fadeOut("slow");
    });
});



var Wizpay_Widgets_PaymentSchedule = function(containerId, amount, installments){
    var htmlElement = jQuery('#' + containerId);
    if(htmlElement){
        var innerHtml = `
            <h5 style="text-align: center;font-size: 16px;">4 x interest free fortnightly instalments totalling  
                $` + amount.toFixed(2) + `
                <a target="_blank" href="https://info.wizpay.com.au/HowItWorks/HowItWorks.html" class="wizp-popup-open">learn more</a>
            </h5>
            <div class="clear"></div>
            <div class="wizp-custom-payfields">
                <div class="wizp-row">
                    <div class="wizp-col-3 wizp-col-sm-6">
                        <p class="wizp-installment1" style="font-size: 18px;">
                            $` + installments.toFixed(2) + `
                        </p>
                        <div class="wizp-installment1">
                            <svg width="25" height="25" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="3" cy="3" r="3" fill="#6F00FF"/>
                                <circle cx="13" cy="3" r="3" fill="#EDEDF4"/>
                                <circle cx="3" cy="13" r="3" fill="#EDEDF4"/>
                                <circle cx="13" cy="13" r="3" fill="#EDEDF4"/>
                            </svg>
                        </div>
                        <p>First Payment</p>
                    </div>
                    <div class="wizp-col-3 wizp-col-sm-6">
                        <p class="wizp-installment2" style="font-size: 18px;">
                            $` + installments.toFixed(2) + `
                        </p>
                        <div class="wizp-installment2">
                            <svg width="25" height="25" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="3" cy="3" r="3" fill="#6F00FF"/>
                                <circle cx="13" cy="3" r="3" fill="#6F00FF"/>
                                <circle cx="3" cy="13" r="3" fill="#EDEDF4"/>
                                <circle cx="13" cy="13" r="3" fill="#EDEDF4"/>
                            </svg>
                        </div>
                        <p>2 weeks later</p>
                    </div>
                    <div class="wizp-col-3 wizp-col-sm-6">
                        <p class="wizp-installment3" style="font-size: 18px;">
                            $` + installments.toFixed(2) + `
                        </p>
                        <div class="wizp-installment3">
                            <svg width="25" height="25" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="3" cy="3" r="3" fill="#6F00FF"/>
                                <circle cx="13" cy="3" r="3" fill="#6F00FF"/>
                                <circle cx="3" cy="13" r="3" fill="#6F00FF"/>
                                <circle cx="13" cy="13" r="3" fill="#EDEDF4"/>
                            </svg>
                        </div>
                        <p>4 weeks later</p>
                    </div>
                    <div class="wizp-col-3 wizp-col-sm-6">
                        <p class="wizp-installment4" style="font-size: 18px;">
                            $` + installments.toFixed(2) + `
                        </p>
                        <div class="wizp-installment4">
                            <svg width="25" height="25" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="3" cy="3" r="3" fill="#6F00FF"/>
                                <circle cx="13" cy="3" r="3" fill="#6F00FF"/>
                                <circle cx="3" cy="13" r="3" fill="#6F00FF"/>
                                <circle cx="13" cy="13" r="3" fill="#6F00FF"/>
                            </svg>
                        </div>
                        <p>6 weeks later</p>
                    </div>
                </div>
            </div>
            <div class="clear"></div>
        `;

        htmlElement.html(innerHtml);


        jQuery(".wizp-popup-open").click(function (){
            jQuery(".wizp-pop-outer").fadeIn("slow");
        });
        jQuery(".wizp-popup-close").click(function (){
            jQuery(".wizp-pop-outer").fadeOut("slow");
        });
    }
}




var wizpay_v_product_pricing_watcher = function (){
    // do variation_price update
    // 1. watch variation_price change
    // Options for the observer (which mutations to observe)
    var config = { aattributes: true, childList: true, subtree: true, characterData:true};
    // Function to convert
    var currencyToNumber = function(currency){
        var k, temp;
        try{
            // Loop to make substring
            for(var i = 0; i < currency.length; i++){
                
                // Getting Unicode value
                k = currency.charCodeAt(i);
                
                // Checking whether the character
                // is of numeric type or not
                if(k > 47 && k < 58){
                    
                    // Making substring
                    temp = currency.substring(i);
                    break;
                }
            }
            
            // If currency is in format like
            // 458, 656.75 then we used replace
            // method to replace every ', ' with ''
            temp = temp.replace(/, /, '');
            
            // Converting string to float
            // or double and return
            return parseFloat(temp);   
        } catch(error){
            return 0;
        }
        
    };

    var cartTotalNodes = document.getElementsByClassName('single_variation_wrap');
    var default_text_holder = document.getElementById('wizpay-price-range-holder');

    var default_text = '';
    if(default_text_holder){
        default_text = default_text_holder.innerHTML;
    }

    var wizpay_min = document.getElementById('wizpay_merchant_minimum_amount');
    var wizpay_max = document.getElementById('wizpay_merchant_maximum_amount');

    var wizpay_min_price = -1;
    var wizpay_max_price = -1;

    if(wizpay_min && wizpay_max){
        wizpay_min_price = parseFloat(wizpay_min.value);
        wizpay_max_price = parseFloat(wizpay_max.value);
    }

    if(cartTotalNodes && cartTotalNodes.length > 0){
        var cartTotalNode = cartTotalNodes[0];
        var callbackCart = function(mutationsList) {
            for(var mutation of mutationsList) {
                var newPriceNodes = cartTotalNode.getElementsByClassName('woocommerce-Price-amount amount');
                
                if(newPriceNodes && newPriceNodes.length > 0){
                    var newPriceNode = newPriceNodes[0];
                    
                    if(newPriceNode){
                        var total = currencyToNumber(newPriceNode.textContent);  
                        
                        if(total > 0){
                            // re-calc wizpay value
                            var priceElement = document.getElementById('wizpay-price-range-holder');
                            
                            if(wizpay_min_price > 0 && wizpay_max_price > 0
                                && priceElement
                                && wizpay_min_price <= total
                                && total <= wizpay_max_price){
                                    priceElement.innerHTML = '&nbsp;or 4 payments of $' + (total / 4).toFixed(2);
                                }
                            else if(priceElement && default_text){
                                priceElement.innerHTML = default_text;
                            }
                        }
                    }
                }
            }
        };

        // Create an observer instance linked to the callback function
        var observerCart = new MutationObserver(callbackCart);    
        // Start observing the target node for configured mutations
        observerCart.observe(cartTotalNode, config);  

    }
};