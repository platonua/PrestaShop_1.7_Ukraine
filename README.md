# Модуль PrestaShop 1.7 для Украины

## Чеклист интеграции:
- [x] Установить модуль.
- [x] Передать тех поддержке PSP Platon  ссылку для коллбеков.
- [x] Провести оплату используя тестовые реквизиты.

## Установка:

* Перейти в раздел Модули -> Module Manager.

* Справа вверху нажать на кнопку Загрузить модуль.

* Перетащить архив в появившееся модальное окно.

* Зайти в настройки модуля "Platon Payment Gateway" и заполнить Platon Key и Platon Password полученные при регистрации. Gateway URL при этом не изменяем.

## Иностранные валюты:
Готовые CMS модули PSP Platon по умолчанию поддерживают только оплату в UAH.

Если необходимы иностранные валюты необходимо провести правки модуля вашими программистами согласно раздела [документации](https://platon.atlassian.net/wiki/spaces/docs/pages/1810235393).

## Ссылка для коллбеков:
* https://ВАШ_САЙТ/ru/module/platon/callback (русская версия)

* https://ВАШ_САЙТ/en/module/platon/callback (английская версия)

## Тестирование:
В целях тестирования используйте наши тестовые реквизиты.

| Номер карты  | Месяц / Год | CVV2 | Описание результата |
| :---:  | :---:  | :---:  | --- |
| 4111  1111  1111  1111 | 01 / 2024 | Любые три цифры | Успешная оплата без 3DS проверки |
| 4111  1111  1111  1111 | 02 / 2024 | Любые три цифры | Не успешная оплата без 3DS проверки |
| 4111  1111  1111  1111 | 05 / 2024 | Любые три цифры | Успешная оплата с 3DS проверкой |
| 4111  1111  1111  1111 | 06 / 2024 | Любые три цифры | Не успешная оплата с 3DS проверкой |
