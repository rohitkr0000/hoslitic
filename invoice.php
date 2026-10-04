<?php
require __DIR__ . '/includes/init.php';

$orderNo = (string) ($_GET['order'] ?? '');
$o = current_admin() ? row('SELECT * FROM orders WHERE order_no = ?', [$orderNo]) : viewable_order($orderNo);
if (!$o) {
    http_response_code(404);
    exit('Invoice not found.');
}
$lines = order_tax_lines($o, order_items((int) $o['id']));
$intra = strcasecmp($o['state'], setting('state', 'Uttar Pradesh')) === 0; // same state => CGST + SGST
$taxable = array_sum(array_column($lines, 'taxable'));
$tax = array_sum(array_column($lines, 'tax'));
$n = fn($v) => number_format((float) $v, 2);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invoice <?= e($o['order_no']) ?></title>
<style>
body{font-family:Arial,Helvetica,sans-serif;color:#17211b;margin:0;background:#eee;font-size:13px;line-height:1.5}
.sheet{max-width:800px;margin:20px auto;background:#fff;padding:36px}
.top{display:flex;justify-content:space-between;gap:20px;border-bottom:3px solid #14432a;padding-bottom:16px}
h1{font-size:22px;margin:0;color:#14432a;letter-spacing:.08em}
h2{font-size:17px;margin:0 0 4px;color:#14432a}
.cols{display:flex;gap:30px;margin:18px 0}
.cols>div{flex:1}
h4{margin:0 0 4px;font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#667}
table{width:100%;border-collapse:collapse;margin-top:8px}
th{background:#14432a;color:#fff;text-align:left;padding:8px;font-size:11px;text-transform:uppercase}
td{padding:8px;border-bottom:1px solid #ddd}
.r{text-align:right}
.totals{width:320px;margin-left:auto;margin-top:10px}
.totals td{border:0;padding:4px 8px}
.totals .g td{border-top:2px solid #14432a;font-weight:bold;font-size:15px}
.foot{margin-top:28px;color:#667;font-size:11px;border-top:1px solid #ddd;padding-top:10px}
.bar{max-width:800px;margin:16px auto 0;text-align:right}
.bar button{background:#14432a;color:#fff;border:0;border-radius:20px;padding:10px 22px;font-size:14px;cursor:pointer}
@media print{body{background:#fff}.sheet{margin:0;padding:0}.bar{display:none}}
</style>
</head>
<body>
<div class="bar"><button onclick="window.print()">Print / Save as PDF</button></div>
<div class="sheet">
  <div class="top">
    <div>
      <h2><?= e(setting('site_name')) ?></h2>
      <?= e(setting('address')) ?><br>
      <?php if (setting('legal_name') !== ''): ?>Proprietor: <?= e(setting('legal_name')) ?><br><?php endif; ?>
      <strong>GSTIN: <?= e(setting('gstin')) ?></strong>
      <?php if (setting('phone') !== ''): ?><br>Phone: <?= e(setting('phone')) ?><?php endif; ?>
      <?php if (setting('email') !== ''): ?><br>Email: <?= e(setting('email')) ?><?php endif; ?>
    </div>
    <div class="r">
      <h1>TAX INVOICE</h1>
      Invoice no.: <strong><?= e($o['order_no']) ?></strong><br>
      Date: <?= e(date('d M Y', strtotime($o['created_at']))) ?><br>
      Payment: <?= $o['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online' ?> (<?= e(ucfirst($o['payment_status'])) ?>)
    </div>
  </div>
  <div class="cols">
    <div><h4>Bill to / Ship to</h4><strong><?= e($o['name']) ?></strong><br><?= e($o['address']) ?><br><?= e($o['city'] . ', ' . $o['state'] . ' – ' . $o['pincode']) ?><br>Phone: <?= e($o['phone']) ?></div>
    <div><h4>Place of supply</h4><?= e($o['state']) ?><br><span style="color:#667"><?= $intra ? 'Intra-state supply (CGST + SGST)' : 'Inter-state supply (IGST)' ?></span></div>
  </div>
  <table>
    <thead><tr><th>#</th><th>Item</th><th>HSN</th><th class="r">Qty</th><th class="r">Taxable value</th><th class="r">GST %</th><th class="r">GST</th><th class="r">Total</th></tr></thead>
    <tbody>
      <?php foreach ($lines as $i => $l): ?>
        <tr><td><?= $i + 1 ?></td><td><?= e($l['name']) ?></td><td><?= e($l['hsn']) ?></td><td class="r"><?= (int) $l['qty'] ?></td>
          <td class="r"><?= $n($l['taxable']) ?></td><td class="r"><?= (float) $l['gst_rate'] ?>%</td><td class="r"><?= $n($l['tax']) ?></td><td class="r"><?= $n($l['gross']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <table class="totals">
    <tr><td>Taxable value</td><td class="r">₹<?= $n($taxable) ?></td></tr>
    <?php if ($intra): ?>
      <tr><td>CGST</td><td class="r">₹<?= $n($tax / 2) ?></td></tr>
      <tr><td>SGST</td><td class="r">₹<?= $n($tax - round($tax / 2, 2)) ?></td></tr>
    <?php else: ?>
      <tr><td>IGST</td><td class="r">₹<?= $n($tax) ?></td></tr>
    <?php endif; ?>
    <?php if ($o['shipping'] > 0): ?><tr><td>Shipping</td><td class="r">₹<?= $n($o['shipping']) ?></td></tr><?php endif; ?>
    <?php if ($o['cod_fee'] > 0): ?><tr><td>COD handling fee</td><td class="r">₹<?= $n($o['cod_fee']) ?></td></tr><?php endif; ?>
    <tr class="g"><td>Grand total</td><td class="r">₹<?= $n($o['total']) ?></td></tr>
  </table>
  <?php if ($o['discount'] > 0): ?><p style="color:#667">Coupon <?= e($o['coupon_code']) ?> discount of ₹<?= $n($o['discount']) ?> has been applied to the item values above.</p><?php endif; ?>
  <div class="foot">This is a computer-generated invoice and does not require a signature. Prices are inclusive of GST.</div>
</div>
</body>
</html>
