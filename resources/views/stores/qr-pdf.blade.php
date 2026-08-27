<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Store QR Code</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            text-align: center;
            padding: 30px;
        }
        .box {
            border: 1px solid #dbe3ea;
            border-radius: 16px;
            padding: 30px;
        }
        img {
            width: 260px;
            height: 260px;
            margin: 20px auto;
            display: block;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .sub {
            font-size: 14px;
            color: #555;
        }
        .url {
            margin-top: 16px;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="title">{{ $store->name }}</div>
        <div class="sub">{{ $store->store_number }}</div>

        <img src="{{ $qrImagePath }}" alt="QR Code">

        <div class="sub">Scan to answer the survey</div>
        <div class="url">{{ $surveyUrl }}</div>
    </div>
</body>
</html>