<?php
session_start();
include 'db.php';
require_once('tcpdf/tcpdf.php');

if (!isset($_GET['id'])) {
    die("Error: Quotation ID is missing.");
}

$quote_id = $_GET['id'];

// 1. Fetch Detailed Quotation, Client, and Branch Data
$sql = "SELECT q.*, c.name as client_name, c.address as client_address, c.company_name,
               u.full_name as creator_name
        FROM quotations q 
        JOIN clients c ON q.client_id = c.id 
        LEFT JOIN users u ON q.created_by = u.id
        WHERE q.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$quote_id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) { die("Error: Quotation not found."); }

// 2. Fetch Line Items
$sqlItems = "SELECT * FROM quotation_items WHERE quote_id = ?";
$stmtItems = $pdo->prepare($sqlItems);
$stmtItems->execute([$quote_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// 3. Setup TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Al Hayiki System');
$pdf->SetTitle('Quotation ' . $quote['quote_no']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// 4. Build the HTML Content based on the Image Format
$html = '
<div style="font-family: helvetica; font-size: 10pt;">
    
    <table width="100%" cellpadding="2">
        <tr>
            <td width="60%">
                <strong>M/S:</strong> ' . ($quote['company_name'] ?? $quote['client_name']) . '<br>
                <strong>Address:</strong> ' . ($quote['client_address'] ?? 'Doha, Qatar') . '
            </td>
            <td width="40%" align="right">
                <strong>Date:</strong> ' . date('d-M-Y', strtotime($quote['created_at'])) . '<br>
                <strong>Quotation No.:</strong> ' . $quote['quote_no'] . '
            </td>
        </tr>
    </table>

    <p align="center" style="font-size: 11pt;"><strong>SUBJECT: QUOTATION FOR TRANSLATION SERVICES</strong></p>

    <p><strong>Dear Sir / Madam,</strong><br>
    With reference to your enquiry and the documents submitted, we are pleased to provide our quotation for translation services as detailed below:</p>

    <table border="1" cellpadding="4" cellspacing="0" width="100%">
        <tr style="background-color: #f2f2f2; font-weight: bold; text-align: center;">
            <th width="5%">SL</th>
            <th width="35%">Description / File Name</th>
            <th width="12%">Source Language</th>
            <th width="12%">Target Language</th>
            <th width="8%">Actual Pages</th>
            <th width="8%">Pages After Translation</th>
            <th width="10%">Rate per Page (QAR)</th>
            <th width="10%">Total Amount (QAR)</th>
        </tr>';

$sl = 1;
foreach ($items as $item) {
    // Splitting description if it contains " to " from our previous save logic
    $langs = explode(' to ', $item['description']);
    $src = $langs[0] ?? '-';
    $tgt = $langs[1] ?? '-';
    
    $html .= '
        <tr>
            <td align="center">' . $sl++ . '</td>
            <td>' . ($item['service_type'] === 'Translation' ? 'Document' : $item['description']) . '</td>
            <td align="center">' . ($item['service_type'] === 'Translation' ? $src : '-') . '</td>
            <td align="center">' . ($item['service_type'] === 'Translation' ? $tgt : '-') . '</td>
            <td align="center">' . $item['pages_s'] . '</td>
            <td align="center">' . $item['qty'] . '</td>
            <td align="center">' . number_format($item['rate'], 2) . '</td>
            <td align="right">' . number_format($item['total'], 2) . '</td>
        </tr>';
}

$html .= '
        <tr>
            <td colspan="7" align="right"><strong>Subtotal</strong></td>
            <td align="right">' . number_format($quote['sub_total'], 2) . '</td>
        </tr>
        <tr>
            <td colspan="7" align="right"><strong>Discount</strong></td>
            <td align="right">' . number_format($quote['discount'], 2) . '</td>
        </tr>
        <tr style="background-color: #eeeeee; font-weight: bold;">
            <td colspan="7" align="right"><strong>Grand Total:</strong></td>
            <td align="right">' . number_format($quote['grand_total'], 2) . '</td>
        </tr>
    </table>

    <h4 style="color: #4466aa; border-bottom: 1px double #000;">Delivery Schedule</h4>
    <ul>
        <li>Estimated delivery: ' . ($quote['deadline'] ? date('d-M-Y H:i', strtotime($quote['deadline'])) : '[Date & Time]') . '</li>
        <li>Delivery timeline is subject to receipt of confirmation and clear, complete documents.</li>
    </ul>

    <h4 style="color: #4466aa; border-bottom: 1px double #000;">Terms of Payment</h4>
    <ul>
        <li>Full payment is required at the time of delivery unless otherwise agreed, in accordance with the payment procedures of your esteemed company.</li>
        <li>Prices are quoted in Qatari Riyals (QAR).</li>
        <li>' . (!empty($quote['payment_terms']) ? $quote['payment_terms'] : '<span style="color:red;">[This part is editable via the Payment Terms field]</span>') . '</li>
    </ul>

    <h4 style="color: #4466aa; border-bottom: 1px double #000;">Mode of Delivery</h4>
    <ul>
        <li>Soft copy (PDF / Word) via email or WhatsApp.</li>
        <li>Hard copy (if required) can be collected from our office or delivered upon request.</li>
    </ul>

    <p><strong>Notes (If Any)</strong><br>' . nl2br($quote['additional_notes']) . '</p>

    <p>We trust that our quotation is competitive and look forward to your kind approval to proceed. Should you require any clarification or modification, please feel free to contact us.</p>
    
    <p><strong>Best Regards,</strong></p>
    
    <p><strong>AL HAYIKI TRANSLATION & SERVICES EST.</strong><br>
    Mobile: +974 3341 1153 / 33411153<br>
    Office: +974 4436 7755</p>
</div>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Quotation_' . $quote['quote_no'] . '.pdf', 'I');