function payWithMonnify(
  amount,
  customerName,
  customerEmail,
  customerMobileNumber,
  apiKey,
  contractCode,
  testmode,
  urlVerify,
  currency
) {
  MonnifySDK.initialize({
    amount,
    currency,
    reference: "" + Math.floor(Math.random() * 1000000000 + 1),
    customerName,
    customerEmail,
    customerMobileNumber,
    apiKey, //Your api key
    contractCode, //Your contract code
    paymentDescription: "Payment of Product",
    isTestMode: testmode, //True or False for testmode
    paymentMethods: ["CARD"],
    onComplete: function (response) {
      //Implement what happens when transaction is completed.
      window.location.href =
        urlVerify + "&transactions_refrence=" + response.transactionReference;
      //Disable button
      jQuery("#wc-monnify-payment-gateway-button").prop("disabled", true);
      jQuery("#cancel-btn").remove();
      jQuery("#yes-add").html(
        `<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">Please keep the page open while we process your order</p>`
      );
    },
    onClose: function (data) {
      //Implement what should happen when the modal is closed here
      // console.log(data);
    }
  });
}

jQuery(function ($) {
  "use strict";

  /**
   * Object to handle Monnify admin functions.
   */
  var wc_monnify_payment = {
    /**
     * Initialize.
     */
    init: function () {
      // console.log(wc_monnify_params);
      // transactions_refrence;
      let run_monnify = () => {
        let amount = Number(wc_monnify_params.amount),
          customerName =
            wc_monnify_params.first_name + " " + wc_monnify_params.last_name,
          customerEmail = wc_monnify_params.email,
          customerMobileNumber = wc_monnify_params.phone;
        //Other Access
        var apiKey = wc_monnify_params.key,
          contractCode = wc_monnify_params.contractCode,
          testmode = wc_monnify_params.testmode == "1" ? true : false,
          urlVerify = wc_monnify_params.api_verify_url,
          currency = wc_monnify_params.currency;
        // console.log(data);
        payWithMonnify(
          amount,
          customerName,
          customerEmail,
          customerMobileNumber,
          apiKey,
          contractCode,
          testmode,
          urlVerify,
          currency
        );
      };
      $("#wc-monnify-payment-gateway-button").click(function (e) {
        e.preventDefault();
        // console.log(data);
        run_monnify();
      });

      //log
      // console.log(wc_monnify_params);
      run_monnify();
    }
  };

  wc_monnify_payment.init();
});
