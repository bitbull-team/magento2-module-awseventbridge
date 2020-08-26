## Magento 2 module AWS EventBridge integration

Send Magento events to [Amazon EventBridge](https://aws.amazon.com/eventbridge/) service to be able to integrate Magento with many different AWS serverless services. 

![Packagist](https://img.shields.io/packagist/v/bitbull/magento2-module-awseventbridge)

![Magento](https://img.shields.io/badge/magento-~2.3.4-orange)

![PHP](https://img.shields.io/packagist/php-v/bitbull/magento2-module-awseventbridge)

## Installation Instructions

Install this module using composer: 

```bash
composer require bitbull/magento2-module-awseventbridge
```

## IAM permissions required

Create a new [IAM Policy](https://docs.aws.amazon.com/en_us/AWSEC2/latest/UserGuide/iam-policies-for-amazon-ec2.html) with these content:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Action": [
        "events:PutEvents"
      ],
      "Effect": "Allow",
      "Resource": "*",
      "Condition": {
        "StringEquals": {
          "events:source": "example.com"
        }
      }
    }
  ]
}
```
change `events:source` according to your module configuration.

read more about IAM permissions at: 
- https://docs.aws.amazon.com/en_us/AmazonCloudWatch/latest/events/auth-and-access-control-cwe.html
- https://docs.aws.amazon.com/en_us/AmazonCloudWatch/latest/events/policy-keys-cwe.html

If you are using EC2 instance add this policy to attached [IAM Role](https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles.html).

read more about using IAM Role with EC2 at:
- https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/iam-roles-for-amazon-ec2.html

If your are running Magento locally, on-premises or with an other Cloud Provider different from AWS follow these steps:

1. Create a new [AWS IAM User](https://docs.aws.amazon.com/en_us/IAM/latest/UserGuide/id_users.html)
2. Attach the created policy
3. Generate access keys for the user

read more about creating IAM users at:
- https://docs.aws.amazon.com/IAM/latest/UserGuide/id_users_create.html
- https://docs.aws.amazon.com/IAM/latest/UserGuide/id_users_change-permissions.html
- https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_access-keys.html

## Setup

Go to "Stores" > "Configuration" > "Services" > "AWS EventBridge", then start configuring this module.

### Credentials

You can set your access keys for IAM Users:  

![Credentials keys](./doc/imgs/config-credentials-keys.png?raw=true)

Retrieving from environment variables AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY:

![Credentials env](./doc/imgs/config-credentials-env.png?raw=true)

Using EC2 Instance Role:

![Credentials ec2](./doc/imgs/config-credentials-ec2.png?raw=true)

### Options

Edit module options:

![Options](./doc/imgs/config-options.png?raw=true)

- Set the correct region where you want to receive events, for example "eu-west-1".
- Set event source name with a value that can be filtered (`events:source`) when you connect to these events.
- Set event bus name, leave empty to use your account default.
- Enable tracking to add `tracking` property to data object.
- Enable debug mode if you want a more verbose logging in `var/log/aws-eventbridge.log` log file.
- Enable CloudWatch Events fallback to use this service instead of EventBridge (for backward compatibility).
- Enable dry run mode to activate the module actions and integrations without actually sending events data.
- Enable Queue mode to send events asynchronously using Magento queue instead of real-time (only available on Magento Enterprise edition).

If you enable the "Queue mode" you also need to start the queue consumer in order to process events queue:
```bash
bin/magento queue:consumers:start aws.eventbridge.events.send --single-thread --max-messages=5
``` 
or enable cron consumer runner into your env.php
```php
    'cron_consumers_runner' => [
        'max_messages' => 5,
        'cron_run' => true,
        'consumers' => [
            'aws.eventbridge.events.send'
        ]
    ]
```

![Events](./doc/imgs/config-cart-events.png?raw=true)

These sections contain a list of supported events that can be activated and used to trigger Lambda functions, send event to an SNS topic, add message to SQS Queue, execute a StepFunction and so on..

## Events 

This module inject observers to listen to Magento 2 events, elaborate the payload and then send the event to AWS services.

### Create an AWS EventBridge Rule

In order to connect to an EventBridge event and trigger a target you need to create an EventBridge Rule that match one or more events.

The event name is used in `detail-type` section of event data, so if you want to match "CartProductAdded" event you need to create a rule like this:
```json
{
  "source": [
    "example.com"
  ],
  "detail-type": [
    "CartProductAdded"
  ]
}
```
remember to also match `source` name to avoid collision with different Magento environment.

You can also match multiple event names, for example if you want to react to all cart events:
```json
{
  "source": [
    "example.com"
  ],
  "detail-type": [
    "CartProductAdded",
    "CartProductUpdated",
    "CartProductRemoved"
  ]
}
```

Is it possible to use a more specific matching rule in order to match, for example, all cart events related to a specific product sku:
```json
{
  "source": [
    "example.com"
  ],
  "detail-type": [
    "CartProductAdded",
    "CartProductUpdated",
    "CartProductRemoved"
  ],
  "detail": {
    "sku": [
      "WJ12-S-Blue"
    ]
  }
}
```
read more about content-based filtering with Event Patterns at:
- https://docs.aws.amazon.com/eventbridge/latest/userguide/content-filtering-with-event-patterns.html

### Data specification

Event will be pass data into `Details` event property:
```php
(
    [sku] => WJ12-S-Blue
    [qty] => 1
)
```

Every event has a `metadata` property that contain date, timestamp and process mode of the event:
```php
(
    [metadata] => Array
        (
            [date] => 2020-08-26 08:51:18
            [timestamp] => 1598431878
            [async] => false // true if event was sent asynchronously using Magento Queue
        )
)
```

Additionally (activating tracking option in backend options) every event will be enriched with `tracking` property that contain infos about client, session and framework, for example:
```php
(
    [sku] => WJ12-S-Blue
    [qty] => 1
    [tracking] => Array
        (
            [transport] => HTTP
            [hostname] => f3a501ad4988
            [time] => 1566594699836
            [storeId] => 1
            [version] => Array
                (
                    [module] => dev-master
                    [php] => 7.1.27-1+ubuntu16.04.1+deb.sury.org+1
                    [magento] => 2.2.7
                )
            [user] => Array
                (
                    [id] => 3
                    [username] => fabio.gollinucci
                    [email] => fabio.gollinucci@bitbull.it
                )
            [ip] => 172.17.0.1
            [userAgent] => Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:68.0) Gecko/20100101 Firefox/68.0
        )
)
```
when using Magento CLI `user` is based on the system user that execute the command:
```php
(
    [sku] => WJ12-S-Blue
    [qty] => 1
    [tracking] => Array
        (
            [transport] => SHELL
            [hostname] => f3a501ad4988
            [time] => 1566821355758
            [storeId] => 1
            [version] => Array
                (
                    [module] => dev-master
                    [php] => 7.1.27-1+ubuntu16.04.1+deb.sury.org+1
                    [magento] => 2.2.7
                )
            [user] => Array
                (
                    [name] => www-data
                    [passwd] => x
                    [uid] => 1000
                    [gid] => 33
                    [gecos] => www-data
                    [dir] => /var/www
                    [shell] => /usr/sbin/nologin
                )
        )
)
``` 

### Supported Events

Here a list of supported events that can be enabled:

#### Cart events

`CartProductAdded`
A product is added to cart by a customer.

```json
{
    "sku": "abc-123",
    "qty": 1
}
```

`CartProductUpdated`
A cart is updated by a customer.

```json
{
    "sku": "abc-123",
    "operation": "add",
    "value": 1,
    "qty": {
        "from": 1,
        "to": 2
    }
}
```
```json
{
    "sku": "abc-123",
    "operation": "remove",
    "value": 1,
    "qty": {
        "from": 2,
        "to": 1
    }
}
```

`CartProductRemoved`
A product is removed from cart by a customer.

```json
{
    "sku": "abc-123",
    "qty": 2
}
```

#### Admin user events

`UserLoggedIn`
An admin user logged in.

```json
{
    "id": 1,
    "username": "admin",
    "email": "admin@example.com"
}
```

`UserLoggedOut`
An admin user logged out.

```json
{
    "id": 1,
    "username": "admin",
    "email": "admin@example.com"
}
```

`UserLoginFailed`
An admin user failed login.

```json
{
    "username": "admin"
}
```

#### Customers events

`CustomerLoggedIn`
A customer user logged in.

```json
{
    "id": 1,
    "createdAt": "2020-08-24 00:00:00",
    "email": "test@example.com",
    "firstname": "Test",
    "gender": "",
    "lastname": "Test",
    "middlename": "",
    "prefix": "",
    "suffix": ""
}
```

`CustomerLoggedOut`
A customer user logged out.

```json
{
    "id": 1,
    "createdAt": "2020-08-24 00:00:00",
    "email": "test@example.com",
    "firstname": "Test",
    "gender": "",
    "lastname": "Test",
    "middlename": "",
    "prefix": "",
    "suffix": ""
}
```

`CustomerLoginFailed`
A customer user failed login.

```json
{
    "username": "test@example.com",
    "messages": [
        "The account sign-in was incorrect or your account is disabled temporarily. Please wait and try again later."
    ]
}
```

`CustomerSignedUp`
A customer user sign up.

```json
{
    "id": 1,
    "createdAt": "2020-08-24 00:00:00",
    "email": "test@example.com",
    "firstname": "Test",
    "gender": "",
    "lastname": "Test",
    "middlename": "",
    "prefix": "",
    "suffix": ""
}
```

`CustomerSignedUpFailed`
A customer user failed sign up.

```json
{
    "email": "test@example.com",
    "firstname": "Test",
    "lastname": "Test",
    "messages": [
        "customerAlreadyExistsErrorMessage"
    ]
}
```

#### Newsletter events

`NewsletterSubscriptionChanged`
A customer user change newsletter subscription preference.

```json
{
    "customerId": 1,
    "email": "test@example.com",
    "isSubscribed": true,
    "status": "SUBSCRIBED",
    "unsubscriptionLink": "https://..",
    "code": "1234"
}
```
```json
{
    "customerId": 1,
    "email": "test@example.com",
    "isSubscribed": false,
    "status": "UNSUBSCRIBED"
}
```

#### Order events

`OrderPlaced`
```json
{
  "id": "000000001",
  "status": "pending",
  "coupon": null,
  "billingAddress": {
    "country": "IT",
    "region": null,
    "street": [
      "via di test"
    ],
    "city": "Test",
    "postCode": "12345"
  },
  "shippingAddress": {
    "country": "IT",
    "region": null,
    "street": [
      "via di test"
    ],
    "city": "Test",
    "postCode": "12345"
  },
  "shippingAmount": 5,
  "taxAmount": 0,
  "total": 64,
  "items":[{
    "sku": "24-MB02",
    "name": "Fusion Backpack",
    "price": 59,
    "qty": 1
  }]
}
```

`OrderCreated` / `OrderUpdated`
An order was saved.

`OrderCanceled`
An order was canceled.

`OrderDeleted`
An order was deleted.

#### Invoice events

`InvoiceSaved`
An invoice was created or updated.

```json
{
  "orderId": "000000001",
  "status": "OPEN",
  "billingAddress":{
    "countryId": "IT",
    "region": null,
    "street": [
      "via di test"
    ],
    "city": "Test",
    "postCode": "12345"
  },
  "shippingAmount": "10.0000",
  "taxAmount": 0,
  "total": 77,
  "items": [{
      "sku": "WS12-M-Purple",
      "name": "Radiant Tee",
      "price": "22.0000",
      "qty": 1
  }]
}
```

`InvoicePayed`
An invoice was payed.

```json
{
  "orderId": "000000001",
  "status": "PAID",
  "billingAddress":{
    "countryId": "IT",
    "region": null,
    "street": [
      "via di test"
    ],
    "city": "Test",
    "postCode": "12345"
  },
  "shippingAmount": "10.0000",
  "taxAmount": 0,
  "total": 77,
  "items": [{
      "sku": "WS12-M-Purple",
      "name": "Radiant Tee",
      "price": "22.0000",
      "qty": 1
  }]
}
```

`InvoiceRegistered`
An invoice was registered.

```json
{
  "orderId": "000000001",
  "status": "PAID",
  "billingAddress":{
    "countryId": "IT",
    "region": null,
    "street": [
      "via di test"
    ],
    "city": "Test",
    "postCode": "12345"
  },
  "shippingAmount": "10.0000",
  "taxAmount": 0,
  "total": 77,
  "items": [{
      "sku": "WS12-M-Purple",
      "name": "Radiant Tee",
      "price": "22.0000",
      "qty": 1
  }]
}
```

`OrderDeleted`
An invoice was deleted.

#### Credit Memo events

`CreditmemoSaved`
A credit memo was created or updated.

```json
{
  "orderId": "000000001",
  "shippingAmount": 10,
  "taxAmount": 0,
  "total": 77,
  "status": "OPEN",
  "items": [{
      "sku":"WS12-M-Purple",
      "name":"Radiant Tee",
      "price":"22.0000",
      "quantity":1
   }]
}
```

`CreditmemoRefunded`
A credit memo was refunded.

```json
{
  "orderId": "000000001",
  "shippingAmount": 10,
  "taxAmount": 0,
  "total": 77,
  "status": "REFUNDED",
  "items": [{
      "sku":"WS12-M-Purple",
      "name":"Radiant Tee",
      "price":"22.0000",
      "quantity":1
   }]
}
```

`CreditmemoDeleted`
A credit memo was deleted.

#### Shipment events

`ShipmentSaved`
A shipment was saved.

```json
{
  "id": "000000016",
  "tracks": [{
    "title": "DHL",
    "carrier": "dhl",
    "number": "123346457" 
   }],
  "comments": [
    "this is a comment"
  ],
  "qty": 1,
  "weight": null,
  "items": [{
    "sku": "MT07-M-Gray",
    "name": "Argus All-Weather Tank",
    "price": "22.0000",
    "qty": 1
  }]
}
```

`ShipmentDeleted`
A shipment was deleted.

#### Cache events

`CacheFlushAll`
An admin user flush the cache.

```json
{}
```

`CacheFlushSystem`
An admin user flush system cache.

```json
{}
```

`CacheFlushCatalogImages`
An admin user flush catalog images cache.

```json
{}
```

`CacheFlushMedia`
An admin user flush media cache.

```json
{}
```

`CacheFlushStaticFiles`
An admin user flush static files cache.

```json
{}
```

#### Indexer events

`StateSaved`
An index change state.

```json
{
  "index": "catalog_product_price",
  "status": "working"
}
```
```json
{
  "index": "catalogsearch_fulltext",
  "status": "valid"
}
```

