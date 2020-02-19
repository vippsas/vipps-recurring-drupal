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
