<?php
defined('NEJMT_ADMIN') or die('Direct access forbidden.');

define('OFFERS_FILE', dirname(MENU_FILE) . '/offers.json');

function offers_read(): array {
    if (!file_exists(OFFERS_FILE)) return ['offers' => []];
    $json = file_get_contents(OFFERS_FILE);
    return json_decode($json, true) ?? ['offers' => []];
}

function offers_write(array $data): bool {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $tmp  = OFFERS_FILE . '.tmp.' . getmypid();
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, OFFERS_FILE);
}

function offer_next_id(array $data): int {
    $max = 0;
    foreach ($data['offers'] as $o) {
        if (($o['id'] ?? 0) > $max) $max = $o['id'];
    }
    return $max + 1;
}

function offer_save(array $post): array {
    $id    = (int)($post['offer_id'] ?? 0);
    $title = trim($post['title'] ?? '');
    $desc  = trim($post['desc']  ?? '');
    $badge = trim($post['badge'] ?? '');
    $image = trim($post['image'] ?? '');

    if (!$title) return ['ok' => false, 'msg' => 'عنوان العرض مطلوب'];

    $data = offers_read();

    if ($id > 0) {
        $found = false;
        foreach ($data['offers'] as &$o) {
            if ($o['id'] === $id) {
                $o['title'] = $title;
                $o['desc']  = $desc;
                $o['badge'] = $badge;
                if ($image) $o['image'] = $image;
                $found = true;
                break;
            }
        }
        if (!$found) return ['ok' => false, 'msg' => 'العرض غير موجود'];
    } else {
        $data['offers'][] = [
            'id'    => offer_next_id($data),
            'title' => $title,
            'desc'  => $desc,
            'badge' => $badge,
            'image' => $image ?: '',
        ];
    }

    return offers_write($data) ? ['ok' => true] : ['ok' => false, 'msg' => 'فشل الحفظ'];
}

function offer_delete(int $id): array {
    $data   = offers_read();
    $before = count($data['offers']);
    $data['offers'] = array_values(array_filter($data['offers'], fn($o) => $o['id'] !== $id));
    if (count($data['offers']) === $before) return ['ok' => false, 'msg' => 'العرض غير موجود'];
    return offers_write($data) ? ['ok' => true] : ['ok' => false, 'msg' => 'فشل الحفظ'];
}
