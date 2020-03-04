# Vipps recurring payments

### Make charges:
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

### Get charges for an agreement:
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
