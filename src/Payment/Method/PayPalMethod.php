<?php

namespace Azuriom\Plugin\Shop\Payment\Method;

use Azuriom\Plugin\Shop\Cart\Cart;
use Azuriom\Plugin\Shop\Models\Payment;
use Azuriom\Plugin\Shop\Payment\PaymentMethod;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class PayPalMethod extends PaymentMethod
{
    /**
     * The payment method id name.
     *
     * @var string
     */
    protected $id = 'paypal';

    /**
     * The payment method display name.
     *
     * @var string
     */
    protected $name = 'PayPal';

    public function startPayment(Cart $cart, float $amount, string $currency)
    {
        $payment = $this->createPayment($cart, $amount, $currency);

        $attributes = [
            'cmd' => '_xclick',
            'charset' => 'utf-8',
            'business' => $this->gateway->data['email'],
            'amount' => $amount,
            'currency_code' => $currency,
            'item_name' => 'Purchase',
            'quantity' => 1,
            'no_shipping' => 1,
            'no_note' => 1,
            'return' => route('shop.cart.index'),
            'notify_url' => route('shop.payments.notification', $this->id),
            'custom' => $payment->id,
            'bn' => 'Azuriom',
        ];

        return redirect()->away('https://www.paypal.com/cgi-bin/webscr?'.http_build_query($attributes));
    }

    public function notification(Request $request, ?string $rawPaymentId)
    {
        $payment = Payment::findOrFail($request->input('custom'));

        $url = 'https://ipnpb.paypal.com/cgi-bin/webscr';
        $client = new Client(['timeout' => 60]);

        $data = ['cmd' => '_notify-validate'] + $request->all();

        $response = $client->post($url, [
            'form_params' => $data,
        ]);

        if ($response->getBody()->getContents() !== 'VERIFIED') {
            return response()->json('Invalid response');
        }

        $paymentId = $request->input('txn_id');
        $amount = $request->input('mc_gross');
        $currency = $request->input('mc_currency');
        $status = $request->input('payment_status');
        $receiverEmail = $request->input('receiver_email');

        if ($status !== 'Completed') {
            logger()->warning("[Shop] Invalid payment status for #{$paymentId}: {$status}");

            return $this->invalidPayment($payment, $paymentId, 'Invalid status');
        }

        if ($payment->currency !== $currency || $payment->price !== $amount) {
            logger()->warning("[Shop] Invalid payment amount/currency for #{$paymentId}: {$amount} {$currency}");

            return $this->invalidPayment($payment, $paymentId, 'Invalid amount/currency');
        }

        if (strcasecmp($this->gateway->data['email'], $receiverEmail) !== 0) {
            logger()->warning("[Shop] Invalid email for #{$paymentId}: {$receiverEmail}");

            return $this->invalidPayment($payment, $paymentId, 'Invalid email');
        }

        return $this->processPayment($payment, $paymentId);
    }

    public function success(Request $request)
    {
        return view('shop::payments.success');
    }

    public function view()
    {
        return 'shop::admin.gateways.methods.paypal';
    }

    public function rules()
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
