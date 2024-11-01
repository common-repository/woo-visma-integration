<?php

namespace src\visma;
use includes\WTV_Plugin;

if ( !defined('ABSPATH') ) die();

?>
<div class="visma_sync_products_shade"></div>
<div class="visma_sync_products_modal">
    <div class="modal_content">
        <section class="wc-backbone-modal-main" role="main">
            <header class="wc-backbone-modal-header">
                <h1><?php echo __( "Produktsynkronisering", WTV_Plugin::TEXTDOMAIN ); ?></h1>
                <button class="visma_modal_close modal-close modal-close-link dashicons dashicons-no-alt">
                    <span class="screen-reader-text">Close modal panel</span>
                </button>
            </header>
            <article >
                <div class="visma-product-sync-progress">
                    <h4><?php echo __("Progress", WTV_Plugin::TEXTDOMAIN ); ?></h4>
                    <div class="visma_sync_products_progress_bar">
                        <div class="outline">
                            <div class="fill"></div>
                        </div>
                        <div class="text">0%</div>
                    </div>
                </div>
                <p id="visma_product_sync_progress_description" class="description"><?php echo __("Klicka på Start för att påbörja synkroniseringen.", WTV_Plugin::TEXTDOMAIN ); ?></p>
            </article>
            <footer>
                <div class="inner">
                    <div>
                        <input class="button button-primary button-large" aria-label="<?php echo __("Start", WTV_Plugin::TEXTDOMAIN ); ?>"
                               id="visma_sync_products_btn" name="visma_sync_products_btn" type="button" value="<?php echo __("Starta", WTV_Plugin::TEXTDOMAIN ); ?>">
                    </div>
                </div>
            </footer>
        </section>
    </div>
</div>
