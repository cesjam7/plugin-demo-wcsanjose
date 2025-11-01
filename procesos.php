<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('rest_api_init', function() {
    register_rest_route('demo/v1', '/procesos', [
        'methods' => 'POST',
        'callback' => 'demo_procesos_analisis'
    ]);
});

function demo_procesos_analisis(WP_REST_Request $request) {
    $productos = wc_get_products(['limit' => 5, 'stock_quantity' => 5, 'stock_status' => 'instock']);
    $pocos = array_map(fn($p) => $p->get_name(), $productos);
    $informe = sanitize_text_field($request->get_param('informe'));
    $prompt = "Según este informe: '$informe', sugiere qué nuevos productos o tallas deberíamos agregar.";

    $body = [
        'model' => 'gpt-4o-mini',
        'messages' => [['role' => 'user', 'content' => $prompt]]
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer '.API_KEY,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($body)
    ]);

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $sugerencia = $data['choices'][0]['message']['content'] ?? 'Sin sugerencias.';

    return [
        'poco_stock' => $pocos,
        'sugerencia_ia' => $sugerencia
    ];
}