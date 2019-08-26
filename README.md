## Magento 2 module AWS EventBridge integration

Event bridge to send Magento 2 events as AWS CloudWatch Events to be able to connect many different AWS serverless services.

### Installation Instructions

Install this module using composer: 

```bash
composer require bitbull/magento2-module-awseventbridge
```

### IAM permissions required

If you are using EC2 instance add these permission to your [IAM policy](https://docs.aws.amazon.com/en_us/AWSEC2/latest/UserGuide/iam-policies-for-amazon-ec2.html), 
otherwise, create a new user and attach a new policy with these required permissions:
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

### Setup

Go to "Stores" > "Configuration" > "Services" > "AWS EventBridge", then start configuring the credentials section:

![Credentials](./doc/imgs/config-credentials.png?raw=true)

- Set access and secret key, leave empty if you are hosting code from EC2 instances, use [IAM role](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/iam-roles-for-amazon-ec2.html) instead. 

![Options](./doc/imgs/config-options.png?raw=true)

- Set the correct region where you want to receive events, for example "eu-west-1".
- Set event source name with a value that can be filtered (`events:source`) when you connect to these events.
- Set event bus name, leave empty to use your account default.
- Enable tracking to add `tracking` property to data object.
- Enable debug mode if you want a more verbose logging in `var/log/aws-eventbridge.log` log file.
- Enable CloudWatch Events fallback to use this service instead of EventBridge (for backward compatibility).
- Enable dry run mode to activate the module actions and integrations without actually sending events data.

![Events](./doc/imgs/config-cart-events.png?raw=true)

These sections contain a list of supported events that can be activated and used to trigger Lambda functions.

### Event data specification

Event will be pass data into `Details` event property:
```php
(
    [sku] => WJ12-S-Blue
    [qty] => 1
    [tracking] => Array
        (
            [transport] => HTTP
            [hostname] => f3a501ad4988
            [time] => 1566821650383
        )
)
```

Additionally (activating tracking option in backend options) every event will be enriched with `tracking` property that contain infos about client, session and framework, for example:
```php
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
```
when using Magento CLI `user` is based on the system user that execute the command:
```php
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
``` 
