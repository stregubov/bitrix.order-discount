# bitrix.order-discount
Ограничение для скидки по количеству заказов у пользователя. На скриншоте видно "Количество заказов меньше чем ...". 

<img width="854" alt="image" src="https://user-images.githubusercontent.com/41703211/166813985-3c97b53e-4a32-4296-a859-7cd5c038162c.png">

Если пользователь авторизован, то количество заказов определяется по ID пользователя, а иначе по номеру телефона с формы оформления заказа.


Подключаем в php_interface/init.php

```php
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandlerCompatible(
    "sale",
    "OnCondSaleActionsControlBuildList",
    ["OrderCountsDiscount", "GetControlDescr"]
);
```
