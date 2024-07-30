<?php
/*
Plugin Name: WooCommerce Variation Scheduler
Plugin URI: http://estudiodusa.pro
Description: Permite que variações de produtos no WooCommerce sejam ativadas e desativadas em datas específicas.
Version: 0.1.7
Author: Alexandra Santos
Author URI: http://estudiodusa.pro
License: GPL2
*/

// Certifique-se de que WooCommerce está ativo
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

// Adicionar campos personalizados ao produto
add_action('woocommerce_variation_options_pricing', 'wvs_add_custom_variation_fields', 10, 3);
function wvs_add_custom_variation_fields($loop, $variation_data, $variation) {
    woocommerce_wp_text_input(array(
        'id' => 'variation_start_date_' . $variation->ID,
        'label' => __('Start Date', 'woocommerce'),
        'placeholder' => 'YYYY-MM-DD',
        'desc_tip' => 'true',
        'description' => __('Date when the variation should become active.', 'woocommerce'),
        'type' => 'date',
        'value' => get_post_meta($variation->ID, 'variation_start_date', true)
    ));
    woocommerce_wp_text_input(array(
        'id' => 'variation_end_date_' . $variation->ID,
        'label' => __('End Date', 'woocommerce'),
        'placeholder' => 'YYYY-MM-DD',
        'desc_tip' => 'true',
        'description' => __('Date when the variation should expire.', 'woocommerce'),
        'type' => 'date',
        'value' => get_post_meta($variation->ID, 'variation_end_date', true)
    ));
}

// Salvar os campos personalizados
add_action('woocommerce_save_product_variation', 'wvs_save_custom_variation_fields', 10, 2);
function wvs_save_custom_variation_fields($variation_id, $i) {
    $start_date = isset($_POST['variation_start_date_' . $variation_id]) ? sanitize_text_field($_POST['variation_start_date_' . $variation_id]) : '';
    $end_date = isset($_POST['variation_end_date_' . $variation_id]) ? sanitize_text_field($_POST['variation_end_date_' . $variation_id]) : '';

    update_post_meta($variation_id, 'variation_start_date', $start_date);
    update_post_meta($variation_id, 'variation_end_date', $end_date);
}

// Ajustar a visibilidade e o preço das variações com base nas datas
add_filter('woocommerce_available_variation', 'wvs_check_variation_dates', 10, 3);
function wvs_check_variation_dates($variation, $product, $variation_obj) {
    $start_date = get_post_meta($variation_obj->get_id(), 'variation_start_date', true);
    $end_date = get_post_meta($variation_obj->get_id(), 'variation_end_date', true);
    $price_html = $variation_obj->get_price_html();

    // Adicionar apenas o preço da variação se a variação tiver datas definidas
    if (!empty($start_date) && !empty($end_date)) {
        $variation['variation_description'] .= '<p class="price">' . $price_html . '</p>';
    }

    return $variation; // Exibe todas as variações com as datas definidas
}

// Atualizar o cache de preços das variações
add_filter('woocommerce_get_variation_prices_hash', 'wvs_custom_variation_prices_hash', 10, 3);
function wvs_custom_variation_prices_hash($price_hash, $product, $for_display) {
    $price_hash[] = 'variation_dates';
    return $price_hash;
}

// Forçar o recálculo de preços de variações ao carregar a página do produto
add_filter('woocommerce_variation_prices', 'wvs_force_recalculate_variation_prices', 10, 2);
function wvs_force_recalculate_variation_prices($prices, $product) {
    $product->get_children(); // Força o recálculo
    return $prices;
}
?>
