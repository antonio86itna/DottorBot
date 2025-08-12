<?php

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

add_action('rest_api_init', function () {
    register_rest_route('dottorbot/v1', '/router', array(
        'methods'  => 'POST',
        'callback' => 'dottorbot_router_endpoint',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('dottorbot/v1', '/subscribe', array(
        'methods'  => 'POST',
        'callback' => 'dottorbot_push_subscribe',
        'permission_callback' => '__return_true',
    ));
});

function dottorbot_router_endpoint(\WP_REST_Request $request) {
    $params = $request->get_json_params();
    $question = $params['question'] ?? '';

    $classification = dottorbot_classify_query($question);
    if ($classification === 'emergency') {
        return array(
            'classification' => 'emergency',
            'message' => 'Se pensi sia un\u2019emergenza, contatta il 112.'
        );
    }

    $response = array(
        'classification' => $classification,
        'question' => $question,
    );

    if ($classification === 'needs_sources') {
        $response['research'] = dottorbot_perplexity_search($question);
    }

    return $response;
}

function dottorbot_classify_query(string $question): string {
    $api_key = getenv('OPENAI_API_KEY');
    if (!$api_key) {
        return 'general';
    }

    $payload = array(
        'model' => 'gpt-5-nano',
        'input' => $question,
        'instructions' => 'Classify the user query as one of: general, needs_sources, emergency.'
    );

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
    ));

    $raw = curl_exec($ch);
    curl_close($ch);
    if (!$raw) {
        return 'general';
    }
    $data = json_decode($raw, true);
    $text = strtolower(trim($data['output'][0]['content'][0]['text'] ?? ''));
    return in_array($text, array('general', 'needs_sources', 'emergency')) ? $text : 'general';
}

function dottorbot_perplexity_search(string $question): array {
    $api_key = getenv('PERPLEXITY_API_KEY');
    if (!$api_key) {
        return array();
    }

    $payload = array(
        'model' => 'sonar-small-online',
        'messages' => array(
            array('role' => 'system', 'content' => 'Return concise medical information with credible sources.'),
            array('role' => 'user', 'content' => $question),
        ),
    );

    $ch = curl_init('https://api.perplexity.ai/chat/completions');
    curl_setopt_array($ch, array(
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
    ));

    $raw = curl_exec($ch);
    curl_close($ch);
    if (!$raw) {
        return array();
    }
    $data = json_decode($raw, true);
    return array(
        'answer' => $data['choices'][0]['message']['content'] ?? '',
        'sources' => $data['sources'] ?? array(),
    );
}

function dottorbot_push_subscribe(\WP_REST_Request $request) {
    $subscription = $request->get_json_params();
    if (!isset($subscription['endpoint'])) {
        return new \WP_Error('invalid_subscription', 'Invalid subscription', array('status' => 400));
    }

    $subs = get_option('dottorbot_push_subscriptions', array());
    $subs[$subscription['endpoint']] = $subscription;
    update_option('dottorbot_push_subscriptions', $subs);

    return array('success' => true);
}

function dottorbot_get_vapid_keys(): array {
    $keys = get_option('dottorbot_vapid_keys');
    if (!$keys) {
        if (!class_exists('Minishlink\\WebPush\\VAPID')) {
            return array('publicKey' => '', 'privateKey' => '');
        }
        $keys = Minishlink\WebPush\VAPID::createVapidKeys();
        update_option('dottorbot_vapid_keys', $keys);
    }
    return $keys;
}

function dottorbot_get_vapid_public_key(): string {
    $keys = dottorbot_get_vapid_keys();
    return $keys['publicKey'] ?? '';
}

add_action('dottorbot_daily_reminder', 'dottorbot_push_daily_reminder');
function dottorbot_push_daily_reminder() {
    $subs = get_option('dottorbot_push_subscriptions', array());
    if (empty($subs)) {
        return;
    }
    $keys = dottorbot_get_vapid_keys();
    if (empty($keys['publicKey']) || empty($keys['privateKey']) || !class_exists('Minishlink\\WebPush\\WebPush')) {
        return;
    }

    $auth = array(
        'VAPID' => array(
            'subject' => get_bloginfo('url'),
            'publicKey' => $keys['publicKey'],
            'privateKey' => $keys['privateKey'],
        ),
    );

    $webPush = new Minishlink\WebPush\WebPush($auth);
    foreach ($subs as $sub) {
        $webPush->queueNotification(
            Minishlink\WebPush\Subscription::create($sub),
            json_encode(array(
                'title' => 'DottorBot',
                'body'  => 'Ricorda di compilare il tuo diario quotidiano.',
                'url'   => home_url('/diario')
            ))
        );
    }
    foreach ($webPush->flush() as $report) {
        // Ignore results for now
    }
}
