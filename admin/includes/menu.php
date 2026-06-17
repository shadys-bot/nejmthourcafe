<?php
defined('NEJMT_ADMIN') or die('Direct access forbidden.');

function menu_read(): array {
    if (!file_exists(MENU_FILE)) return ['categories' => []];
    $json = file_get_contents(MENU_FILE);
    return json_decode($json, true) ?? ['categories' => []];
}

function menu_write(array $data): bool {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // Atomic write: write to temp file then rename
    $tmp = MENU_FILE . '.tmp.' . getmypid();
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, MENU_FILE);
}

function menu_find_category(array &$data, string $catId): ?array {
    foreach ($data['categories'] as &$cat) {
        if ($cat['id'] === $catId) return $cat;
    }
    return null;
}

function menu_next_id(array $data): int {
    $max = 0;
    foreach ($data['categories'] as $cat) {
        foreach ($cat['items'] as $item) {
            if (($item['id'] ?? 0) > $max) $max = $item['id'];
        }
    }
    return $max + 1;
}

function menu_save_item(array $post): array {
    $catId  = trim($post['category_id'] ?? '');
    $itemId = (int)($post['item_id'] ?? 0);
    $ar     = trim($post['ar'] ?? '');
    $en     = trim($post['en'] ?? '');
    $cal    = trim($post['cal'] ?? '');
    $price  = (float)($post['price'] ?? 0);
    $image  = trim($post['image'] ?? '');

    if (!$catId || !$ar || !$en || $price <= 0) {
        return ['ok' => false, 'msg' => 'بيانات ناقصة'];
    }

    $data = menu_read();
    $found = false;

    foreach ($data['categories'] as &$cat) {
        if ($cat['id'] !== $catId) continue;

        if ($itemId > 0) {
            // Update existing
            foreach ($cat['items'] as &$item) {
                if ($item['id'] === $itemId) {
                    $item['ar']    = $ar;
                    $item['en']    = $en;
                    $item['cal']   = $cal;
                    $item['price'] = $price;
                    if ($image) $item['image'] = $image;
                    $found = true;
                    break;
                }
            }
        } else {
            // New item
            $cat['items'][] = [
                'id'    => menu_next_id($data),
                'ar'    => $ar,
                'en'    => $en,
                'cal'   => $cal,
                'price' => $price,
                'image' => $image ?: 'images/menu/coffee.jpg',
            ];
            $found = true;
        }
        break;
    }

    if (!$found && $itemId === 0) {
        return ['ok' => false, 'msg' => 'الفئة غير موجودة'];
    }

    return menu_write($data) ? ['ok' => true] : ['ok' => false, 'msg' => 'فشل الحفظ'];
}

function menu_delete_item(string $catId, int $itemId): array {
    $data = menu_read();
    foreach ($data['categories'] as &$cat) {
        if ($cat['id'] !== $catId) continue;
        $before = count($cat['items']);
        $cat['items'] = array_values(array_filter($cat['items'], fn($i) => $i['id'] !== $itemId));
        if (count($cat['items']) === $before) return ['ok' => false, 'msg' => 'العنصر غير موجود'];
        break;
    }
    return menu_write($data) ? ['ok' => true] : ['ok' => false, 'msg' => 'فشل الحفظ'];
}
