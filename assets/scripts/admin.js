;( function( $ ) {
	$( function() {
        var templates = {
            notice: '<div id="visma-message" class="updated notice notice-{{ type }} is-dismissible"><p>{{{ message }}}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>',
        };

        function update_progress_bar(bar, percentage) {
            if (percentage < 0) percentage = 0;
            if (percentage > 100) percentage = 100;
            bar.find('.fill').css('width', percentage + '%');
            percentage = Math.ceil(percentage);
            bar.find('.text').html(percentage + ' %');
        }

        /**
         * Orders sync
         */
        let modals = {
            visma_sync_orders_date_range: async (loader) => {
                let main_button = $('input#visma_order_date_range_btn');
                let description = $('#visma_order_sync_range_progress_description');
                $( '.date-picker-field, .date-picker' ).datepicker({
                    dateFormat: 'yy-mm-dd',
                    numberOfMonths: 1,
                    showButtonPanel: true
                });

                function orderSyncRestoreButton(){
                    setTimeout(()=>{
                        $('#order_sync_date_to').removeAttr('disabled');
                        $('#order_sync_date_from').removeAttr('disabled');
                        main_button.removeClass('button-secondary');
                        main_button.addClass('button-primary');
                        main_button.val('Starta');
                        //main_button.off();
                        main_button.click(orderSyncStartMethod);
                        running=false;
                    },1000);
                }

                function orderSyncBindClose() {
                    $('button.visma_modal_close').off();
                    console.log($('button.visma_modal_close').length);
                    $(document).on("click", "button.visma_modal_close", (e) => {
                        $('.visma_sync_orders_date_range_shade').hide();
                        $('.visma_sync_orders_date_range_modal').hide();
                        loader.css({visibility: "hidden"});
                        $('.visma_order_sync_range_setup').show();
                    });
                }

                orderSyncBindClose();

                $('.visma_sync_orders_date_range_shade').show();
                $('.visma_sync_orders_date_range_modal').show();
                $('button.visma_modal_close').off();
                $('span.order_sync_start').off();

                async function synchronizeOrder (orderId){
                    return $.ajax({
                        url: window.ajaxurl,
                        data: {
                            action: "visma_admin_action",
                            visma_action: "sync_order",
                            order_id: orderId
                        },
                        type: "POST",
                        dataType: "json",
                        success: function (response) {
                        }
                    });
                }

                async function processOrders(orderIds) {
                    $('button.visma_modal_close').off();
                    $('#order_sync_date_to').attr('disabled','');
                    $('#order_sync_date_from').attr('disabled','');
                    $('.visma-order-sync-range-progress').show();
                    $('.visma_order_sync_range_setup').hide();
                    let progress = 0;

                    for (let index = 0; index < orderIds.length; index++) {
                        await synchronizeOrder(orderIds[index]).then(function () {
                            progress = ((index+1)/orderIds.length) * 100
                            update_progress_bar($('.visma_order_sync_range_progress_bar'), progress);
                        });
                    }
                }

                let orderSyncStartMethod = async (e) => { //start function
                    console.log('orderSyncStartMethod')
                    error = false;
                    description.html('');
                    if ("" == $('#order_sync_date_to').val() || "" == $('#order_sync_date_from').val()) {
                        let message = "Please set ranges" +' (' + status + '): ' + error;
                        description.html('<br>' + message);
                        orderSyncRestoreButton();
                        return;
                    }
                    $('.visma_sync_orders_date_range_modal .wc-backbone-modal-main').addClass('hide-footer');

                    await $.ajax({
                        url: window.ajaxurl,
                        data: {
                            action: "visma_admin_action",
                            visma_action: 'visma_sync_orders_date_range',
                            from_date: $('#order_sync_date_from').val(),
                            to_date: $('#order_sync_date_to').val(),
                            status: $('#visma_sync_on_status').val()
                        },
                        success: (response) => {
                        if (response.error) {
                        error = true;
                        description.append('<br><b>'+'Error'+':</b><br>' + response.message);
                        $('.visma-order-sync-range-progress').show();
                        $('.visma_sync_orders_date_range_modal .wc-backbone-modal-main').removeClass('hide-footer');
                    } else {
                        processOrders(response.order_ids)
                    }
                },
                    error: (request, status, error) => {
                        error = true;
                        let message = 'Ett fel uppstod med status' +' (' + status + '): ' + error;
                        description.html('<br>' + message);
                        $('.visma_sync_orders_date_range_modal .wc-backbone-modal-main').removeClass('hide-footer');
                    },
                    dataType: "json"
                });

                };

                orderSyncRestoreButton();
            },
            visma_sync_products: async (loader) => {
                let main_button = $('input#visma_sync_products_btn');
                let description = $('#visma_product_sync_progress_description');

                function productSyncRestoreButton(){
                    setTimeout(()=>{
                        main_button.removeClass('button-secondary');
                        main_button.addClass('button-primary');
                        main_button.val('Start');
                        main_button.off();
                        main_button.click(productSyncStartMethod);
                        running=false;
                    },1000);
                }


            function productSyncBindClose() {
                $('button.visma_modal_close').off();
                console.log($('button.visma_modal_close').length);
                $(document).on("click", "button.visma_modal_close", (e) => {
                    console.log('close');
                    $('.visma_sync_products_shade').hide();
                    $('.visma_sync_products_modal').hide();
                    loader.css({visibility: "hidden"});
                    $('.visma_sync_products_setup').show();
                });
            }

            productSyncBindClose();

            $('.visma_sync_products_shade').show();
            $('.visma_sync_products_modal').show();
            $('button.visma_modal_close').off();
            $('span.order_sync_start').off();

            async function synchronizeProduct (productId){
                description.html('Synkroniserar Product ID: ' + productId);
                return $.ajax({
                    url: window.ajaxurl,
                    data: {
                        action: "visma_admin_action",
                        visma_action: "sync_product",
                        product_id: productId
                    },
                    type: "POST",
                    dataType: "json",
                    success: function (response) {
                    }
                });
            }

            async function processProducts(productIds) {
                $('button.visma_modal_close').off();
                $('.visma-product-sync-progress').show();
                $('.visma_sync_products_setup').hide();
                let progress = 0

                for (let index = 0; index < productIds.length; index++) {
                    await synchronizeProduct(productIds[index]).then(function () {
                        progress = ((index+1)/productIds.length) * 100
                        update_progress_bar($('.visma_sync_products_progress_bar'), progress);
                    });
                }
                description.html('Synkronisering klar');
            }

            let productSyncStartMethod = async (e) => { //start function
                error = false;
                main_button.off();
                description.html('Synkronisering startad');

                $('.visma_sync_products_modal .wc-backbone-modal-main').addClass('hide-footer');

                await $.ajax({
                    url: window.ajaxurl,
                    data: {
                        action: "visma_admin_action",
                        visma_action: 'visma_sync_products',
                    },
                    success: (response) => {
                    if (response.error) {
                    error = true;
                    description.append('<br><b>'+'Error'+':</b><br>' + response.message);
                    $('.visma_sync_products_modal .wc-backbone-modal-main').removeClass('hide-footer');
                } else {
                    processProducts(response.product_ids)
                }
            },
                error: (request, status, error) => {
                    error = true;
                    let message = 'Ett fel uppstod med status'+' (' + status + '): ' + error;
                    description.html('<br>' + message);
                    $('.visma_sync_products_modal .wc-backbone-modal-main').removeClass('hide-footer');
                    return alert(message);
                },
                dataType: "json"
            });

            };

            productSyncRestoreButton();
        }
    };


        /**
         * AJAX Admin actions
         */
        $('.visma-admin-action').on('click', function (event) {
            event.preventDefault();

            var loader = $(this).siblings('.spinner');

            if (!$(this).data('visma-admin-action'))
                return console.warn("No bulk action specified.");

            loader.css({visibility: "visible"});
            var action = $(this).data('visma-admin-action');

            $.ajax({
                url: window.ajaxurl,
                data: {
                    action: "visma_admin_action",
                    visma_action: $(this).data('visma-admin-action')
                },
                success: function (response) {

                    if (action == 'visma_get_auth_url') {
                        loader.css({visibility: "hidden"});
                        window.open(response.message);
                    }

                    else if (action == 'visma_update_settings') {
                        loader.css({visibility: "hidden"});
                        if ("undefined" !== typeof response.message)
                            return alert(response.message);
                    }

                },
                dataType: "json",
                timeout: 3600000,
            });
        });

        /**
         * Sync order
         */
        $('.syncOrderToVisma').on('click', function (event) {
            event.preventDefault();

            var orderId = $(this).data('order-id');
            var nonce = $(this).data('nonce');
            var loader = $(this).siblings('.visma-spinner');
            var status = $(this).siblings('.wetail-visma-status');

            loader.css({visibility: "visible"});
            status.hide();

            $('#visma-message').remove();

            $.ajax({
                url: window.ajaxurl,
                data: {
                    action: "visma_admin_action",
                    visma_action: "sync_order",
                    order_id: orderId
                },
                type: "POST",
                dataType: "json",
                success: function (response) {
                    if (!response.error)
                        status.removeClass('wetail-icon-cross').addClass('wetail-icon-check');

                    loader.css({visibility: "hidden"});
                    status.show();

                    $('#wpbody .wrap h1').after(Mustache.render(templates.notice, {
                        type: response.error ? "error" : "success",
                        message: response.message
                    }));

                    var vismaMessageElement = $('#visma-message');
                    vismaMessageElement.show();

                    $('.sendInvoiceToCustomer[data-order-id=' + orderId + ']').removeClass("wetail-hidden");

                    $('html, body').animate({scrollTop: vismaMessageElement.offset().top - 100});

                }
            });
        });

        $(document.body).on('click', '.notice-dismiss', function() {
            $(this).parents('.is-dismissible').first().hide();
        })


        /**
         * Sync product
         */
        $('.syncProductToVisma').on('click', function (event) {
            event.preventDefault();

            var productId = $(this).data('product-id');
            var nonce = $(this).data('nonce');
            var loader = $(this).siblings('.visma-spinner');
            var status = $(this).siblings('.wetail-visma-status');

            loader.css({visibility: "visible"});
            status.hide();

            $('#visma-message').remove();

            $.ajax({
                url: window.ajaxurl,
                data: {
                    action: "visma_admin_action",
                    visma_action: "sync_product",
                    product_id: productId
                },
                type: "POST",
                dataType: "json",
                success: function (response) {
                    if (!response.error)
                        status.removeClass('wetail-icon-cross').addClass('wetail-icon-check');

                    loader.css({visibility: "hidden"});
                    status.show();

                    $('#wpbody .wrap h1').after(Mustache.render(templates.notice, {
                        type: response.error ? "error" : "success",
                        message: response.message
                    }));

                    var vismaMessageElement = $('#visma-message');
                    vismaMessageElement.show();

                    $('html, body').animate({scrollTop: vismaMessageElement.offset().top - 100});
                }
            });

            return;
        });

        /**
         * Check sync method
         */
        $(document).ready(toggleSyncMethod);
        $('input[type="radio"][name="visma_sync_order_method"]').on('change', toggleSyncMethod);

        /**
         * Check organization number field setting
         */
        $(document).ready(toggleOrganizationNumberField);
        $('input[type="checkbox"][name="show_organization_number_field_in_billing_address_form"]').on('change', toggleOrganizationNumberField);

        /**
         * Toggle sync method
         */
        function toggleSyncMethod() {
            if ( $('input[type="radio"][name="visma_sync_order_method"]:checked').val() === "create_orders" ) {
                $('body').addClass('create-orders');
            } else {
                $('body').removeClass('create-orders');
            }
        }

        /**
         * Toggle organization number field setting
         */
        function toggleOrganizationNumberField() {
            if ( $('input[type="checkbox"][name="show_organization_number_field_in_billing_address_form"]').prop('checked') ) {
                $('body').addClass('show-organization-number-field');
            } else {
                $('body').removeClass('show-organization-number-field');
            }
        }

        /**
         * Check connection
         */
        if ($('.button.visma-check-connection').length) {

            $('.button.visma-check-connection').on('click', function (event) {
                event.preventDefault();

                var loader = $(this).siblings('.spinner');
                var message_alert = $(this).siblings('.alert');

                loader.css({visibility: "visible"});

                $.ajax({
                    url: window.ajaxurl,
                    data: {
                        action: "check_" + $(this).siblings('[type=text]').attr('name'),
                        key: $(this).siblings('[type=text]').val()
                    },
                    success: function (response) {
                        console.log(typeof(response.error))
                        loader.css({visibility: "hidden"});
                        var html = response.error ? '<div class="notice notice-error">' + response.message + '</div>' : '<div class="notice notice-success">' + response.message + '</div>'
                        message_alert.html(html);
                    },
                    dataType: "json"
                });
            });
        }

        /**
         * class-wf-ajax bulk actions
         */
        $('.visma-admin-action').on('click', function (event) {
            event.preventDefault();

            var loader = $(this).siblings('.spinner');

            if (!$(this).data('visma-admin-action'))
                return console.warn("No bulk action specified.");

            loader.css({visibility: "visible"});

            if ($(this).data('modal')) {
                modals[$(this).data('visma-admin-action')](loader);
                return;
            }

            $.ajax({
                url: window.ajaxurl,
                data: {
                    action: "visma_admin_action",
                    visma_action: $(this).data('visma-admin-action')
                },
                success: function (response) {
                    loader.css({visibility: "hidden"});
                    if ("undefined" !== typeof response.message && $(this).data('visma-admin-action') == 'visma_get_auth_url')
                        return alert(response.message);
                },
                dataType: "json"
            });
        });

        function generate_order_PDFs_preview(payload, parent) {
            parent.append($('<p style="position: relative; height: 95%; width: 100%;"></p>'));

            if (1 === payload.length) {
                parent.find('p').append($('<iframe  style="position: relative; height: 100%; width: 100%;" src="' + payload[0] + '"></iframe>'));
            } else if (payload.length) {
                let tabs = $('<nav class="nav-tab-wrapper woo-nav-tab-wrapper"></nav>');

                payload.forEach((v, i) => {
                    let tab = $('<a class="nav-tab pacsoft-order-pdf-tab"></a>');
                    if (i === 0) {
                        tab.addClass('nav-tab-active');
                    }
                    tab.html(i + 1);
                    tab.attr('data-iframe_id', 'wtv-order-pdf-' + i);

                    tab.on('click', (evt) => {
                        let that = $(evt.target);
                        $('a.nav-tab.wtv-order-pdf-tab').removeClass('nav-tab-active');
                        that.addClass('nav-tab-active');
                        $('iframe.wtv-order-pdf-iframe').hide();
                        $('#' + that.attr('data-iframe_id')).show();
                    });

                    tabs.append(tab);


                    let iframe = $('<iframe></iframe>');
                    iframe.addClass('wtv-order-pdf-iframe');
                    iframe.attr('src', v);
                    iframe.attr('id', 'wtv-order-pdf-' + i);
                    iframe.css('position', 'relative');
                    iframe.css('height', '95%');
                    iframe.css('width', '100%');
                    if (i !== 0) {
                        iframe.css('display', 'none');
                    }

                    parent.find('p').append(iframe);
                });
                // let merge_tab = $('<a class="nav-tab-wrapper woo-nav-tab-wrapper">Merge</a>');
                // // /wp-admin/edit.php?s=&post_status=all&post_type=shop_order&_wpnonce=36f2eb7676&_wp_http_referer=%2Fru%2Fwp-admin%2Fedit.php%3Fpost_type%3Dshop_order&action=unifaun_pdfs&m=0&_customer_user=&paged=1&post%5B%5D=10839&action2=-1
                // merge_tab.attr('href','');
                // tabs.append();


                parent.find('p').prepend(tabs);
            }
        }


        /**
         * @description "With invoice" feature on single order view to show pdf
         * @wrike https://www.wrike.com/open.htm?id=1257978584
         * @since 2.3.0
         */

        $('#printSingleOrderInvoicePdf').on('click', function (e) {
            e.preventDefault();

            const invoiceWrapper = $('.invoice-print-pdf');

            invoiceWrapper.addClass('loading');

            $.post(window.wp.ajax.settings.url, {
                action: 'wtv_get_order_invoice_pdf',
                pid: $('input#post_ID').val(),
            })
                .done(response => {
                    const width = $(window).width() * 0.8;
                    const height = $(window).height() * 0.8;
                    const wtvMessage = $('#wtv-message');

                    let popup = $('<div></div>');

                    if (wtvMessage.length > 0) {
                        wtvMessage.remove();
                    }

                    if (response.hasOwnProperty('url')) {
                        const width = $(window).width() * 0.8;
                        const height = $(window).height() * 0.8;

                        invoiceWrapper.removeClass('loading');

                        tb_show(visma_scripts.i18n.with_invoice_popup_title, response.url + '&TB_iframe=1&width=' + width + '&height=' + height);
                    } else {
                        popup.css('display', 'none');

                        $('#wtv-order-pdf-thickbox').remove();

                        popup.attr('id', 'wtv-order-pdf-thickbox');

                        generate_order_PDFs_preview(response, popup);

                        $('body').prepend(popup);

                        invoiceWrapper.removeClass('loading');

                        tb_show(visma_scripts.i18n.with_invoice_popup_title, '/?TB_inlinewidth=' + width + '&height=' + height + '&inlineId=wtv-order-pdf-thickbox');
                    }
                })
                .fail(response => {
                    if ($('#wtv-message').length === 0) {
                        $('#wpbody .wrap h1').after($(`<div id="wtv-message" class="notice notice-error is-dismissible"><p>${response.hasOwnProperty('message') ? response.message : visma_scripts.i18n.with_invoice_popup_failed_message}</p></div>`));
                    }

                    invoiceWrapper.removeClass('loading');

                    $('#wtv-message').show();

                    $('html, body').animate({scrollTop: $('#wtv-message').offset().top - 100});

                })
        })

        $( document.body )
            .on( 'init_tooltips', function() {
                var tiptip_args = {
                    'attribute': 'data-tip',
                    'fadeIn': 50,
                    'fadeOut': 50,
                    'delay': 200
                };

                $( '.tips, .help_tip, .woocommerce-help-tip' ).tipTip( tiptip_args );

                // Add tiptip to parent element for widefat tables
                $( '.parent-tips' ).each( function() {
                    $( this ).closest( 'a, th' ).attr( 'data-tip', $( this ).data( 'tip' ) ).tipTip( tiptip_args ).css( 'cursor', 'help' );
                });
            })
            .trigger( 'init_tooltips' );
    });
} )( jQuery );
