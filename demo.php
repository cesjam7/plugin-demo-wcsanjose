<?php
/**
 * Plugin Name: Demo IA WooCommerce
 * Description: Ejemplos de cómo usar OpenAI con WordPress y WooCommerce.
 * Version: 1.0
 * Author: Cesar Aquino
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ===========================================================
 * 🟦 1. Chatbot para ventas o soporte
 * ===========================================================
 */
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

    $prompt = "Eres un asistente de ventas. 
    Pregunta del cliente: $pregunta.
    Usa esta información de productos: $nombres.";

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


/**
 * ===========================================================
 * 🟨 2. Análisis de recursos para mejorar los procesos
 * ===========================================================
 */
add_action('rest_api_init', function() {
    register_rest_route('demo/v1', '/procesos', [
        'methods' => 'POST',
        'callback' => 'demo_procesos_analisis'
    ]);
});

function demo_procesos_analisis(WP_REST_Request $request) {
    // Productos con poco stock
    $productos = wc_get_products(['limit' => 5, 'stock_quantity' => 5, 'stock_status' => 'instock']);
    $pocos = array_map(fn($p) => $p->get_name(), $productos);

    // Reporte ejemplo
    $informe = sanitize_text_field($request->get_param('informe'));
    $prompt = "Según este informe: '$informe', sugiere qué nuevos productos o tallas deberíamos agregar.";

    // Llamada a OpenAI
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

    // Enviar correo
    // $to = get_option('admin_email');
    // $subject = 'Productos con poco stock';
    // $message = "Productos con poco stock: " . implode(', ', $pocos) . "\n\n";
    // $message .= "Sugerencia de IA: $sugerencia";
    // wp_mail($to, $subject, $message);

    return [
        'poco_stock' => $pocos,
        'sugerencia_ia' => $sugerencia
    ];
}


/**
 * ===========================================================
 * 🟥 3. Compartir mediante API los mejores resultados
 * ===========================================================
 */
add_action('rest_api_init', function() {
    register_rest_route('demo/v1', '/ventas', [
        'methods' => 'GET',
        'callback' => 'demo_api_ventas'
    ]);
});

function demo_api_ventas() {
    // Productos y ventas simuladas
    $productos = wc_get_products(['limit' => 5]);
    $info = array_map(function($p) {
        return [
            'nombre' => $p->get_name(),
            'ventas' => rand(0, 20)
        ];
    }, $productos);

    $prompt = "Analiza estos productos con sus ventas: " . json_encode($info) . 
              ". Indica cuáles conviene promocionar o vender mejor.";

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
?>