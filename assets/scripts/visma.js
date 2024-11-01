function sync_orders(nonce) {
    var data = {
        action: 'sync_orders',
        security: nonce
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många ordrar som ska exporteras. Ett meddelande visas på denna sida när synkroniseringen är klar.');

    jQuery.post(ajaxurl, data, function(response) {
        alert(response);
    }, 'json');
}

function fetch_contacts(nonce) {
    var data = {
        action: 'fetch_contacts',
        security: nonce
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många kunder som ska importeras. Ett meddelande visas på denna sida när synkroniseringen är klar');

    jQuery.post(ajaxurl, data, function(response) {
        alert(response);
    });
}

function manual_sync_products(nonce) {
    var data = {
        action: 'manual_sync_products',
        security: nonce
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många produkter som ska exporteras. Ett meddelande visas på denna sida när synkroniseringen är klar.');
    jQuery.post(ajaxurl, data, function(response) {

        var products = JSON.parse(response);
        jQuery('#ajax-visma-notification').show();
        for (index = 0; index < products.length; ++index) {
            try{
                var resp = JSON.parse(sync_multi_product(products[index], nonce));
                jQuery('#ajax-visma-message').html('WooCommerce Visma: Synkar ' + (index + 1) + ' av ' + products.length);

                if(resp['success'] == false){
                    jQuery('#ajax-visma-notification').append('<p id="ajax-error-visma-message" class="error">Fel på produkt ' + products[index] + ': ' + resp['message'] + '</p>');
                }
            }
            catch(err) {

            }
        }
    });
}

function manual_diff_sync_orders(nonce) {
    var data = {
        action: 'manual_diff_sync_orders',
        security: nonce
    };
    jQuery.post(ajaxurl, data, function(response) {

        var orders = JSON.parse(response);
        jQuery('#ajax-visma-notification').show();
        for (index = 0; index < orders.length; ++index) {
            try{
                var resp = JSON.parse(sync_multi_order(orders[index], nonce));
                jQuery('#ajax-visma-message').html('WooCommerce Visma: Synkar ' + (index + 1) + ' av ' + orders.length);

                if(resp['success'] == false){
                    jQuery('#ajax-visma-notification').append('<p id="ajax-error-visma-message" class="error">Fel på order ' + orders[index] + ': ' + resp['message'] + '</p>');
                }
            }
            catch(err) {

            }
        }
    });
}

function update_visma_inventory(nonce) {
    var data = {
        action: 'update_visma_inventory',
        security: nonce
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många produkters lagesaldo som ska importeras. Ett meddelande visas på denna sida när synkroniseringen är klar.');
    jQuery.post(ajaxurl, data, function(response) {

    });
}

function send_support_mail(nonce) {
    var data = jQuery('form#support').serialize();
    jQuery.post(ajaxurl, data, function(response) {
        alert(response);
    });
}

function missing_list(nonce) {
    var data = {
        action: 'missing_list',
        security: nonce
    };
    jQuery.post(ajaxurl, data, function(response) {
        alert(response);
    });
}

function clean_sku(nonce) {
    var data = {
        action: 'clean_sku',
        security: nonce
    };
    alert('Ett meddelande visas på denna sida när operationen är klar.');
    jQuery.post(ajaxurl, data, function(response) {
        alert(response);
    });
}

function sync_all_orders(nonce) {
    var data = {
        action: 'sync_all_orders',
        security: nonce
    };
    alert('Synkroniseringen kan ta lång tid beroende på hur många ordrar som ska exporteras. Ett meddelande visas på denna sida när synkroniseringen är klar.');

    jQuery.post(ajaxurl, data, function(response) {
        var orders = JSON.parse(response);
        jQuery('#ajax-visma-notification').show();
        for (index = 0; index < orders.length; ++index) {
            try{
                var resp = JSON.parse(sync_multi_order(orders[index], nonce));
                jQuery('#ajax-visma-message').html('WooCommerce Visma: Synkroniserar order ' + (index + 1) + ' av ' + orders.length);

                if(resp['success'] == false){
                    jQuery('#ajax-visma-notification').append('<p id="ajax-error-visma-message" class="error">Fel på order ' + orders[index] + ': ' + resp['message'] + '</p>');
                }
            }
            catch(err) {

            }
        }
    });

}

function check_products_diff(nonce) {
    var data = {
        action: 'check_products_diff',
        security: nonce
    };

    jQuery.post(ajaxurl, data, function(response) {
        var products = JSON.parse(response);
        jQuery('#ajax-visma-notification').show();
        var missing = 0;
        for (index = 0; index < products.length; ++index) {
            try{
                var resp = JSON.parse(check_diff(products[index], nonce));
                jQuery('#ajax-visma-message').html('WooCommerce Visma: Behandlar produkt ' + (index + 1) + ' av ' + products.length + '<br> Antal saknade: ' + missing);

                if(resp['success'] == false){
                    missing++;
                    jQuery('#ajax-visma-notification').append('<p id="ajax-error-visma-message" class="error">' + resp['title'] + '; ' + resp['sku'] + '; ' + resp['product_id'] + '</p>');
                }
            }
            catch(err) {

            }
        }
    });
}

function sync_order(orderId, nonce) {
    var data = {
        action: 'sync_order',
        security: nonce,
        order_id: orderId
    };
    jQuery.post(ajaxurl, data, function(response) {
        jQuery('#ajax-visma-notification').show();
        jQuery('#ajax-visma-message').html('WooCommerce Visma: ' + response['message']);

        jQuery('html,body').animate({scrollTop: jQuery('#ajax-visma-notification').offset().top - 100 });

        if(response['success'] == false){
            jQuery('#ajax-visma-notification')
                .removeClass('updated')
                .addClass('error');
            if(response['link']){
                jQuery('#ajax-visma-message').append('<a href="http://wp-plugs.com/woocommerce-visma/' + response['link'] + '"> Se info</a>');
            }
        }
        window.setTimeout(function(){

            // Move to a new location or you can do something else
            window.location.reload();

        }, 5000);
    }, 'json');
}

function sync_product(productId, nonce) {
    var data = {
        action: 'sync_product',
        security: nonce,
        product_id: productId
    };
    jQuery.post(ajaxurl, data, function(response) {
        jQuery('#ajax-visma-notification').show();
        jQuery('#ajax-visma-message').html('WooCommerce Visma: ' + response['message']);
        jQuery('html,body').animate({scrollTop: jQuery('#ajax-visma-notification').offset().top - 100 });
        if(response['success'] == false){
            jQuery('#ajax-visma-notification')
                .removeClass('updated')
                .addClass('error');
            if(response['link']){
                jQuery('#ajax-visma-message').append('<a href="http://wp-plugs.com/woocommerce-visma/' + response['link'] + '"> Se info</a>');
            }
        }
        else{
            jQuery('#post-productId');
        }
        window.setTimeout(function(){

            // Move to a new location or you can do something else
            window.location.reload();

        }, 5000);

    }, 'json');
}

function sync_multi_product(productId, nonce) {
    var data = {
        action: 'sync_product',
        security: nonce,
        product_id: productId
    };
    var ajax_response;
    ajax_response = jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: data,
        async:false
    });
    return ajax_response.responseText;
}

function sync_multi_order(orderId, nonce) {
    var data = {
        action: 'sync_order',
        security: nonce,
        order_id: orderId
    };
    var ajax_response;
    ajax_response = jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: data,
        async:false
    });
    return ajax_response.responseText;
}

function set_product_as_unsynced(productId, nonce) {
    var data = {
        action: 'set_product_as_unsynced',
        security: nonce,
        product_id: productId
    };
    jQuery.post(ajaxurl, data, function(response) {
       window.location.reload();
    });
}

function clear_accesstoken(nonce) {
    var data = {
        action: 'clear_accesstoken',
        security: nonce
    };
    jQuery.post(ajaxurl, data, function(response) {
    });
}

function check_diff(productId, nonce) {
    var data = {
        action: 'check_diff',
        security: nonce,
        product_id: productId
    };
    var ajax_response;
    ajax_response = jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: data,
        async:false
    });
    return ajax_response.responseText;
}

function clean_customer_table(nonce) {
    var data = {
        action: 'clean_customer_table',
        security: nonce
    };
    jQuery.post(ajaxurl, data, function(response) {
        response = JSON.parse(response);
        jQuery('#ajax-visma-notification').show();
        console.log(response);
        if(response['success'] == false){
            jQuery('#ajax-visma-notification').append('<p id="ajax-error-visma-message" class="error">' + response['message'] + '</p>');
        }
        else{
            jQuery('#ajax-visma-message').html(response['message']);
        }
    });
}
