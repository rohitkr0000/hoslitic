<?php
// Fship (courier aggregator) client: pincode check, create shipment (waybill), tracking, label, cancel.
// Every request carries the account's API key in the "signature" header.

function fship_enabled(): bool
{
    return setting('fship_key') !== '';
}

function fship_base(): string
{
    return setting('fship_live', '1') === '1' ? 'https://capi.fship.in' : 'https://capi-qc.fship.in';
}

// Returns ['status', 'data', 'error'].
function fship_request(string $method, string $path, ?array $body = null): array
{
    $res = http_json($method, fship_base() . $path, $body, ['signature: ' . setting('fship_key'), 'Accept: application/json']);
    if ($res['status'] === 401) {
        $res['error'] = 'Fship rejected the API key. Check the key and the Live/Test switch in Settings.';
    }
    return $res;
}

// Fship answers with "status": true/false; the wording of the message field varies by endpoint.
function fship_ok(array $res): bool
{
    $s = $res['data']['status'] ?? null;
    return $res['status'] === 200 && ($s === true || $s === 1 || $s === 'true' || $s === 'Success');
}

function fship_error(array $res): string
{
    $d = is_array($res['data']) ? $res['data'] : [];
    $msg = $d['response'] ?? $d['error'] ?? $d['message'] ?? $d['title'] ?? '';
    if (is_array($msg)) {
        $msg = json_encode($msg);
    }
    if (!empty($d['errors']) && is_array($d['errors'])) { // field validation errors
        $msg = trim($msg . ' ' . json_encode($d['errors']));
    }
    return $msg !== '' ? (string) $msg : ($res['error'] ?: 'Unexpected response (HTTP ' . $res['status'] . ').');
}

// Serviceability of a PIN code from the pickup PIN code.
// Returns null if Fship could not be reached or gave no clear answer (callers should not block the order),
// otherwise ['serviceable' => bool, 'prepaid' => bool, 'cod' => bool].
function fship_pincode(string $pin): ?array
{
    $from = setting('fship_pickup_pincode');
    if ($from === '') {
        return null;
    }
    $res = fship_request('POST', '/api/pincodeserviceability', ['source_Pincode' => $from, 'destination_Pincode' => $pin]);
    $d = $res['data'];
    if ($res['status'] !== 200 || !is_array($d) || !isset($d['status'])) {
        return null;
    }
    if (!fship_ok($res)) {
        return ['serviceable' => false, 'prepaid' => false, 'cod' => false];
    }
    $yes = fn($v) => $v === true || $v === 1 || in_array(strtolower((string) $v), ['y', 'yes', 'true', '1'], true);
    $d = $d['destination'] ?? $d;
    $prepaid = !isset($d['prepaid']) || $yes($d['prepaid']);
    $cod = !isset($d['cod']) || $yes($d['cod']);
    return ['serviceable' => $prepaid || $cod, 'prepaid' => $prepaid, 'cod' => $cod];
}

// Creates a forward shipment. Returns ['ok' => true, 'awb' => ..] or ['ok' => false, 'error' => ..].
function fship_create_shipment(array $o, array $items): array
{
    $weight = 0;
    $products = [];
    foreach ($items as $it) {
        $weight += $it['weight_grams'] * $it['qty'];
        $products[] = [
            'productId' => (string) ($it['product_id'] ?? ''),
            'productName' => $it['name'],
            'unitPrice' => (float) $it['price'],
            'quantity' => (int) $it['qty'],
            'productCategory' => 'Health & Wellness',
            'hsnCode' => $it['hsn'],
            'sku' => $it['sku'],
            'taxRate' => (float) $it['gst_rate'],
            'productDiscount' => 0,
        ];
    }
    $cod = $o['payment_method'] === 'cod';
    $warehouse = (int) setting('fship_warehouse_id');
    $payload = [
        'customer_Name' => $o['name'],
        'customer_Mobile' => $o['phone'],
        'customer_Emailid' => $o['email'],
        'customer_Address' => $o['address'],
        'landMark' => '',
        'customer_Address_Type' => 'Home',
        'customer_PinCode' => $o['pincode'],
        'customer_City' => $o['city'],
        'orderId' => $o['order_no'],
        'invoice_Number' => $o['order_no'],
        'payment_Mode' => $cod ? 1 : 2, // 1 = COD, 2 = Prepaid
        'express_Type' => strcasecmp(setting('fship_mode', 'surface'), 'air') === 0 ? 'air' : 'surface',
        'is_Ndd' => 0,
        'order_Amount' => (float) $o['subtotal'] - (float) $o['discount'],
        'tax_Amount' => 0, // prices already include GST
        'extra_Charges' => (float) $o['shipping'] + (float) $o['cod_fee'],
        'total_Amount' => (float) $o['total'],
        'cod_Amount' => $cod ? (float) $o['total'] : 0,
        'shipment_Weight' => round(max(50, $weight) / 1000, 3), // kg
        'shipment_Length' => 12, // cm
        'shipment_Width' => 8,
        'shipment_Height' => 8,
        'volumetric_Weight' => round(12 * 8 * 8 / 5000, 3),
        'pick_Address_ID' => $warehouse,
        'return_Address_ID' => $warehouse,
        'products' => $products,
    ];
    $res = fship_request('POST', '/api/createforwardorder', $payload);
    $awb = (string) ($res['data']['waybill'] ?? $res['data']['awbNumber'] ?? '');
    if (fship_ok($res) && $awb !== '') {
        return ['ok' => true, 'awb' => $awb];
    }
    return ['ok' => false, 'error' => fship_error($res)];
}

// Returns ['status' => 'In Transit', 'scans' => [['date', 'activity', 'location'], ...]] (newest first) or null.
function fship_track(string $awb): ?array
{
    if ($awb === '') {
        return null;
    }
    $res = fship_request('POST', '/api/trackinghistory', ['waybill' => $awb]);
    if (!fship_ok($res)) {
        return null;
    }
    $d = $res['data'];
    $pick = function (array $row, array $keys): string {
        foreach ($keys as $k) {
            if (isset($row[$k]) && $row[$k] !== '') { return (string) $row[$k]; }
        }
        return '';
    };
    $scans = [];
    foreach ($d['trackingdata'] ?? $d['trackingData'] ?? [] as $sc) {
        if (!is_array($sc)) { continue; }
        $when = $pick($sc, ['DateandTime', 'dateandTime', 'dateTime', 'date']);
        $ts = $when !== '' ? strtotime($when) : false;
        $scans[] = [
            'date' => $ts ? date('d M Y, h:i A', $ts) : $when,
            'activity' => trim($pick($sc, ['Status', 'status']) . ($pick($sc, ['Remark', 'remark']) !== '' ? ' – ' . $pick($sc, ['Remark', 'remark']) : '')),
            'location' => $pick($sc, ['Location', 'location']),
            'ts' => $ts ?: 0,
        ];
    }
    usort($scans, fn($a, $b) => $b['ts'] <=> $a['ts']);
    $summary = is_array($d['summary'] ?? null) ? $d['summary'] : [];
    return ['status' => $pick($summary, ['status', 'Status']) ?: ($scans[0]['activity'] ?? ''), 'scans' => $scans];
}

// Returns the URL of the printable shipping label, or null.
function fship_label_url(string $awb): ?string
{
    $res = fship_request('POST', '/api/shippinglabel', ['awbNumber' => $awb]);
    $found = null;
    $data = is_array($res['data']) ? $res['data'] : [];
    // The label link's field name is not fixed, so take the first https link in the reply.
    array_walk_recursive($data, function ($v) use (&$found) {
        if ($found === null && is_string($v) && preg_match('#^https://\S+$#', $v)) { $found = $v; }
    });
    return $found;
}

function fship_cancel(string $awb): array
{
    $res = fship_request('POST', '/api/cancelorder', ['reason' => 'Cancelled by the seller', 'waybill' => $awb]);
    return fship_ok($res) ? ['ok' => true] : ['ok' => false, 'error' => fship_error($res)];
}
