<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('rest_api_init', function() {
    register_rest_route('demo/v1', '/ventas', [
        'methods' => 'GET',
        'callback' => 'demo_api_ventas'
    ]);
});

function demo_api_ventas() {
    $productos = wc_get_products(['limit' => 5]);
    $info = array_map(function($p) {
        return [
            'nombre' => $p->get_name(),
            'ventas' => rand(0, 20)
        ];
    }, $productos);

    $prompt = "Analiza estos productos con sus ventas: " . json_encode($info) . ". Indica cuáles conviene promocionar o vender mejor.";

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
    $analisis = $data['choices'][0]['message']['content'] ?? 'Sin análisis.';

    return [
        'productos' => $info,
        'analisis_ia' => $analisis
    ];
}