<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Donation Receipt</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 14px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .header, .footer {
            text-align: center;
            padding: 10px 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            margin: 30px 0;
        }
        .line {
            border-top: 2px solid #000;
            margin: 20px 0;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-grid td {
            padding: 8px 0;
            vertical-align: middle;
        }
        .label {
            font-weight: bold;
            width: 100px;
        }
        .value-box {
            border: 1px solid #000;
            padding: 8px 12px;
            display: inline-block;
            min-width: 150px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="line"></div>

        <div class="header">
            <h1>Niyaz - E- Hussain</h1>
        </div>

        <div class="line"></div>

        <div class="content">
            <table class="info-grid" style="width: 100%;">
                <tr>
                    <td class="label">Type</td>
                    <td><div class="value-box">{{ strtoupper($donation->donationType->name) }}</div></td>
                    <td class="label" style="text-align: right; padding-right: 10px;">Number :</td>
                    <td><div class="value-box">{{ $donation->id }}</div></td>
                </tr>
                <tr>
                    <td class="label">ITS No:</td>
                    <td colspan="3"><div class="value-box" style="min-width: 250px;">{{ $donation->donor_its_id }}</div></td>
                </tr>
            </table>

            <div class="line"></div>

            <table class="info-grid" style="width: 100%;">
                <tr>
                    <td class="label">Currency :</td>
                    <td><div class="value-box">{{ $donation->currency->code }}</div></td>
                    <td class="label" style="text-align: right; padding-right: 10px;">Amount :</td>
                    <td><div class="value-box">{{ number_format($donation->amount, 0) }}</div></td>
                </tr>
            </table>
        </div>

        <div class="line"></div>

        <div class="footer">
            <p>This is a system generated document and does not require any signature</p>
        </div>

        <div class="line"></div>
    </div>
</body>
</html>
