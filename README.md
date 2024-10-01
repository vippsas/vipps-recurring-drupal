<!-- START_METADATA
---
title: Vipps Recurring Payments
sidebar_position: 1
hide_table_of_contents: false
description: Provide Vipps recurring payments for Drupal.
pagination_next: null
pagination_prev: null
---
END_METADATA -->

# Vipps Recurring Payments

![Support and development by Frontkom ](./docs/images/frontkom.svg#gh-light-mode-only)![Support and development by Frontkom](./docs/images/frontkom_dark.svg#gh-dark-mode-only)

![Vipps](./docs/images/vipps.png) *Available for Vipps.*

![MobilePay](./docs/images/mp.png) *Availability for MobilePay has not yet been determined.*

*This plugin is built and maintained by [Frontkom](https://frontkom.com/)
and is hosted on [GitHub](https://github.com/vippsas/vipps-recurring-drupal).
For support, submit an issue at [drupal.org: *Vipps Recurring Payments*](https://www.drupal.org/project/vipps_recurring_payments).*

<!-- START_COMMENT -->
💥 Please use the plugin pages on [https://developer.vippsmobilepay.com](https://developer.vippsmobilepay.com/docs/plugins-ext/recurring-drupal/). 💥
<!-- END_COMMENT -->

## Features

### Basic authentication

In order to be able to send API requests to the module, you need to activate `Basic auth` module which is a part of the core and use Basic auth with all your API calls:

```json
{
  "Content-type": "application/json",
  "Authorization": "Basic ZnJvbnRrb206R29vZCBsdWNrIHRyeWluZw=="

}
```

### Test auth

* Endpoint: `/vipps-recurring-payments/test/auth`
* Method: `GET`
* Content-Type: `application/json`
* Response example:

```text
eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsIng1dCI6IkN0VHVoTUptRDVNN0RMZHpEMnYyeDNRS1NSWSIsImtpZCI6IkN0VHVoTUptRDVNN0RMZHpEMnYyeDNRS1NSWSJ9.eyJhdWQiOiIwMDAwMDAwMi0wMDAwLTAwMDAtYzAwMC0wMDAwMDAwMDAwMDAiLCJpc3MiOiJodHRwczovL3N0cy53aW5kb3dzLm5ldC9lNTExNjUyNi01MWRjLTRjMTQtYjA4Ni1hNWNiNDcxNmJjNGIvIiwiaWF0IjoxNTg4NzcwNDA1LCJuYmYiOjE1ODg3NzA0MDUsImV4cCI6MTU4ODc3NDMwNSwiYWlvIjoiNDJkZ1lHalhYemc3UE8wcXI4QzNCV2M2cjZmYkF3QT0iLCJhcHBpZCI6IjllZjEyNjA1LTNiYzYtNDk5Yi1iZjU0LTI4Mzc4ZTQyM2I3YSIsImFwcGlkYWNyIjoiMSIsImlkcCI6Imh0dHBzOi8vc3RzLndpbmRvd3MubmV0L2U1MTE2NTI2LTUxZGMtNGMxNC1iMDg2LWE1Y2I0NzE2YmM0Yi8iLCJ0ZW5hbnRfcmVnaW9uX3Njb3BlIjoiRVUiLCJ0aWQiOiJlNTExNjUyNi01MWRjLTRjMTQtYjA4Ni1hNWNiNDcxNmJjNGIiLCJ1dGkiOiJMSHRnWmFQOFhrLWtMTUV2Zlg1TkFBIiwidmVyIjoiMS4wIn0.PqdB33eWX90tRWYjNhCY6TeTMdC4DMbMYasfIBapdOUTgC8lhhWMImGlOK-dbEEsJ1gRrkGVFgqaqcdbiNPfUgJAcQPl2FZj1iMxwaKq6i_I7cHEfs8sHxpZ50lYjFjXtOkecZ2N62AdA6ltE_tWdtmaVRksiTfveaViSpjQLjnvZYUIb4WYomG7IzFcof_QV2ZbxNlh6fEYMT-D-2HEoEzq2i2eH2lpu-bsJAxLpUBdgE2dvt9fmbDRAb1uzNY_ouNDfml9LXo8GINB_KUaahouXmP1itW7mZW1FXImICkLVXWDlTi3NT31jv-L4NhbwPbZDsmv1ggCa-rcbLpK_g
```

### Get status of an agreement

* Endpoint: `/vipps-recurring-payments/agreement/get`
* Method: `POST`
* Content-Type: `application/json`
* Request content example:

```json
"agr_UVUFcx31"
```

* Response example:

```json
{
    "id": "agr_CUfJvfx",
    "start": "2020-02-06T10:10:07Z",
    "stop": null,
    "status": "ACTIVE",
    "productName": "Vipps product",
    "price": 87600,
    "productDescription": "Testing Vipps recurring payment",
    "interval": "MONTH",
    "intervalCount": 1,
    "currency": "NOK",
    "campaign": null
}
```

### Cancel an agreement

* Endpoint: `/vipps-recurring-payments/agreement/cancel`
* Method: `POST`
* Content-Type: `application/json`
* Request content example:

```json
[
  "agr_UVUFcx31",
  "agr_CUfJvfx"
]
```

* Response example:

```json
{
    "successes": [
        "agr_UVUFcx31"
    ],
    "errors": [
      "agr_CUfJvfx: Client error: `PATCH https://apitest.vipps.no/recurring/v2/agreements/agr_CUfJvfx` resulted in a `400 Bad Request` response:\n[{\"field\":\"status\",\"message\":\"Missing message for error: status.notActive\",\"code\":\"status.notActive\",\"contextId\":\"a47463 (truncated...)\n"
    ]
}
```

### Make charges

* Endpoint: `/vipps-recurring-payments/charge/make`
* Method: `POST`
* Content-Type: `application/json`
* Request content example:

```json
[{
 "agreement_id": "agr_UVUFcx3",
 "price": "250",
 "description": "Custom description"
},{
 "agreement_id": "agr_UVUFcx3",
 "price": "25000",
 "description": "Custom description"
},{
 "agreement_id": "agr_UVUFcx31",
 "price": "25000",
 "description": "Custom description"
}]
```

* Response example:

```json
{
    "successes": [
        "chr_hFZSqV6"
    ],
    "errors": [
        "agr_UVUFcx3: Client error: `POST amount.exceeds.expected.chargelimit ...",
        "agr_UVUFcx31: Client error: `POST This agreement does not exist"
    ]
}
```

### Get charges for an agreement

* Endpoint: `/vipps-recurring-payments/charge/get`
* Method: `POST`
* Content-Type: `application/json`
* Request content example:

```json
[
  {
    "agreement_id": "agr_UVUFcx3"
  },
  {
    "agreement_id": "agr_UVUFcx3"
  }
]
```

* Response example:

```json
{
    "successes": [
        "[{\"id\":\"chr_Ntsak6z\",\"status\":\"PARTIALLY_REFUNDED\",\"due\":\"2020-02-06T10:09:54Z\",\"amount\":87600,\"amountRefunded\":26640,\"transactionId\":\"5001630551\",\"description\":\"Testing Vipps recurring payment\",\"type\":\"INITIAL\"},{\"id\":\"chr_dxWPD5n\",\"status\":\"CANCELLED\",\"due\":\"2020-03-01T10:00:00Z\",\"amount\":123,\"amountRefunded\":0,\"transactionId\":null,\"description\":\"This is description\",\"type\":\"RECURRING\"},{\"id\":\"chr_xTsRNrg\",\"status\":\"DUE\",\"due\":\"2020-03-01T10:00:00Z\",\"amount\":123,\"amountRefunded\":0,\"transactionId\":\"5001659055\",\"description\":\"This is description\",\"type\":\"RECURRING\"}]"
    ],
    "errors": [
        "agr_UVUFcx3: Client error: `GET https://apitest.vipps.no/recurring/v2/agreements/agr_knvVufdj/charges` resulted in a `400 Bad Request` response:\n[{\"field\":\"agreementId\",\"message\":\"Missing message for error: invalid.agreementId\",\"code\":\"invalid.agreementId\",\"context (truncated...)\n"
    ]
}
```

### Cancel charges for an agreement

* Endpoint: `/vipps-recurring-payments/charge/cancel`
* Method: `POST`
* Content-Type: `application/json`
* Request content example:

```json
[
 {
  "agreement_id": "agr_CUfJvfx",
  "price": 123,
     "description": "This is description",
     "charge_id": "chr_ptqHYWG"
 }
]
```

* Response example:

```json
{
    "successes": [
        "chr_ptqHYWG"
    ],
    "errors": []
}
```

### Refund charges for an agreement

* Endpoint: `/vipps-recurring-payments/charge/refund`
* Method: `POST`
* Content-Type: `application/json`
* Request content example:

```json
[
 {
  "agreement_id": "agr_CUfJvfx",
  "price": 123,
     "description": "This is description",
     "charge_id": "chr_ptqHYWG"
 }
]
```

* Response example:

```json
{
    "successes": [
        "chr_ptqHYWG"
    ],
    "errors": []
}
```

## Support

For problems with your plugin,
submit an issue at [drupal.org: *Vipps Recurring Payments*](https://www.drupal.org/project/vipps_recurring_payments).
