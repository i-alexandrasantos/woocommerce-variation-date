<?php
/*
Plugin Name: WooCommerce Variation Scheduler
Plugin URI: http://estudiodusa.pro
Description: Permite que variações de produtos no WooCommerce sejam ativadas e desativadas em datas específicas.
Version: 0.2.4
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

// Verificar as datas e ajustar a visibilidade e o preço
add_filter('woocommerce_available_variation', 'wvs_check_variation_dates', 10, 3);
function wvs_check_variation_dates($variation, $product, $variation_obj) {
    $start_date = get_post_meta($variation_obj->get_id(), 'variation_start_date', true);
    $end_date = get_post_meta($variation_obj->get_id(), 'variation_end_date', true);
    $current_date = date('Y-m-d');

    // Exibir variações apenas se ambas as datas forem definidas
    if (empty($start_date) || empty($end_date)) {
        return $variation; // Exibe a variação se uma das datas não estiver definida
    }

    // Certificar que a variação está dentro do intervalo de datas
    if ($current_date >= $start_date && $current_date <= $end_date) {
        // Adicionar mensagem de preço
        $locale = get_locale();
        $local_text = ($locale === 'es_ES') ? 'Precio' : 'Valor';
        $variation['variation_description'] .= '<p class="price-valor">' . $local_text . ' ' . $variation_obj->get_price_html() . '</p>';
        
        return $variation; // Exibe a variação dentro das datas
    }

    // Definir a variação como indisponível fora das datas
    $variation['is_in_stock'] = false;
    $localeIndisponivel = get_locale();
    $local_text_indisponivel = ($localeIndisponivel === 'es_ES') ? 'Disponible pronto.' : 'Disponível em breve.';
    $variation['availability_html'] = __($local_text_indisponivel, 'woocommerce');
    
    return $variation; // Exibe a variação com a mensagem de indisponível
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

// Enfileirar o arquivo de estilo
add_action('wp_enqueue_scripts', 'wvs_enqueue_styles');
function wvs_enqueue_styles() {
    wp_enqueue_style('wvs-style', plugins_url('style.css', __FILE__));
}
?>
