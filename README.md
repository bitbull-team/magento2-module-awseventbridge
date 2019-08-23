## Magento 2 module AWS EventBridge integration

Event bridge to send Magento 2 events as AWS CloudWatch Events to be able to connect many different AWS serverless services.

### Installation Instructions

Install this module using composer: 

```bash
composer require bitbull/magento2-module-awseventbridge
```

### Setup

Go to "Stores" > "Configuration" > "Services" > "AWS EventBridge", then start configuring the credentials section:

![Credentials](./doc/imgs/config-credentials.png?raw=true)

- Set the correct region where you want to receive events, for example "eu-west-1".
- Set access and secret key, leave empty if you are hosting code from EC2 instances, use [IAM role](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/iam-roles-for-amazon-ec2.html) instead.
- Set event source name with a value that can be filtered (`events:source`) when you connect to these events. 

![Developers](./doc/imgs/config-developers.png?raw=true)

- Enable debug mode if you want a more verbose logging in `var/log/aws-eventbridge.log` log file.
- Enable dry run mode to activate the module actions and integrations without actually sending events data.

![Events](./doc/imgs/config-events.png?raw=true)

This section contain a list of supported events that can be activated and used to trigger Lambda functions.

### Contributing

If you want to add new events for this plugin keep in mind this rules:

Observer classes should be placed in `Observer` directory and must extends `Bitbull\AWSEventBridge\Observer\BaseObserver` class.

Register around plugin to your observer setting it into `etc/di.xml`:
```xml
<type name="Bitbull\AWSEventBridge\Observer\User\LoggedIn">
    <plugin name="AroundEventUserLoggedIn" type="Bitbull\AWSEventBridge\Plugin\Observer" sortOrder="1" disabled="false"/>
</type>
```

Connect Observer to events that you what to track setting it into `etc/frontend/events.xml` or `etc/adminhtml/events.xml` based on event scope.
```xml
 <event name="backend_auth_user_login_success">
    <observer name="awseventbridge_user_logged_in" instance="Bitbull\AWSEventBridge\Observer\User\LoggedIn" />
</event>
```

Inside observer `execute()` method do you logic to collect data and then send it to CloudWatch using `eventEmitter` helper:
```php
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->eventEmitter->send($this->getFullEventName(), [
            'mydata' => [
                'myprop' => 'myvalue'
            ]
        ]);
    }
```
Elaborate event name using `$this->getEventName()`, this allow you to setup the event name into observer class as constant:
```php
const EVENT_NAME = 'UserLoggedIn';
```
or directly using the class name additionally to first namespace path, for example observer class named `Bitbull\AWSEventBridge\Observer\User\LoggedIn` becomes `UserLoggedIn`.

Add your event into configuration section described in `etc/adminhtml/system.xml`. 
Group and field id must follow this relation:
- Last class namespace become the group id, after camel to snake case conversion and added 'events_' prefix. example: 'Cart\ProductAdded' -> group id is 'events_cart'.
- Class name become the field id, after camel to snake case conversion. example: 'Cart\ProductAdded' -> field id is 'events_cart' .
```xml
<group id="events_order" translate="label" type="text" sortOrder="6" showInDefault="1" showInWebsite="1" showInStore="1">
    <field id="placed" type="select" sortOrder="1" showInDefault="1" showInWebsite="1" showInStore="1">
        <label>OrderPlaced</label>
        <comment><![CDATA[A customer place a new order]]></comment>
        <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
    </field>
</group>
```
use `select` filed type with `Magento\Config\Model\Config\Source\Yesno` source model. The around plugin `Bitbull\AWSEventBridge\Plugin\Observer` automatically check by event name if this configuration is active or not.
Configuration's name must follow the **snake_case** format, parsed from **CamelCase** group and event name before check.

### Event data specification

Event will be pass data into `data` object property:
```php
[data] => Array
  (
      [myprop] => 'myvalue'
  )
```

Additionally every event will be enriched with `tracking` property that contain informations about client, session and framework, for example:
```php
[tracking] => Array
    (
        [ip] => '172.17.0.1'
        [userAgent] => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:68.0) Gecko/20100101 Firefox/68.0'
        [time] => 1566311700467,
        [storeId] => 1
        [version] => Array
            (
                [module] => 'dev-master'
                [php] => '7.1.27-1+ubuntu16.04.1+deb.sury.org+1'
                [magento] => '2.2.7'
            )
        [user] => Array
            (
                [id] => 2
                [email] => 'fabio.gollinucci@bitbull.it'
            )
    )
```
ps: `user` will be NULL if user is not logged in.
