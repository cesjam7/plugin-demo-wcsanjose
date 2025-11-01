<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('rest_api_init', function() {
    register_rest_route('demo/v1', '/chatbot', [
        'methods' => 'POST',
        'callback' => 'demo_chatbot_respuesta'
    ]);
});

function demo_chatbot_respuesta(WP_REST_Request $request) {
    $pregunta = sanitize_text_field($request->get_param('pregunta'));
    $productos = wc_get_products(['limit' => 3]);
    $nombres = implode(", ", array_map(fn($p) => $p->get_name(), $productos));
    $prompt = "Eres un asistente de ventas. Pregunta del cliente: $pregunta. Usa esta información de productos: $nombres.";

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

    $response = json_decode(wp_remote_retrieve_body($response), true);
    return wp_send_json_success([
        'respuesta' => $response['choices'][0]['message']['content'] ?? 'No se pudo obtener respuesta.'
    ]);
}