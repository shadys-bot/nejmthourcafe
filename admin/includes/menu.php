<?php
defined('NEJMT_ADMIN') or die('Direct access forbidden.');

function menu_read(): array {
    if (!file_exists(MENU_FILE)) return ['categories' => []];
    $json = file_get_contents(MENU_FILE);
    return json_decode($json, true) ?? ['categories' => []];
}

function menu_write(array $data): bool {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $tmp  = MENU_FILE . '.tmp.' . getmypid();
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return rename($tmp, MENU_FILE);
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

/* ── Save item (add / edit / move between categories) ── */
function menu_save_item(array $post): array {
    $targetCatId = trim($post['category_id'] ?? '');
    $itemId      = (int)($post['item_id']     ?? 0);
    $ar          = trim($post['ar']           ?? '');
    $en          = trim($post['en']           ?? '');
    $cal         = trim($post['cal']          ?? '');
    $price       = (float)($post['price']     ?? 0);
    $image       = trim($post['image']        ?? '');

    if (!$targetCatId || !$ar || !$en || $price < 0) {
        return ['ok' => false, 'msg' => 'بيانات ناقصة'];
    }
    if ($cal === '') $cal = '-';

    $data = menu_read();

    // Verify target category exists
    $targetCat = null;
    foreach ($data['categories'] as $c) {
        if ($c['id'] === $targetCatId) { $targetCat = $c; break; }
    }
    if (!$targetCat) return ['ok' => false, 'msg' => 'الفئة غير موجودة'];

    if ($itemId > 0) {
        // ── Edit existing: find item in ANY category, then update/move ──
        $foundItem   = null;
        $sourceCatId = null;
        foreach ($data['categories'] as $c) {
            foreach ($c['items'] as $it) {
                if ((int)$it['id'] === $itemId) {
                    $foundItem   = $it;
                    $sourceCatId = $c['id'];
                    break 2;
                }
            }
        }
        if (!$foundItem) return ['ok' => false, 'msg' => 'العنصر غير موجود'];

        // Apply edits
        $foundItem['ar']    = $ar;
        $foundItem['en']    = $en;
        $foundItem['cal']   = $cal;
        $foundItem['price'] = $price;
        if ($image) $foundItem['image'] = $image;

        // Sync has_price with target category
        if (($targetCat['has_price'] ?? true) === false) {
            $foundItem['has_price'] = false;
        } else {
            unset($foundItem['has_price']);
        }

        if ($sourceCatId !== $targetCatId) {
            // Move: remove from source, append to target
            foreach ($data['categories'] as &$cat) {
                if ($cat['id'] === $sourceCatId) {
                    $cat['items'] = array_values(
                        array_filter($cat['items'], fn($i) => (int)$i['id'] !== $itemId)
                    );
                }
                if ($cat['id'] === $targetCatId) {
                    $cat['items'][] = $foundItem;
                }
            }
        } else {
            // Same category — update in place
            foreach ($data['categories'] as &$cat) {
                if ($cat['id'] !== $targetCatId) continue;
                foreach ($cat['items'] as &$item) {
                    if ((int)$item['id'] === $itemId) {
                        $item = $foundItem;
                        break;
                    }
                }
                break;
            }
        }
    } else {
        // ── New item ──
        foreach ($data['categories'] as &$cat) {
            if ($cat['id'] !== $targetCatId) continue;
            $newItem = [
                'id'    => menu_next_id($data),
                'ar'    => $ar,
                'en'    => $en,
                'cal'   => $cal,
                'price' => $price,
                'image' => $image ?: 'images/menu/coffee.jpg',
            ];
            if (($cat['has_price'] ?? true) === false) {
                $newItem['has_price'] = false;
            }
            $cat['items'][] = $newItem;
            break;
        }
    }

    return menu_write($data) ? ['ok' => true] : ['ok' => false, 'msg' => 'فشل الحفظ'];
}

/* ── Delete item ── */
function menu_delete_item(string $catId, int $itemId): array {
    $data = menu_read();
    foreach ($data['categories'] as &$cat) {
        if ($cat['id'] !== $catId) continue;
        $before = count($cat['items']);
        $cat['items'] = array_values(
            array_filter($cat['items'], fn($i) => (int)$i['id'] !== $itemId)
        );
        if (count($cat['items']) === $before) return ['ok' => false, 'msg' => 'العنصر غير موجود'];
        break;
    }
    return menu_write($data) ? ['ok' => true] : ['ok' => false, 'msg' => 'فشل الحفظ'];
}

/* ── Category management ── */
function menu_save_category(array $post): array {
    $action   = $post['action']   ?? '';
    $id       = trim(strtolower($post['cat_id']   ?? ''));
    $icon     = trim($post['icon']     ?? '☕');
    $labelAr  = trim($post['label_ar'] ?? '');
    $labelEn  = trim($post['label_en'] ?? '');
    $hasPrice = ($post['has_price'] ?? '1') !== '0';
    $isHidden = ($post['is_hidden'] ?? '0') === '1';

    if (!in_array($action, ['add', 'edit', 'delete', 'reorder'], true)) {
        return ['ok' => false, 'msg' => 'إجراء غير معروف'];
    }
    if (!$id) return ['ok' => false, 'msg' => 'معرف الفئة مطلوب'];
    if (!preg_match('/^[a-z0-9\-]+$/', $id)) {
        return ['ok' => false, 'msg' => 'المعرف: أحرف إنجليزية صغيرة وأرقام وشرطات فقط'];
    }
    if (!in_array($action, ['delete', 'reorder'], true) && !$labelAr) {
        return ['ok' => false, 'msg' => 'الاسم بالعربية مطلوب'];
    }

    $data = menu_read();

    if ($action === 'reorder') {
        $direction = $post['direction'] ?? '';
        if (!in_array($direction, ['up', 'down'], true)) {
            return ['ok' => false, 'msg' => 'اتجاه الترتيب غير صحيح'];
        }

        $idx = null;
        foreach ($data['categories'] as $i => $cat) {
            if ($cat['id'] === $id) { $idx = $i; break; }
        }
        if ($idx === null) return ['ok' => false, 'msg' => 'الفئة غير موجودة'];

        $newIdx = $direction === 'up' ? $idx - 1 : $idx + 1;
        if ($newIdx < 0 || $newIdx >= count($data['categories'])) {
            return ['ok' => true];
        }

        $tmp = $data['categories'][$idx];
        $data['categories'][$idx] = $data['categories'][$newIdx];
        $data['categories'][$newIdx] = $tmp;

    } elseif ($action === 'add') {
        foreach ($data['categories'] as $c) {
            if ($c['id'] === $id) return ['ok' => false, 'msg' => 'هذا المعرف موجود مسبقاً'];
        }
        $newCat = [
            'id'       => $id,
            'icon'     => $icon ?: '☕',
            'label_ar' => $labelAr,
            'label_en' => $labelEn,
            'items'    => [],
        ];
        if (!$hasPrice) $newCat['has_price'] = false;
        if ($isHidden) $newCat['hidden'] = true;
        $data['categories'][] = $newCat;

    } elseif ($action === 'edit') {
        $found = false;
        foreach ($data['categories'] as &$cat) {
            if ($cat['id'] === $id) {
                $cat['icon']     = $icon ?: $cat['icon'];
                $cat['label_ar'] = $labelAr;
                $cat['label_en'] = $labelEn;
                if ($hasPrice) {
                    unset($cat['has_price']);
                    // restore price visibility on existing items
                    foreach ($cat['items'] as &$it) { unset($it['has_price']); }
                } else {
                    $cat['has_price'] = false;
                    // propagate no-price flag to existing items
                    foreach ($cat['items'] as &$it) { $it['has_price'] = false; }
                }
                if ($isHidden) {
                    $cat['hidden'] = true;
                } else {
                    unset($cat['hidden']);
                }
                $found = true;
                break;
            }
        }
        unset($cat, $it);
        if (!$found) return ['ok' => false, 'msg' => 'الفئة غير موجودة'];

    } elseif ($action === 'delete') {
        $idx = null;
        foreach ($data['categories'] as $i => $cat) {
            if ($cat['id'] === $id) { $idx = $i; break; }
        }
        if ($idx === null) return ['ok' => false, 'msg' => 'الفئة غير موجودة'];
        if (count($data['categories'][$idx]['items']) > 0) {
            return ['ok' => false, 'msg' => 'لا يمكن حذف فئة تحتوي على عناصر. انقل العناصر أولاً.'];
        }
        array_splice($data['categories'], $idx, 1);
    }

    return menu_write($data) ? ['ok' => true] : ['ok' => false, 'msg' => 'فشل الحفظ'];
}
