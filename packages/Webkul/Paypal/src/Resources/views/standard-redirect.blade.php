<?php
$formFields = [
    'cmd' => '_xclick',
    'business' => config('services.paypal.business_email'),
    'item_name' => 'Order #1234',
    'amount' => 100.0,
    'currency_code' => 'USD',
    'invoice' => 'ORDER-ID-1234',
    'notify_url' => route('paypal.ipn'),
    'return' => route('paypal.success'),
    'cancel_return' => route('paypal.cancel'),
];
?>

<body>
    You will be redirected to the PayPal website in a few seconds.

    <form action="https://www.paypal.com/cgi-bin/webscr" id="paypal_standard_checkout" method="POST">
        <input value="Click here if you are not redirected within 10 seconds..." type="submit">
        @foreach ($formFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}" />
        @endforeach
    </form>

    <script type="text/javascript">
        document.getElementById("paypal_standard_checkout").submit();
    </script>
</body>
