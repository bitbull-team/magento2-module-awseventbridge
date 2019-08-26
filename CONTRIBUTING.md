### Contributing

If you want to add new events for this plugin keep in mind this rules.

#### Create observer class

Observer classes should be placed in `Observer` directory and must extends `Bitbull\AWSEventBridge\Observer\BaseObserver` class.

#### Add CheckConfigFlag plugin

Register `Bitbull\AWSEventBridge\Plugin\Observer\CheckConfigFlag` around plugin to your observer into `etc/di.xml`:
```xml
<type name="Bitbull\AWSEventBridge\Observer\User\LoggedIn">
    <plugin name="AroundEventUserLoggedIn" type="Bitbull\AWSEventBridge\Plugin\Observer\CheckConfigFlag" sortOrder="1" disabled="false"/>
</type>
```
this will enable an auto-switch based con system configurations names. 

#### Register Observer

Connect Observer to events that you what to track setting it into `etc/frontend/events.xml` or `etc/adminhtml/events.xml` based on event scope.
```xml
 <event name="backend_auth_user_login_success">
    <observer name="awseventbridge_user_logged_in" instance="Bitbull\AWSEventBridge\Observer\User\LoggedIn" />
</event>
```

#### Event logic

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

#### Configurations

Add your event into configuration sections described in `etc/adminhtml/system.xml`. 
Group and field id must follow this relation:
- Last class namespace become the group id, after camel to snake case conversion and added 'events_' prefix. example: 'Cart\ProductAdded' -> group id is 'events_cart'.
- Class name become the field id, after camel to snake case conversion. example: 'Cart\ProductAdded' -> field id is 'events_cart' .
```xml
<group id="events_order" translate="label" type="text" sortOrder="6" showInDefault="1" showInWebsite="1" showInStore="1">
    <label>Order events</label>
    <field id="placed" type="select" sortOrder="1" showInDefault="1" showInWebsite="1" showInStore="1">
        <label>OrderPlaced</label>
        <comment><![CDATA[A customer place a new order]]></comment>
        <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
    </field>
</group>
```
use `select` filed type with `Magento\Config\Model\Config\Source\Yesno` source model. The around plugin `Bitbull\AWSEventBridge\Plugin\Observer` automatically check by event name if this configuration is active or not.
Configuration's name must follow the **snake_case** format, parsed from **CamelCase** group and event name before check.
