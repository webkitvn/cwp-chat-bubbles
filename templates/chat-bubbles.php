<?php
defined('ABSPATH') or exit;

$chat_items = [
    'phone' => [
        'name' => 'Phone Call',
        'scheme' => 'tel:',
        'icon' => 'hotline.svg',
        'label' => __('Hotline', 'chatbubble'),
        'btn_bg' => '#52BA00'
    ],
    'zalo' => [
        'name' => 'Zalo',
        'scheme' => 'https://zalo.me/',
        'icon' => 'zalo.svg',
        'label' => __('Chat Zalo', 'chatbubble'),
        'btn_bg' => '#008BE6'
    ],
    'whatsapp' => [
        'name' => 'WhatsApp',
        'scheme' => 'https://wa.me/',
        'icon' => 'whatsapp.svg',
        'label' => __('Chat WhatsApp', 'chatbubble'),
        'btn_bg' => '#25D366'
    ],
    'wechat' => [
        'name' => 'WeChat',
        'scheme' => '',
        'icon' => 'wechat.svg',
        'label' => __('Chat WeChat', 'chatbubble'),
        'btn_bg' => '#2DC100'
    ],
    'telegram' => [
        'name' => 'Telegram',
        'scheme' => 'https://t.me/',
        'icon' => 'telegram.svg',
        'label' => __('Chat Telegram', 'chatbubble'),
        'btn_bg' => '#0088cc'
    ],
    'viber' => [
        'name' => 'Viber',
        'scheme' => '',
        'icon' => 'viber.svg',
        'label' => __('Chat Viber', 'chatbubble'),
        'btn_bg' => '#665CAC'
    ],
    'facebook' => [
        'name' => 'Facebook',
        'scheme' => 'https://m.me/',
        'icon' => 'messenger.svg',
        'label' => __('Chat Messenger', 'chatbubble'),
        'btn_bg' => '#0084FF'
    ],
    'line' => [
        'name' => 'Line',
        'scheme' => 'https://line.me/ti/p/',
        'icon' => 'line.svg',
        'label' => __('Chat Line', 'chatbubble'),
        'btn_bg' => '#00C300'
    ],
    'kakaotalk' => [
        'name' => 'KakaoTalk',
        'scheme' => '',
        'icon' => 'kakaotalk.svg',
        'label' => __('Chat KakaoTalk', 'chatbubble'),
        'btn_bg' => '#FFCD00'
    ]
];

function get_chat_contact($chat_items) {
    $contact = [];
    foreach ($chat_items as $key => $value) {
        $accountID = get_field('chatbb_' . $key, 'option');
        if ($accountID) {
            $contact[$key] = $accountID;
        }
    }
    return $contact;
}

$contact = get_chat_contact($chat_items);
if ($contact) :
?>
<div id="chat-bubbles">
    <a href="#" class="chat-icon chat-btn-toggle" data-twe-ripple-init data-twe-ripple-color="light">
        <img class="chat-icon-open" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/chat-bubble/support.svg"
            alt="Chat" width="50" height="41" loading="lazy">
        <img class="close" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/chat-bubble/cancel.svg" alt="Close"
            width="20" height="20" loading="lazy">
    </a>
    <div class="item-group">
        <?php foreach ($contact as $item => $accountID) : 
            $qrcode = get_field('chatbb_qrcode_' . $item, 'option');
            $qrcodeID = $qrcode['id'] ?? null;
            $scheme_url = $chat_items[$item]['scheme'] . $accountID;
            $icon_url = get_stylesheet_directory_uri() . '/assets/images/chat-bubble/socials/' . $chat_items[$item]['icon'];
        ?>
        <a href="<?php echo $chat_items[$item]['scheme'] ? $scheme_url : '#' ?>" class="chat-item chat-item-<?php echo esc_attr($item); ?> flex"
            title="<?php echo esc_attr($chat_items[$item]['label']); ?>" data-twe-ripple-init
            data-twe-ripple-color="light"
            <?php echo $qrcodeID ? 'data-bubble-modal="bubble-' . esc_attr($item) . '" target="_self"' : 'target="_blank"'; ?>>
            <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($item); ?>" width="24" height="24"
                loading="lazy">
            <span class="chat-item-text flex-1 ml-2"><?php echo esc_html($chat_items[$item]['label']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <?php foreach ($contact as $item => $accountID) : 
        
        $qrcode = get_field('chatbb_qrcode_' . $item, 'option');
        $qrcodeID = $qrcode['id'] ?? null;

        $icon_url = get_stylesheet_directory_uri() . '/assets/images/chat-bubble/socials/' . $chat_items[$item]['icon'];
        $scheme_url = $chat_items[$item]['scheme'] . $accountID;
        if ($qrcodeID) :
    ?>
    <div class="bubble-modal" id="bubble-<?php echo esc_attr($item); ?>" tabindex="-1" aria-hidden="true">
        <button class="bubble-modal-close">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <div class="modal-body">
            <div class="qrcode">
                <h3>
                    <?php echo esc_html($chat_items[$item]['label']); ?>
                </h3>
                <?php echo wp_get_attachment_image($qrcodeID, 'full', false, ['class' => 'w-full h-auto', 'loading' => 'lazy']); ?>
            </div>
            <?php 
                if($chat_items[$item]['scheme']) :
            ?>
            <a class="btn" href="<?php echo esc_url($chat_items[$item]['scheme'] . $accountID); ?>" data-twe-ripple-init
                data-twe-ripple-color="light"
                style="background-color: <?php echo esc_attr($chat_items[$item]['btn_bg']); ?>" target="_blank">
                <div class="icon">
                    <img src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($item); ?>" width="24"
                        height="24" loading="lazy">
                </div>
                <span>
                    <?php 
                        echo esc_html($accountID);
                    ?>
                </span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; endforeach; ?>
</div>
<?php endif; ?>