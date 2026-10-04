<?php
// Delhivery B2C client: pincode check, create shipment (waybill), tracking, label, cancel.

function delhivery_enabled(): bool
{
    return setting('delhivery_token') !== '';
}

function delhivery_base(): string
{
    return setting('delhivery_live', '0') === '1' ? 'https://track.delhivery.com' : 'https://staging-express.delhivery.com';
}

function delhivery_track_link(string $awb): string
{
    return 'https://www.delhivery.com/track-v2/package/' . rawurlencode($awb);
}

// Low-level request. $body: array => JSON, string => sent as form data. Returns ['status', 'data', 'error'].
function delhivery_request(string $method, string $path, $body = null): array
{
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'data' => null, 'error' => 'PHP cURL extension is not enabled on this server.'];
    }
    $headers = ['Authorization: Token ' . setting('delhivery_token'), 'Accept: application/json'];
    $ch = curl_init(delhivery_base() . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 25]);
    if (is_array($body)) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif (is_string($body)) {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($status === 401) {
        $err = 'Delhivery rejected the API token. Check the token and the Live/Test switch in Settings.';
    }
    return ['status' => $status, 'data' => $raw === false ? null : json_decode($raw, true), 'error' => $err];
}

// Serviceability of a PIN code.
// Returns null if Delhivery could not be reached (callers should not block the order),
// otherwise ['serviceable' => bool, 'prepaid' => bool, 'cod' => bool].
function delhivery_pincode(string $pin): ?array
{
    $res = delhivery_request('GET', '/c/api/pin-codes/json/?filter_codes=' . rawurlencode($pin));
    if ($res['status'] !== 200 || !is_array($res['data']) || !isset($res['data']['delivery_codes'])) {
        return null;
    }
    $pc = $res['data']['delivery_codes'][0]['postal_code'] ?? null;
    if (!$pc || strcasecmp((string) ($pc['remarks'] ?? ''), 'Embargo') === 0) {
        return ['serviceable' => false, 'prepaid' => false, 'cod' => false];
    }
    $prepaid = ($pc['pre_paid'] ?? 'N') === 'Y';
    $cod = ($pc['cod'] ?? 'N') === 'Y';
    return ['serviceable' => $prepaid || $cod, 'prepaid' => $prepaid, 'cod' => $cod];
}

// Creates a forward shipment. Returns ['ok' => true, 'awb' => ..] or ['ok' => false, 'error' => ..].
function delhivery_create_shipment(array $o, array $items): array
{
    $weight = 0;
    $qty = 0;
    $desc = [];
    foreach ($items as $it) {
        $weight += $it['weight_grams'] * $it['qty'];
        $qty += $it['qty'];
        $desc[] = $it['name'] . ' x ' . $it['qty'];
    }
    $cod = $o['payment_method'] === 'cod';
    $payload = [
        'shipments' => [[
            'name' => $o['name'],
            'add' => $o['address'],
            'pin' => $o['pincode'],
            'city' => $o['city'],
            'state' => $o['state'],
            'country' => 'India',
            'phone' => $o['phone'],
            'order' => $o['order_no'],
            'payment_mode' => $cod ? 'COD' : 'Prepaid',
            'cod_amount' => $cod ? (float) $o['total'] : 0,
            'total_amount' => (float) $o['total'],
            'order_date' => date('Y-m-d H:i:s', strtotime($o['created_at'])),
            'products_desc' => mb_substr(implode(', ', $desc), 0, 200),
            'hsn_code' => $items[0]['hsn'] ?? '',
            'quantity' => (string) $qty,
            'weight' => (string) max(50, $weight), // grams
            'shipment_length' => '12',
            'shipment_width' => '8',
            'shipment_height' => '8',
            'shipping_mode' => setting('delhivery_mode', 'Surface'),
            'address_type' => 'home',
            'seller_name' => setting('site_name'),
            'seller_add' => setting('address'),
            'seller_gst_tin' => setting('gstin'),
            'seller_inv' => $o['order_no'],
        ]],
        'pickup_location' => ['name' => setting('delhivery_pickup')],
    ];
    $res = delhivery_request('POST', '/api/cmu/create.json', 'format=json&data=' . rawurlencode(json_encode($payload)));
    $pkg = $res['data']['packages'][0] ?? null;
    if ($pkg && !empty($pkg['waybill']) && strcasecmp((string) ($pkg['status'] ?? ''), 'Fail') !== 0) {
        return ['ok' => true, 'awb' => (string) $pkg['waybill']];
    }
    $msg = $pkg['remarks'] ?? ($res['data']['rmk'] ?? ($res['error'] ?: 'Unexpected response (HTTP ' . $res['status'] . ').'));
    return ['ok' => false, 'error' => is_array($msg) ? implode(' ', $msg) : (string) $msg];
}

// Returns ['status' => 'In Transit', 'scans' => [['date', 'activity', 'location'], ...]] (newest first) or null.
function delhivery_track(string $awb): ?array
{
    if ($awb === '') {
        return null;
    }
    $res = delhivery_request('GET', '/api/v1/packages/json/?waybill=' . rawurlencode($awb));
    $s = $res['data']['ShipmentData'][0]['Shipment'] ?? null;
    if (!$s) {
        return null;
    }
    $scans = [];
    foreach ($s['Scans'] ?? [] as $sc) {
        $d = $sc['ScanDetail'] ?? [];
        $scans[] = [
            'date' => isset($d['ScanDateTime']) ? date('d M Y, h:i A', strtotime($d['ScanDateTime'])) : '',
            'activity' => trim(($d['Scan'] ?? '') . (!empty($d['Instructions']) ? ' – ' . $d['Instructions'] : '')),
            'location' => $d['ScannedLocation'] ?? '',
        ];
    }
    return ['status' => (string) ($s['Status']['Status'] ?? ''), 'scans' => array_reverse($scans)];
}

// Returns the URL of the printable shipping label PDF, or null.
function delhivery_label_url(string $awb): ?string
{
    $res = delhivery_request('GET', '/api/p/packing_slip?wbns=' . rawurlencode($awb) . '&pdf=true');
    $link = $res['data']['packages'][0]['pdf_download_link'] ?? '';
    return preg_match('#^https://#', (string) $link) ? $link : null;
}

function delhivery_cancel(string $awb): array
{
    $res = delhivery_request('POST', '/api/p/edit', ['waybill' => $awb, 'cancellation' => 'true']);
    if ($res['status'] === 200 && !empty($res['data']['status'])) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => (string) ($res['data']['error'] ?? $res['data']['remark'] ?? ($res['error'] ?: 'Unexpected response (HTTP ' . $res['status'] . ').'))];
}
